<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\QuranVerse;
use App\Services\Memorization\MemorizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GET    /api/v1/me/memorization?surah=      — status hafalan milik pengguna
 * POST   /api/v1/me/memorization             — {verse_id, grade: lupa|ragu|lancar}
 * DELETE /api/v1/me/memorization?surah=      — atur ulang hafalan satu surah
 * @2026-Alifiandra
 */
class MemorizationController extends ApiController
{
    public function __construct(private MemorizationService $mem)
    {
    }

    private function present($m): array
    {
        return [
            'verse_id' => $m->verse_id,
            'status' => $m->status,
            'interval_days' => $m->interval_days,
            'next_review_on' => $m->next_review_on?->toDateString(),
            'next_review_label' => $m->next_review_on ? ($m->next_review_on->isToday() ? 'hari ini' : $m->next_review_on->translatedFormat('j M Y')) : null,
            'reviews' => $m->reviews,
            'lapses' => $m->lapses,
            'last_grade' => $m->last_grade,
        ];
    }

    public function index(Request $request)
    {
        $data = $request->validate(['surah' => ['nullable', 'integer', 'between:1,114']]);
        $items = $request->user()->memorizations()
            ->when($data['surah'] ?? null, fn ($q, $s) => $q->whereHas('verse.surah', fn ($w) => $w->where('number', $s)))
            ->orderBy('verse_id')->get()->map(fn ($m) => $this->present($m))->all();

        return $this->ok($items, $this->mem->stats($request->user()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'verse_id' => ['required', 'integer', 'exists:quran_verses,id'],
            'grade' => ['required', Rule::in(array_keys(MemorizationService::GRADES))],
        ]);
        $m = $this->mem->grade($request->user(), QuranVerse::query()->findOrFail($data['verse_id']), $data['grade']);

        return $this->ok($this->present($m), $this->mem->stats($request->user()));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['surah' => ['required', 'integer', 'between:1,114']]);
        $n = $request->user()->memorizations()->whereHas('verse.surah', fn ($w) => $w->where('number', $data['surah']))->delete();

        return $this->ok(['deleted' => $n]);
    }
}
