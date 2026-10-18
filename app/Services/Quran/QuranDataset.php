<?php

namespace App\Services\Quran;

/**
 * Metadata dataset Al-Qur'an yang disertakan di resources/data/quran.
 * Sumber: Kementerian Agama RI (quran.kemenag.go.id) melalui rioastamal/quran-json (MIT).
 *
 * @2026-Alifiandra
 */
final class QuranDataset
{
    public const SURAH_COUNT = 114;
    public const VERSE_COUNT = 6236;

    public const DATASET_VERSION = 'quran-json@73b1d52 (2021-06-08)';

    public const SOURCES = [
        'quran-text-kemenag' => [
            'name' => "Teks Al-Qur'an (rasm Kemenag RI)",
            'content_type' => 'quran_text',
            'url' => 'https://github.com/rioastamal/quran-json',
            'documentation_url' => 'https://quran.kemenag.go.id',
            'license' => 'MIT (dataset quran-json); teks bersumber dari Kementerian Agama RI',
            'attribution' => "Teks Al-Qur'an: Kementerian Agama Republik Indonesia (quran.kemenag.go.id), melalui dataset terbuka quran-json oleh Rio Astamal.",
        ],
        'quran-translation-kemenag-id' => [
            'name' => "Terjemahan Al-Qur'an Bahasa Indonesia (Kemenag RI)",
            'content_type' => 'quran_translation',
            'url' => 'https://github.com/rioastamal/quran-json',
            'documentation_url' => 'https://quran.kemenag.go.id',
            'license' => 'MIT (dataset quran-json); terjemahan © Kementerian Agama RI — periksa ketentuan redistribusi sebelum produksi',
            'attribution' => "Terjemahan: Al-Qur'an dan Terjemahannya, Kementerian Agama Republik Indonesia.",
        ],
        'quran-tafsir-kemenag-id' => [
            'name' => 'Tafsir Ringkas Kemenag RI',
            'content_type' => 'quran_tafsir',
            'url' => 'https://github.com/rioastamal/quran-json',
            'documentation_url' => 'https://quran.kemenag.go.id',
            'license' => 'MIT (dataset quran-json); tafsir © Kementerian Agama RI — periksa ketentuan redistribusi sebelum produksi',
            'attribution' => 'Tafsir: Tafsir Kementerian Agama Republik Indonesia.',
        ],
    ];

    /** Surah Madaniyah (klasifikasi Tanzil). */
    public const MADANIYAH = [2, 3, 4, 5, 8, 9, 13, 22, 24, 33, 47, 48, 49, 55, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 76, 98, 99, 110];

    /** Awal setiap juz [surah, ayat] (pembagian juz standar Mushaf Madinah). */
    public const JUZ_STARTS = [
        1 => [1, 1], [2, 142], [2, 253], [3, 93], [4, 24], [4, 148], [5, 82], [6, 111], [7, 88], [8, 41],
        [9, 93], [11, 6], [12, 53], [15, 1], [17, 1], [18, 75], [21, 1], [23, 1], [25, 21], [27, 56],
        [29, 46], [33, 31], [36, 28], [39, 32], [41, 47], [46, 1], [51, 31], [58, 1], [67, 1], [78, 1],
    ];

    public static function juzFor(int $surah, int $ayah): int
    {
        $juz = 1;
        foreach (self::JUZ_STARTS as $j => [$s, $a]) {
            if ($surah > $s || ($surah === $s && $ayah >= $a)) {
                $juz = $j;
            } else {
                break;
            }
        }

        return $juz;
    }

    public static function path(?string $file = null): string
    {
        return resource_path('data/quran'.($file ? '/'.$file : ''));
    }

    /** SHA-256 seluruh berkas dataset (urut nama) untuk deteksi perubahan. */
    public static function checksum(): string
    {
        $ctx = hash_init('sha256');
        $files = glob(self::path('*.json')) ?: [];
        natsort($files);
        foreach ($files as $f) {
            hash_update($ctx, basename($f)."\n");
            hash_update_file($ctx, $f);
        }

        return hash_final($ctx);
    }
}
