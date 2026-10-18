<?php

namespace App\Services\Prayer;

/**
 * Parameter perhitungan waktu sholat yang sedang aktif (DTO).
 * @2026-Alifiandra
 */
final class PrayerParameters
{
    public const OFFSET_KEYS = ['imsak', 'subuh', 'terbit', 'duha', 'zuhur', 'asar', 'magrib', 'isya'];

    /**
     * @param  array<string, int>  $offsets  koreksi manual per waktu (menit, −30…30)
     */
    public function __construct(
        public readonly string $methodKey = 'kemenag',
        public readonly string $methodName = 'Kementerian Agama RI',
        public readonly float $fajrAngle = 20.0,
        public readonly ?float $ishaAngle = 18.0,
        public readonly ?int $ishaMinutes = null,
        public readonly int $asrFactor = 1,
        public readonly int $ihtiyat = 2,
        public readonly int $imsakMinutes = 10,
        public readonly float $duhaAltitude = 4.5,
        public readonly array $offsets = [],
    ) {
    }

    /** @param array<string, mixed> $p parameters_json dari tabel prayer_methods */
    public static function fromMethod(string $key, string $name, array $p, int $asrFactor = 1, array $offsets = []): self
    {
        return new self(
            methodKey: $key,
            methodName: $name,
            fajrAngle: (float) ($p['fajr_angle'] ?? 18),
            ishaAngle: isset($p['isha_angle']) ? (float) $p['isha_angle'] : null,
            ishaMinutes: isset($p['isha_minutes']) ? (int) $p['isha_minutes'] : null,
            asrFactor: $asrFactor,
            ihtiyat: (int) ($p['ihtiyat'] ?? 0),
            imsakMinutes: (int) ($p['imsak_minutes'] ?? 10),
            duhaAltitude: (float) ($p['duha_altitude'] ?? 4.5),
            offsets: self::cleanOffsets($offsets),
        );
    }

    /** @return array<string, int> */
    public static function cleanOffsets(array $offsets): array
    {
        $out = [];
        foreach (self::OFFSET_KEYS as $k) {
            $v = (int) ($offsets[$k] ?? 0);
            if ($v !== 0) {
                $out[$k] = max(-30, min(30, $v));
            }
        }

        return $out;
    }

    public function describe(): string
    {
        $isha = $this->ishaMinutes !== null ? $this->ishaMinutes.' menit setelah Magrib' : $this->ishaAngle.'°';
        $parts = ["Subuh {$this->fajrAngle}°", "Isya {$isha}", 'Asar '.($this->asrFactor === 2 ? 'Hanafi' : 'jumhur')];
        if ($this->ihtiyat) {
            $parts[] = "ihtiyat {$this->ihtiyat} menit";
        }
        if ($this->offsets) {
            $parts[] = 'koreksi manual aktif';
        }

        return implode(', ', $parts);
    }
}
