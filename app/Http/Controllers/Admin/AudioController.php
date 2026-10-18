<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AudioUploadRequest;
use App\Models\ContentReview;
use App\Models\QuranAudioTrack;
use App\Models\QuranReciter;
use App\Services\Quran\Audio\AudioWorkflow;
use App\Services\Quran\Audio\QuranFoundationClient;
use App\Services\Quran\QuranService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Admin: Qari ALMARA — unggah, review, terbitkan rekaman. @2026-Alifiandra
class AudioController extends Controller
{
    public function __construct(private AudioWorkflow $workflow)
    {
    }

    public function index(Request $request, QuranService $quran, QuranFoundationClient $qf)
    {
        $status = ContentStatus::tryFrom((string) $request->query('status'));
        $reciterId = $request->integer('qari') ?: null;
        $tracks = QuranAudioTrack::query()->with(['reciter:id,name,slug', 'creator:id,name', 'reviewer:id,name', 'surah:id,number,name_latin'])
            ->withCount('segments')
            ->when($status, fn ($q) => $q->where('review_status', $status->value))
            ->when($reciterId, fn ($q) => $q->where('reciter_id', $reciterId))
            ->orderBy('surah_number')->orderByDesc('version')->paginate(40)->withQueryString();

        return view('admin.audio.index', [
            'tracks' => $tracks,
            'status' => $status,
            'reciterId' => $reciterId,
            'reciters' => QuranReciter::query()->withCount(['tracks as published_count' => fn ($q) => $q->published()])->orderBy('sort_order')->orderBy('name')->get(),
            'surahs' => $quran->surahs(),
            'qf' => ['enabled' => $qf->configured(), 'mock' => $qf->isMock()],
        ]);
    }

    public function store(AudioUploadRequest $request)
    {
        $track = $this->workflow->upload($request->file('audio'), $request->validated(), $request->user());

        return redirect()->route('admin.audio.show', $track)->with('status', "Rekaman versi {$track->version} masuk karantina (draf). Periksa lalu ajukan review.");
    }

    public function show(QuranAudioTrack $track)
    {
        $track->load(['reciter', 'surah', 'segments', 'creator:id,name', 'submitter:id,name', 'reviewer:id,name']);
        $reviews = ContentReview::query()->with('reviewer:id,name')->where('content_type', 'audio')->where('content_id', $track->id)->latest('created_at')->get();
        $segmentsText = $track->segments->map(fn ($s) => sprintf('%d %s %s', $s->ayah_number, self::fmt($s->start_ms), self::fmt($s->end_ms)))->implode("\n");

        return view('admin.audio.show', compact('track', 'reviews', 'segmentsText'));
    }

    private static function fmt(int $ms): string
    {
        $rest = ($ms % 60000) / 1000;
        $sec = rtrim(rtrim(number_format($rest, 3, '.', ''), '0'), '.');

        return intdiv($ms, 60000).':'.($rest < 10 ? '0' : '').$sec;
    }

    public function segments(Request $request, QuranAudioTrack $track)
    {
        $data = $request->validate(['segments' => ['nullable', 'string', 'max:20000']]);
        $this->workflow->updateSegments($track, $data['segments'] ?? null, $request->user());

        return back()->with('status', 'Penanda ayat disimpan.');
    }

    public function transition(Request $request, QuranAudioTrack $track, string $action)
    {
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:1000']])['notes'] ?? null;
        $this->workflow->transition($track, $action, $request->user(), $notes);

        if ($action === 'delete') {
            return redirect()->route('admin.audio.index')->with('status', 'Draf rekaman dihapus.');
        }

        return back()->with('status', 'Status: '.$track->review_status->label().'.');
    }

    public function storeReciter(Request $request)
    {
        abort_unless($request->user()->hasPermission('audio.upload'), 403);
        $data = $request->validate($this->reciterRules());
        $base = Str::slug($data['name']) ?: 'qari';
        $slug = $base;
        for ($i = 2; QuranReciter::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }
        $r = QuranReciter::query()->create($data + ['slug' => $slug, 'is_active' => true]);
        Audit::log('audio.reciter_created', $r, ['name' => $r->name]);

        return back()->with('status', "Qari \"{$r->name}\" ditambahkan.");
    }

    public function updateReciter(Request $request, QuranReciter $reciter)
    {
        abort_unless($request->user()->hasPermission('content.publish'), 403);
        $data = $request->validate($this->reciterRules() + ['is_active' => ['nullable', 'boolean']]);
        $data['is_active'] = $request->boolean('is_active');
        $reciter->update($data);
        Audit::log('audio.reciter_updated', $reciter, ['name' => $reciter->name, 'active' => $reciter->is_active]);

        return back()->with('status', 'Data qari disimpan.');
    }

    private function reciterRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'style' => ['nullable', 'string', 'max:60', Rule::in(['Murattal', 'Mujawwad', 'Mu\'allim', 'Lainnya'])],
            'license_note' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ];
    }
}
