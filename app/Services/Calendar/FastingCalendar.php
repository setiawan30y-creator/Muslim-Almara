<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;

/**
 * Penanda hari puasa (wajib, sunnah, terlarang) berdasarkan kalender Hijriah urfi.
 * Tanggal Hijriah resmi dapat berbeda ±1 hari (isbat) — ditampilkan sebagai perkiraan.
 * @2026-Alifiandra
 */
class FastingCalendar
{
    /** jenis => [label, nada, rujukan] */
    public const KINDS = [
        'ramadan' => ['Puasa Ramadan (wajib)', 'success', 'QS. Al-Baqarah: 183–185'],
        'arafah' => ['Puasa Arafah', 'warning', 'HR. Muslim no. 1162'],
        'tasua' => ["Puasa Tasu'a (9 Muharram)", 'warning', 'HR. Muslim no. 1134'],
        'asyura' => ['Puasa Asyura (10 Muharram)', 'warning', 'HR. Muslim no. 1162'],
        'syawal' => ['Puasa 6 hari Syawal (boleh dipilih)', 'neutral', 'HR. Muslim no. 1164'],
        'ayyamul_bidh' => ['Ayyamul Bidh (13–15 Hijriah)', 'neutral', 'HR. An-Nasa\'i no. 2420; HR. At-Tirmidzi no. 761'],
        'senin_kamis' => ['Puasa Senin/Kamis', 'neutral', 'HR. At-Tirmidzi no. 747'],
        'haram' => ['Dilarang berpuasa', 'danger', 'HR. Bukhari no. 1990; HR. Muslim no. 1141'],
    ];

    /** @return array<int, string> */
    public static function classify(CarbonImmutable $date, int $hMonth, int $hDay): array
    {
        // Hari terlarang: Idul Fitri, Idul Adha, hari tasyrik
        if (($hMonth === 10 && $hDay === 1) || ($hMonth === 12 && in_array($hDay, [10, 11, 12, 13], true))) {
            return ['haram'];
        }
        if ($hMonth === 9) {
            return ['ramadan'];
        }
        $tags = [];
        if ($hMonth === 12 && $hDay === 9) {
            $tags[] = 'arafah';
        }
        if ($hMonth === 1 && $hDay === 9) {
            $tags[] = 'tasua';
        }
        if ($hMonth === 1 && $hDay === 10) {
            $tags[] = 'asyura';
        }
        if ($hMonth === 10 && $hDay >= 2) {
            $tags[] = 'syawal';
        }
        if (in_array($hDay, [13, 14, 15], true)) {
            $tags[] = 'ayyamul_bidh';
        }
        if ($date->isMonday() || $date->isThursday()) {
            $tags[] = 'senin_kamis';
        }

        return $tags;
    }
}
