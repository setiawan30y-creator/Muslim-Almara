<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentItemRequest;
use App\Models\ContentReview;
use App\Models\DhikrCategory;
use App\Models\DuaCategory;
use App\Services\Content\ContentWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Kelola doa & dzikir dengan alur review. @2026-Alifiandra
class ContentController extends Controller
{
    public function __construct(private ContentWorkflow $workflow)
    {
    }

    private function categories(string $type)
    {
        if ($type === 'panduan') {
            $order = array_keys(\App\Models\GuideCategory::MODULES);

            return \App\Models\GuideCategory::query()->orderBy('sort_order')->get()
                ->sortBy(fn ($c) => array_search($c->module, $order, true))->values()
                ->each(fn ($c) => $c->title = $c->moduleLabel().' — '.$c->title);
        }

        return ($type === 'doa' ? DuaCategory::class : DhikrCategory::class)::query()->orderBy('sort_order')->get();
    }

    public function index(Request $request, string $type)
    {
        $class = ContentWorkflow::model($type);
        $status = ContentStatus::tryFrom((string) $request->query('status'));
        $q = trim((string) $request->query('q'));
        $items = $class::query()->with(['category', 'reviewer:id,name'])
            ->when($status, fn ($query) => $query->where('review_status', $status->value))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('translation', 'like', "%{$q}%")))
            ->orderBy('category_id')->orderBy('sort_order')->paginate(30)->withQueryString();

        return view('admin.content.index', ['type' => $type, 'items' => $items, 'status' => $status, 'q' => $q]);
    }

    public function create(string $type)
    {
        abort_unless(auth()->user()->hasPermission('content.edit'), 403);

        return view('admin.content.form', ['type' => $type, 'item' => null, 'categories' => $this->categories($type), 'reviews' => collect()]);
    }

    public function store(ContentItemRequest $request, string $type)
    {
        $item = $this->workflow->create($type, $request->validated(), $request->user());

        return redirect()->route('admin.content.edit', [$type, $item->id])->with('status', 'Draf dibuat. Ajukan review bila sudah siap.');
    }

    public function edit(string $type, int $id)
    {
        $item = ContentWorkflow::model($type)::query()->with(['creator:id,name', 'submitter:id,name', 'reviewer:id,name', 'source'])->findOrFail($id);
        $reviews = ContentReview::query()->with('reviewer:id,name')->where('content_type', $type)->where('content_id', $id)->latest('created_at')->get();

        return view('admin.content.form', ['type' => $type, 'item' => $item, 'categories' => $this->categories($type), 'reviews' => $reviews]);
    }

    public function update(ContentItemRequest $request, string $type, int $id)
    {
        $item = ContentWorkflow::model($type)::query()->findOrFail($id);
        $this->workflow->update($item, $request->validated(), $request->user(), $type);

        return back()->with('status', $item->review_status === ContentStatus::Draft ? 'Perubahan disimpan sebagai versi baru (draf) dan perlu ditinjau ulang.' : 'Disimpan.');
    }

    public function transition(Request $request, string $type, int $id, string $action)
    {
        $item = ContentWorkflow::model($type)::query()->findOrFail($id);
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:1000']])['notes'] ?? null;
        $this->workflow->transition($item, $action, $request->user(), $type, $notes);

        return back()->with('status', 'Status: '.$item->review_status->label().'.');
    }

    public function bulk(Request $request, string $type)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['submit', 'approve-publish', 'publish', 'suspend'])],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Centang pernyataan bahwa konten telah Anda periksa.']);

        $ok = 0;
        $errors = [];
        foreach (ContentWorkflow::model($type)::query()->whereIn('id', $data['ids'])->get() as $item) {
            try {
                $this->workflow->transition($item, $data['action'], $request->user(), $type, 'Tindakan massal');
                $ok++;
            } catch (ValidationException|AuthorizationException $e) {
                $errors[] = $item->title.': '.($e instanceof ValidationException ? collect($e->errors())->flatten()->first() : $e->getMessage());
            }
        }

        return back()->with('status', "{$ok} item diproses.".($errors ? ' '.count($errors).' dilewati.' : ''))->with('bulk_errors', $errors);
    }
}
