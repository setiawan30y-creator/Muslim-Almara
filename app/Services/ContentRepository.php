<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Membaca data statis (Al-Qur'an, doa, Asmaul Husna, kota) dari resources/data.
 * Data disimpan di dalam aplikasi agar tetap berjalan tanpa API eksternal.
 *
 * @2026-Alifiandra
 */
class ContentRepository
{
    public function __construct(private string $path)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function surahs(): array
    {
        return $this->json('quran/index.json');
    }

    /** @return array<string, mixed>|null */
    public function surah(int $number): ?array
    {
        if ($number < 1 || $number > 114) {
            return null;
        }

        return $this->json("quran/{$number}.json");
    }

    /** @return array<string, mixed>|null */
    public function surahMeta(int $number): ?array
    {
        return $this->surahs()[$number - 1] ?? null;
    }

    /**
     * Ayat pilihan harian (deterministik per tanggal) dari kumpulan ayat pendek.
     *
     * @return array{surah: array<string, mixed>, ayat: array<string, mixed>}
     */
    public function verseOfTheDay(string $dateKey): array
    {
        $pool = [[2, 286], [2, 152], [2, 186], [3, 139], [13, 28], [94, 5], [94, 6], [65, 3], [2, 153], [39, 53],
            [3, 8], [20, 114], [29, 69], [16, 97], [8, 46], [49, 13], [17, 24], [25, 74], [55, 13], [93, 5], [3, 159], [2, 45], [40, 60]];
        [$s, $a] = $pool[crc32($dateKey) % count($pool)];
        $surah = $this->surah($s);

        return ['surah' => collect($surah)->except('ayat')->all(), 'ayat' => $surah['ayat'][$a - 1]];
    }

    /** @return array<int, array<string, mixed>> */
    public function doa(): array
    {
        return $this->json('doa.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function asmaulHusna(): array
    {
        return $this->json('asmaul-husna.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function cities(): array
    {
        return $this->json('kota.json');
    }

    /** @return array<string, mixed>|null */
    public function city(string $slug): ?array
    {
        foreach ($this->cities() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }

        return null;
    }

    /** @return array<mixed> */
    private function json(string $file): array
    {
        $full = $this->path.DIRECTORY_SEPARATOR.$file;

        return Cache::rememberForever('content:'.$file.':'.@filemtime($full), function () use ($full) {
            if (! is_file($full)) {
                return [];
            }

            return json_decode((string) file_get_contents($full), true, flags: JSON_THROW_ON_ERROR);
        });
    }
}
