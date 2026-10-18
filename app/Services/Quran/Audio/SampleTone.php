<?php

namespace App\Services\Quran\Audio;

/**
 * Nada uji untuk mode simulasi pemutar (BUKAN bacaan Al-Qur'an).
 * Satu bunyi "bip" pendek per ayat, 2 detik per ayat, maksimal 12 ayat.
 * @2026-Alifiandra
 */
class SampleTone
{
    public const SECONDS_PER_AYAH = 2;
    public const MAX_AYAH = 12;

    private static function count(int $ayahCount): int
    {
        return max(1, min(self::MAX_AYAH, $ayahCount ?: 1));
    }

    /** @return array<int, array{ayah: int, start_ms: int, end_ms: int}> */
    public static function segments(int $ayahCount): array
    {
        $out = [];
        for ($i = 0; $i < self::count($ayahCount); $i++) {
            $out[] = ['ayah' => $i + 1, 'start_ms' => $i * self::SECONDS_PER_AYAH * 1000, 'end_ms' => ($i + 1) * self::SECONDS_PER_AYAH * 1000];
        }

        return $out;
    }

    public static function durationMs(int $ayahCount): int
    {
        return self::count($ayahCount) * self::SECONDS_PER_AYAH * 1000;
    }

    /** WAV mono 8 kHz 8-bit. */
    public static function wav(int $ayahCount): string
    {
        $rate = 8000;
        $n = self::count($ayahCount);
        $pcm = '';
        for ($a = 0; $a < $n; $a++) {
            $freq = 440 + ($a % 4) * 110;
            for ($i = 0; $i < $rate * self::SECONDS_PER_AYAH; $i++) {
                $t = $i / $rate;
                $v = $t < 0.25 ? sin(2 * M_PI * $freq * $t) * 0.25 * (1 - $t / 0.25) : 0.0;
                $pcm .= chr((int) round(128 + $v * 127));
            }
        }
        $len = strlen($pcm);

        return 'RIFF'.pack('V', 36 + $len).'WAVE'.'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate, 1, 8).'data'.pack('V', $len).$pcm;
    }
}
