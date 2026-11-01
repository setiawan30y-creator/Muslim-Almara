<?php

namespace App\Services\Worship;

use App\Models\User;
use App\Models\WorshipLog;
use Carbon\CarbonImmutable;

/**
 * Tracker ibadah harian: sholat fardhu (berjamaah / tepat waktu / qadha) + amalan sunnah.
 * Privat, tanpa papan peringkat. Hanya bisa diisi untuk hari ini s.d. 30 hari ke belakang.
 * @2026-Alifiandra
 */
class WorshipTracker
{
    public const PRAYERS = ['subuh' => 'Subuh', 'dzuhur' => 'Dzuhur', 'ashar' => 'Ashar', 'maghrib' => 'Maghrib', 'isya' => 'Isya'];

    public const PRAYER_STATES = ['jamaah' => 'Berjamaah', 'tepat' => 'Tepat waktu', 'qadha' => 'Qadha'];

    /** Amalan harian: kunci => [label, ikon] */
    public const HABITS = [
        'tilawah' => ['Tilawah Al-Qur\'an', 'book'],
        'dzikir_pagi' => ['Dzikir pagi', 'sun'],
        'dzikir_petang' => ['Dzikir petang', 'moon'],
        'dhuha' => ['Sholat Dhuha', 'sun'],
        'rawatib' => ['Sunnah rawatib', 'pray'],
        'tahajud' => ['Tahajud / qiyamul lail', 'moon'],
        'witir' => ['Witir', 'moon'],
        'puasa' => ['Puasa', 'calendar'],
        'sedekah' => ['Sedekah', 'heart'],
    ];

    public const BACK_DAYS = 30;

    /** Rentang tanggal yang boleh diisi. */
    public function editable(CarbonImmutable $date, string $tz): bool
    {
        $today = CarbonImmutable::now($tz)->startOfDay();

        return $date->lte($today) && $date->gte($today->subDays(self::BACK_DAYS));
    }

    /** @return array{prayers: array<string, ?string>, habits: array<string, bool>} */
    public function sanitize(array $input): array
    {
        $prayers = [];
        foreach (self::PRAYERS as $k => $_) {
            $v = $input['prayers'][$k] ?? null;
            $prayers[$k] = is_string($v) && isset(self::PRAYER_STATES[$v]) ? $v : null;
        }
        $habits = [];
        foreach (self::HABITS as $k => $_) {
            $habits[$k] = filter_var($input['habits'][$k] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return ['prayers' => $prayers, 'habits' => $habits];
    }

    public function empty(): array
    {
        return $this->sanitize([]);
    }

    /** Skor 0–100: sholat fardhu 70% (jamaah 14, tepat 12, qadha 4 per waktu), amalan 30%. */
    public function score(array $data): int
    {
        $w = ['jamaah' => 14, 'tepat' => 12, 'qadha' => 4];
        $s = 0;
        foreach ($data['prayers'] ?? [] as $v) {
            $s += $w[$v] ?? 0;
        }
        $done = count(array_filter($data['habits'] ?? []));

        return (int) min(100, round($s + 30 * $done / max(1, count(self::HABITS) - 3))); // 6 amalan sudah penuh
    }

    public function save(User $user, CarbonImmutable $date, array $input): WorshipLog
    {
        $data = $this->sanitize($input);

        $log = $user->worshipLogs()->whereDate('date', $date->toDateString())->first()
            ?? new WorshipLog(['user_id' => $user->id, 'date' => $date->toDateString()]);
        $log->fill(['data' => $data, 'score' => $this->score($data)])->save();

        return $log;
    }

    public function forDate(User $user, CarbonImmutable $date): array
    {
        $log = $user->worshipLogs()->whereDate('date', $date->toDateString())->first();

        return $log ? array_replace_recursive($this->empty(), $log->data) : $this->empty();
    }

    /** Ringkasan N hari terakhir + rangkaian hari (streak) 5 waktu lengkap. */
    public function summary(User $user, string $tz, int $days = 30): array
    {
        $today = CarbonImmutable::now($tz)->startOfDay();
        $logs = $user->worshipLogs()->whereDate('date', '>=', $today->subDays($days - 1)->toDateString())->get()
            ->keyBy(fn ($l) => $l->date->toDateString());

        $grid = [];
        $complete = 0;
        $jamaah = 0;
        $habitCount = array_fill_keys(array_keys(self::HABITS), 0);
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $today->subDays($i);
            $log = $logs->get($d->toDateString());
            $grid[] = ['date' => $d, 'score' => $log?->score];
            if ($log) {
                $p = array_filter($log->data['prayers'] ?? []);
                $complete += count($p) === 5 ? 1 : 0;
                $jamaah += count(array_filter($p, fn ($v) => $v === 'jamaah'));
                foreach ($log->data['habits'] ?? [] as $k => $v) {
                    if ($v && isset($habitCount[$k])) {
                        $habitCount[$k]++;
                    }
                }
            }
        }

        // Streak: hari berturut-turut (sampai hari ini/kemarin) dengan 5 waktu tercatat
        $streak = 0;
        $all = $user->worshipLogs()->whereDate('date', '<=', $today->toDateString())->orderByDesc('date')->limit(400)->get()
            ->keyBy(fn ($l) => $l->date->toDateString());
        $cursor = $all->has($today->toDateString()) ? $today : $today->subDay();
        while (($l = $all->get($cursor->toDateString())) && count(array_filter($l->data['prayers'] ?? [])) === 5) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return ['grid' => $grid, 'complete_days' => $complete, 'jamaah' => $jamaah, 'habits' => $habitCount, 'streak' => $streak, 'days' => $days];
    }
}
