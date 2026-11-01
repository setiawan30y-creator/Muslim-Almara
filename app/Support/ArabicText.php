<?php

namespace App\Support;

/**
 * Normalisasi teks Arab untuk pencarian: hapus harakat & tanda waqaf, satukan bentuk alif/ya/ta marbuthah.
 * "اَللّٰهُ لَآ اِلٰهَ" → "الله لا اله". @2026-Alifiandra
 */
class ArabicText
{
    public static function hasArabic(string $s): bool
    {
        return (bool) preg_match('/[\x{0621}-\x{064A}\x{0671}-\x{06D3}]/u', $s);
    }

    public static function normalize(string $s): string
    {
        $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D3}-\x{08FF}\x{0640}\x{200C}\x{200D}]/u', '', $s);
        $s = strtr($s, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ٲ' => 'ا', 'ٳ' => 'ا',
            'ى' => 'ي', 'ی' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه', 'ە' => '', 'ک' => 'ك', 'ء' => '',
        ]);
        // sisakan huruf Arab & spasi saja
        $s = preg_replace('/[^\x{0621}-\x{064A}\s]/u', ' ', $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
