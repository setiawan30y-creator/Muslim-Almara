<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;

/**
 * Konversi kalender Masehi <-> Hijriah dengan metode aritmetika (tabular/urfi).
 *
 * Penanggalan resmi (awal Ramadan, Syawal, Zulhijah) ditetapkan melalui rukyat/hisab
 * oleh otoritas terkait, sehingga hasil di sini dapat berbeda ±1 hari. Selisih tersebut
 * bisa dikoreksi melalui HIJRI_ADJUSTMENT di .env.
 *
 * @2026-Alifiandra
 */
class HijriCalendar
{
    public const MONTHS = [
        1 => 'Muharam', 'Safar', 'Rabiulawal', 'Rabiulakhir', 'Jumadilawal', 'Jumadilakhir',
        'Rajab', 'Syakban', 'Ramadan', 'Syawal', 'Zulkaidah', 'Zulhijah',
    ];

    public const MONTHS_SHORT = [
        1 => 'Muh', 'Saf', 'R.Awal', 'R.Akhir', 'J.Awal', 'J.Akhir', 'Rajab', 'Syakb', 'Ram', 'Syaw', 'Zulk', 'Zulh',
    ];

    public const MONTHS_AR = [
        1 => 'مُحَرَّم', 'صَفَر', 'رَبِيْع الْأَوَّل', 'رَبِيْع الْآخِر', 'جُمَادَى الْأُوْلَى', 'جُمَادَى الْآخِرَة',
        'رَجَب', 'شَعْبَان', 'رَمَضَان', 'شَوَّال', 'ذُو الْقَعْدَة', 'ذُو الْحِجَّة',
    ];

    /** Hari besar Islam: [bulan, tanggal, nama, keterangan]. */
    public const EVENTS = [
        [1, 1, 'Tahun Baru Islam', '1 Muharam'],
        [1, 10, 'Hari Asyura', '10 Muharam'],
        [3, 12, 'Maulid Nabi Muhammad ﷺ', '12 Rabiulawal'],
        [7, 27, "Isra Mi'raj", '27 Rajab'],
        [9, 1, 'Awal Ramadan', '1 Ramadan'],
        [9, 17, "Nuzulul Qur'an", '17 Ramadan'],
        [10, 1, 'Idulfitri', '1 Syawal'],
        [12, 9, 'Hari Arafah', '9 Zulhijah'],
        [12, 10, 'Iduladha', '10 Zulhijah'],
    ];

    public function __construct(private int $adjustment = 0)
    {
    }

    /** @return array{day: int, month: int, year: int, month_name: string, month_ar: string, label: string} */
    public function fromGregorian(CarbonImmutable $date): array
    {
        $jd = $this->gregorianToJd((int) $date->year, (int) $date->month, (int) $date->day) + $this->adjustment;
        [$y, $m, $d] = $this->jdToHijri($jd);

        return [
            'day' => $d,
            'month' => $m,
            'year' => $y,
            'month_name' => self::MONTHS[$m],
            'month_ar' => self::MONTHS_AR[$m],
            'month_short' => self::MONTHS_SHORT[$m],
            'label' => sprintf('%d %s %d H', $d, self::MONTHS[$m], $y),
        ];
    }

    public function toGregorian(int $year, int $month, int $day): CarbonImmutable
    {
        $jd = $this->hijriToJd($year, $month, $day) - $this->adjustment;
        [$y, $m, $d] = $this->jdToGregorian($jd);

        return CarbonImmutable::create($y, $m, $d)->startOfDay();
    }

    /** Jumlah hari dalam bulan Hijriah (29/30). */
    public function daysInMonth(int $year, int $month): int
    {
        $nextY = $month === 12 ? $year + 1 : $year;
        $nextM = $month === 12 ? 1 : $month + 1;

        return $this->hijriToJd($nextY, $nextM, 1) - $this->hijriToJd($year, $month, 1);
    }

    /**
     * Hari besar Islam yang akan datang mulai dari tanggal tertentu.
     *
     * @return array<int, array{nama: string, hijri: string, tanggal: CarbonImmutable, sisa_hari: int}>
     */
    public function upcomingEvents(CarbonImmutable $from, int $limit = 5): array
    {
        $today = $from->startOfDay();
        $h = $this->fromGregorian($today);
        $items = [];
        foreach ([$h['year'], $h['year'] + 1] as $year) {
            foreach (self::EVENTS as [$m, $d, $name, $desc]) {
                $g = $this->toGregorian($year, $m, $d);
                if ($g->lessThan($today)) {
                    continue;
                }
                $items[] = [
                    'nama' => $name,
                    'hijri' => "{$desc} {$year} H",
                    'tanggal' => $g,
                    'sisa_hari' => (int) $today->diffInDays($g),
                ];
            }
        }
        usort($items, fn ($a, $b) => $a['tanggal'] <=> $b['tanggal']);

        return array_slice($items, 0, $limit);
    }

    // ---------------------------------------------------------------------

    private function gregorianToJd(int $y, int $m, int $d): int
    {
        return intdiv(1461 * ($y + 4800 + intdiv($m - 14, 12)), 4)
            + intdiv(367 * ($m - 2 - 12 * intdiv($m - 14, 12)), 12)
            - intdiv(3 * intdiv($y + 4900 + intdiv($m - 14, 12), 100), 4)
            + $d - 32075;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function jdToGregorian(int $jd): array
    {
        $l = $jd + 68569;
        $n = intdiv(4 * $l, 146097);
        $l = $l - intdiv(146097 * $n + 3, 4);
        $i = intdiv(4000 * ($l + 1), 1461001);
        $l = $l - intdiv(1461 * $i, 4) + 31;
        $j = intdiv(80 * $l, 2447);
        $d = $l - intdiv(2447 * $j, 80);
        $l = intdiv($j, 11);
        $m = $j + 2 - 12 * $l;
        $y = 100 * ($n - 49) + $i + $l;

        return [$y, $m, $d];
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function jdToHijri(int $jd): array
    {
        $l = $jd - 1948440 + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - 10631 * $n + 354;
        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
        $l = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
        $m = intdiv(24 * $l, 709);
        $d = $l - intdiv(709 * $m, 24);
        $y = 30 * $n + $j - 30;

        return [$y, $m, $d];
    }

    private function hijriToJd(int $y, int $m, int $d): int
    {
        return intdiv(11 * $y + 3, 30) + 354 * $y + 30 * $m - intdiv($m - 1, 2) + $d + 1948440 - 385;
    }
}
