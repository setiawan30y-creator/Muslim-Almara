<?php

namespace App\Http\Controllers\Quran;

use App\Http\Controllers\Controller;
use App\Services\Preferences\PreferenceService;
use App\Services\Quran\QuranService;
use Illuminate\Http\Request;

// @2026-Alifiandra
class QuranController extends Controller
{
    public function __construct(private QuranService $quran)
    {
    }

    public function index(Request $request)
    {
        return view('pages.quran.index', [
            'surahs' => $this->quran->surahs(),
            'juz' => $this->quran->juzList(),
            'tab' => $request->query('tab') === 'juz' ? 'juz' : 'surah',
            'lastRead' => $request->user()?->lastRead()->with('verse.surah')->first(),
        ]);
    }

    public function show(Request $request, int $nomor, PreferenceService $prefs)
    {
        $surah = $this->quran->surah($nomor);
        abort_if(! $surah, 404);
        $user = $request->user();
        $catalog = app(\App\Services\Quran\Audio\AudioCatalog::class);
        $reciters = $catalog->reciters($nomor);

        return view('pages.quran.show', [
            'audio' => ['reciters' => $reciters, 'current' => $catalog->resolve($prefs->quran()['reciter'], $nomor)],
            'surah' => $surah,
            'verses' => $this->quran->verses($surah),
            'prev' => $this->quran->surah($nomor - 1),
            'next' => $this->quran->surah($nomor + 1),
            'prefs' => $prefs->quran(),
            'mode' => $prefs->effectiveReadingMode(),
            'mushafAvailable' => PreferenceService::mushafAvailable(),
            'bookmarked' => $user ? $user->bookmarks()->whereIn('verse_id', $this->quran->verses($surah)->pluck('id'))->pluck('verse_id')->flip() : collect(),
            'noteCounts' => $user ? $user->notes()->whereNotNull('verse_id')->selectRaw('verse_id, count(*) c')->groupBy('verse_id')->pluck('c', 'verse_id') : collect(),
            'source' => $surah->source,
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $q = (string) ($data['q'] ?? '');

        return view('pages.quran.search', [
            'q' => $q,
            'results' => $q !== '' ? $this->quran->search($q) : null,
            'surahMatches' => $q !== '' ? $this->quran->surahs()->filter(fn ($s) => str_contains(mb_strtolower($s->name_latin.' '.$s->name_translation), mb_strtolower($q)))->take(6) : collect(),
        ]);
    }
}
