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

    /** Nama populer beberapa ayat (judul halaman lebih mudah ditemukan di mesin pencari). */
    public const FAMOUS = [
        '2:255' => 'Ayat Kursi', '2:285' => 'Amanar Rasul', '2:286' => 'Akhir Surah Al-Baqarah', '2:201' => 'Doa Sapu Jagat',
        '2:183' => 'Kewajiban Puasa', '9:128' => "Laqad Ja'akum", '24:35' => 'Ayat Nur', '3:8' => 'Doa Keteguhan Hati',
        '17:24' => 'Doa untuk Orang Tua', '20:114' => 'Doa Tambahan Ilmu', '25:74' => 'Doa Keturunan Saleh', '94:5' => 'Bersama Kesulitan Ada Kemudahan',
    ];

    /** Halaman per ayat (SEO): /al-quran/{surah}/{ayat}. */
    public function verse(Request $request, int $nomor, int $ayat, PreferenceService $prefs)
    {
        $surah = $this->quran->surah($nomor);
        abort_if(! $surah || $ayat < 1 || $ayat > $surah->ayah_count, 404);
        $verse = \App\Models\QuranVerse::query()->with(['surah', 'translationId', 'tafsirId'])
            ->where('surah_id', $surah->id)->where('ayah_number', $ayat)->firstOrFail();

        return view('pages.quran.verse', [
            'surah' => $surah,
            'verse' => $verse,
            'famous' => self::FAMOUS[$nomor.':'.$ayat] ?? null,
            'prevRef' => $ayat > 1 ? [$nomor, $ayat - 1] : ($nomor > 1 ? [$nomor - 1, \App\Services\Quran\QuranDataset::AYAH_COUNTS[$nomor - 2]] : null),
            'nextRef' => $ayat < $surah->ayah_count ? [$nomor, $ayat + 1] : ($nomor < 114 ? [$nomor + 1, 1] : null),
            'showLatin' => PreferenceService::latinAvailable(),
            'source' => $surah->source,
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
            'latinAvailable' => PreferenceService::latinAvailable(),
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
