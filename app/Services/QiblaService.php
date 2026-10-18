<?php

namespace App\Services;

/**
 * Arah kiblat (great-circle bearing) dan jarak ke Ka'bah.
 *
 * @2026-Alifiandra
 */
class QiblaService
{
    public const KAABA_LAT = 21.422487;
    public const KAABA_LNG = 39.826206;

    /** Sudut dari arah utara sejati, searah jarum jam (0–360°). */
    public function bearing(float $lat, float $lng): float
    {
        $phi1 = deg2rad($lat);
        $phi2 = deg2rad(self::KAABA_LAT);
        $dl = deg2rad(self::KAABA_LNG - $lng);

        $y = sin($dl);
        $x = cos($phi1) * tan($phi2) - sin($phi1) * cos($dl);
        $deg = rad2deg(atan2($y, $x));

        return fmod($deg + 360, 360);
    }

    /** Jarak dalam kilometer (haversine). */
    public function distanceKm(float $lat, float $lng): float
    {
        $r = 6371.0;
        $dLat = deg2rad(self::KAABA_LAT - $lat);
        $dLng = deg2rad(self::KAABA_LNG - $lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad(self::KAABA_LAT)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function compassLabel(float $deg): string
    {
        $labels = ['Utara', 'Utara–Timur Laut', 'Timur Laut', 'Timur–Timur Laut', 'Timur', 'Timur–Tenggara', 'Tenggara', 'Selatan–Tenggara',
            'Selatan', 'Selatan–Barat Daya', 'Barat Daya', 'Barat–Barat Daya', 'Barat', 'Barat–Barat Laut', 'Barat Laut', 'Utara–Barat Laut'];

        return $labels[(int) round($deg / 22.5) % 16];
    }
}
