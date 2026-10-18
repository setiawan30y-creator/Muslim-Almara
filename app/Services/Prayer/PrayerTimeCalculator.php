<?php

namespace App\Services\Prayer;

use Carbon\CarbonImmutable;

/**
 * Perhitungan waktu sholat secara astronomis (tanpa API eksternal), stateless.
 * Parameter (metode, mazhab Asar, koreksi menit) diberikan lewat PrayerParameters
 * sehingga hasil selalu dapat dijelaskan ke pengguna (blueprint §4.2).
 * Hasil adalah perkiraan; selisih 1–2 menit dengan jadwal resmi daerah bisa terjadi.
 *
 * @2026-Alifiandra
 */
class PrayerTimeCalculator
{
    public const NAMES = [
        'imsak' => 'Imsak',
        'subuh' => 'Subuh',
        'terbit' => 'Terbit',
        'duha' => 'Duha',
        'zuhur' => 'Zuhur',
        'asar' => 'Asar',
        'magrib' => 'Magrib',
        'isya' => 'Isya',
    ];

    /** Waktu yang merupakan sholat wajib (dipakai untuk "sholat berikutnya"). */
    public const OBLIGATORY = ['subuh', 'zuhur', 'asar', 'magrib', 'isya'];

    /**
     * Hitung jadwal untuk satu tanggal.
     *
     * @return array<string, string> nama => "HH:MM"
     */
    public function forDate(CarbonImmutable $date, float $lat, float $lng, float $tz, ?PrayerParameters $p = null): array
    {
        $p ??= new PrayerParameters;
        $jd = $this->julianDay((int) $date->year, (int) $date->month, (int) $date->day) - $lng / 360.0;

        // Perkiraan awal (dalam jam), lalu disempurnakan dua kali.
        $t = ['subuh' => 5, 'terbit' => 6, 'duha' => 6.5, 'zuhur' => 12, 'asar' => 15, 'magrib' => 18, 'isya' => 19];

        for ($i = 0; $i < 2; $i++) {
            $t = [
                'subuh' => $this->sunAngleTime($jd, $p->fajrAngle, $t['subuh'], $lat, true),
                'terbit' => $this->sunAngleTime($jd, 0.833, $t['terbit'], $lat, true),
                'duha' => $this->sunAngleTime($jd, -$p->duhaAltitude, $t['duha'], $lat, true),
                'zuhur' => $this->midDay($jd, $t['zuhur']),
                'asar' => $this->asrTime($jd, $p->asrFactor, $t['asar'], $lat),
                'magrib' => $this->sunAngleTime($jd, 0.833, $t['magrib'], $lat, false),
                'isya' => $p->ishaMinutes !== null ? 0.0 : $this->sunAngleTime($jd, (float) $p->ishaAngle, $t['isya'], $lat, false),
            ];
        }

        // Ubah ke waktu lokal.
        $offset = $tz - $lng / 15.0;
        foreach ($t as $k => $v) {
            $t[$k] = $v + $offset;
        }

        if ($p->ishaMinutes !== null) {
            $t['isya'] = $t['magrib'] + $p->ishaMinutes / 60;
        }

        $ih = $p->ihtiyat / 60;
        $result = [
            'imsak' => $t['subuh'] + $ih - $p->imsakMinutes / 60,
            'subuh' => $t['subuh'] + $ih,
            'terbit' => $t['terbit'] - $ih,
            'duha' => $t['duha'] + $ih,
            'zuhur' => $t['zuhur'] + $ih,
            'asar' => $t['asar'] + $ih,
            'magrib' => $t['magrib'] + $ih,
            'isya' => $t['isya'] + $ih,
        ];

        foreach ($p->offsets as $k => $min) {
            if (isset($result[$k])) {
                $result[$k] += $min / 60;
            }
        }

        $out = [];
        foreach ($result as $k => $h) {
            // Terbit dibulatkan ke bawah, waktu lain ke atas (lebih hati-hati).
            $out[$k] = $this->format($h, ceil: $k !== 'terbit');
        }

        return $out;
    }

    /**
     * Jadwal satu bulan penuh.
     *
     * @return array<int, array{tanggal: CarbonImmutable, waktu: array<string, string>}>
     */
    public function forMonth(int $year, int $month, float $lat, float $lng, float $tz, ?PrayerParameters $p = null): array
    {
        $start = CarbonImmutable::create($year, $month, 1);
        $rows = [];
        for ($d = 0; $d < $start->daysInMonth; $d++) {
            $date = $start->addDays($d);
            $rows[] = ['tanggal' => $date, 'waktu' => $this->forDate($date, $lat, $lng, $tz, $p)];
        }

        return $rows;
    }

    /**
     * Tentukan sholat wajib berikutnya dari "sekarang" (waktu lokal lokasi).
     *
     * @return array{key: string, nama: string, waktu: string, timestamp: int}
     */
    public function next(CarbonImmutable $now, float $lat, float $lng, float $tz, ?PrayerParameters $p = null): array
    {
        $today = $this->forDate($now->startOfDay(), $lat, $lng, $tz, $p);
        foreach (self::OBLIGATORY as $key) {
            $at = $this->toDateTime($now, $today[$key], $tz);
            if ($at->greaterThan($now)) {
                return ['key' => $key, 'nama' => self::NAMES[$key], 'waktu' => $today[$key], 'timestamp' => $at->getTimestamp()];
            }
        }

        $tomorrow = $now->startOfDay()->addDay();
        $times = $this->forDate($tomorrow, $lat, $lng, $tz, $p);
        $at = $this->toDateTime($tomorrow, $times['subuh'], $tz);

        return ['key' => 'subuh', 'nama' => 'Subuh', 'waktu' => $times['subuh'], 'timestamp' => $at->getTimestamp()];
    }

    /** "Sekarang" pada zona waktu lokasi (berdasar offset jam). */
    public function nowAt(float $tz): CarbonImmutable
    {
        return CarbonImmutable::now($this->tzString($tz));
    }

    public function tzString(float $tz): string
    {
        $sign = $tz < 0 ? '-' : '+';
        $abs = abs($tz);

        return sprintf('%s%02d:%02d', $sign, (int) floor($abs), (int) round(($abs - floor($abs)) * 60));
    }

    public function tzLabel(float $tz): string
    {
        return match ((int) round($tz * 60)) {
            420 => 'WIB',
            480 => 'WITA',
            540 => 'WIT',
            default => 'UTC'.($tz >= 0 ? '+' : '').rtrim(rtrim(number_format($tz, 2), '0'), '.'),
        };
    }

    private function toDateTime(CarbonImmutable $day, string $hhmm, float $tz): CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return CarbonImmutable::create((int) $day->year, (int) $day->month, (int) $day->day, $h, $m, 0, $this->tzString($tz));
    }

    // ---------------------------------------------------------------------
    // Astronomi
    // ---------------------------------------------------------------------

    private function julianDay(int $y, int $m, int $d): float
    {
        if ($m <= 2) {
            $y--;
            $m += 12;
        }
        $a = floor($y / 100);
        $b = 2 - $a + floor($a / 4);

        return floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d + $b - 1524.5;
    }

    /** @return array{0: float, 1: float} [deklinasi, equation of time (jam)] */
    private function sunPosition(float $jd): array
    {
        $d = $jd - 2451545.0;
        $g = $this->fixAngle(357.529 + 0.98560028 * $d);
        $q = $this->fixAngle(280.459 + 0.98564736 * $d);
        $l = $this->fixAngle($q + 1.915 * $this->dsin($g) + 0.020 * $this->dsin(2 * $g));
        $e = 23.439 - 0.00000036 * $d;

        $ra = $this->darctan2($this->dcos($e) * $this->dsin($l), $this->dcos($l)) / 15;
        $eqt = $q / 15 - $this->fixHour($ra);
        $decl = $this->darcsin($this->dsin($e) * $this->dsin($l));

        return [$decl, $eqt];
    }

    private function midDay(float $jd, float $time): float
    {
        [, $eqt] = $this->sunPosition($jd + $time / 24);

        return $this->fixHour(12 - $eqt);
    }

    private function sunAngleTime(float $jd, float $angle, float $time, float $lat, bool $ccw): float
    {
        [$decl] = $this->sunPosition($jd + $time / 24);
        $noon = $this->midDay($jd, $time);
        $cos = (-$this->dsin($angle) - $this->dsin($decl) * $this->dsin($lat)) / ($this->dcos($decl) * $this->dcos($lat));
        $cos = max(-1.0, min(1.0, $cos));
        $t = $this->darccos($cos) / 15;

        return $noon + ($ccw ? -$t : $t);
    }

    private function asrTime(float $jd, float $factor, float $time, float $lat): float
    {
        [$decl] = $this->sunPosition($jd + $time / 24);
        $angle = -$this->darccot($factor + $this->dtan(abs($lat - $decl)));

        return $this->sunAngleTime($jd, $angle, $time, $lat, false);
    }

    private function format(float $hours, bool $ceil = false): string
    {
        $hours = $this->fixHour($hours + 0.5 / 3600); // hindari error pembulatan
        $minutes = $ceil ? ceil($hours * 60 - 1e-6) : floor($hours * 60);
        $minutes = ((int) $minutes) % (24 * 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function dsin(float $d): float { return sin(deg2rad($d)); }
    private function dcos(float $d): float { return cos(deg2rad($d)); }
    private function dtan(float $d): float { return tan(deg2rad($d)); }
    private function darcsin(float $x): float { return rad2deg(asin($x)); }
    private function darccos(float $x): float { return rad2deg(acos($x)); }
    private function darctan2(float $y, float $x): float { return rad2deg(atan2($y, $x)); }
    private function darccot(float $x): float { return rad2deg(atan(1 / $x)); }
    private function fixAngle(float $a): float { return $a - 360 * floor($a / 360); }
    private function fixHour(float $a): float { return $a - 24 * floor($a / 24); }
}
