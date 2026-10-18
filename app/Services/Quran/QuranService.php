<?php

namespace App\Services\Quran;

use App\Models\QuranSurah;
use App\Models\QuranTranslation;
use App\Models\QuranVerse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Akses baca Al-Qur'an dari database dengan cache berbasis versi.
 * @2026-Alifiandra
 */
class QuranService
{
    /** Kumpulan ayat pendek untuk "Ayat hari ini". */
    private const DAILY_POOL = [[2, 286], [2, 152], [2, 186], [3, 139], [13, 28], [94, 5], [94, 6], [65, 3], [2, 153], [39, 53],
        [3, 8], [20, 114], [29, 69], [16, 97], [8, 46], [49, 13], [17, 24], [25, 74], [55, 13], [93, 5], [3, 159], [2, 45], [40, 60]];

    /** @var array<int, Collection<int, QuranVerse>> memo per request */
    private array $verseMemo = [];

    private ?Collection $surahMemo = null;

    /**
     * Daftar surah (114 baris) — dimemo per request. Model Eloquent sengaja tidak
     * disimpan ke cache persisten (Laravel membatasi unserialize objek demi keamanan).
     *
     * @return Collection<int, QuranSurah>
     */
    public function surahs(): Collection
    {
        return $this->surahMemo ??= QuranSurah::query()->orderBy('number')->get();
    }

    public function surah(int $number): ?QuranSurah
    {
        return $this->surahs()->firstWhere('number', $number);
    }

    /** @return Collection<int, QuranVerse> */
    public function verses(QuranSurah $surah): Collection
    {
        return $this->verseMemo[$surah->number] ??= $surah->verses()
            ->with(['translationId:id,verse_id,text,translator,source_id', 'tafsirId:id,verse_id,text,work_name'])
            ->get();
    }

    public function verse(int $surah, int $ayah): ?QuranVerse
    {
        return QuranVerse::query()->with(['surah', 'translationId'])
            ->whereHas('surah', fn ($q) => $q->where('number', $surah))
            ->where('ayah_number', $ayah)->first();
    }

    public function verseOfTheDay(string $dateKey): ?QuranVerse
    {
        [$s, $a] = self::DAILY_POOL[crc32($dateKey) % count(self::DAILY_POOL)];

        return $this->verse($s, $a) ?? QuranVerse::query()->with(['surah', 'translationId'])->first();
    }

    /** @return array<int, array{juz: int, surah: int, ayah: int, name: string}> */
    public function juzList(): array
    {
        $names = $this->surahs()->pluck('name_latin', 'number');
        $out = [];
        foreach (QuranDataset::JUZ_STARTS as $j => [$s, $a]) {
            $out[] = ['juz' => $j, 'surah' => $s, 'ayah' => $a, 'name' => $names[$s] ?? "Surah {$s}"];
        }

        return $out;
    }

    /**
     * Pencarian dasar pada terjemahan Indonesia (dan referensi "2:255").
     */
    public function search(string $q, int $perPage = 20): LengthAwarePaginator
    {
        $q = trim(preg_replace('/\s+/', ' ', $q));
        $query = QuranTranslation::query()->where('language', 'id')
            ->with(['verse.surah'])
            ->join('quran_verses', 'quran_verses.id', '=', 'quran_translations.verse_id')
            ->join('quran_surahs', 'quran_surahs.id', '=', 'quran_verses.surah_id')
            ->select('quran_translations.*')
            ->orderBy('quran_surahs.number')->orderBy('quran_verses.ayah_number');

        if (preg_match('/^(\d{1,3})\s*[:.]\s*(\d{1,3})$/', $q, $m)) {
            $query->where('quran_surahs.number', (int) $m[1])->where('quran_verses.ayah_number', (int) $m[2]);
        } else {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where('quran_translations.text', 'like', $like);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
