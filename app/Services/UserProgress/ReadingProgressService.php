<?php

namespace App\Services\UserProgress;

use App\Models\QuranVerse;
use App\Models\User;
use App\Models\UserReadingProgress;
use App\Services\Quran\QuranDataset;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Progres tilawah pribadi (blueprint §4.8): terakhir dibaca, ayat yang ditandai dibaca,
 * target harian, dan persentase khatam. Privat; dapat dimatikan & dihapus pengguna.
 * @2026-Alifiandra
 */
class ReadingProgressService
{
    public function setLastRead(User $user, QuranVerse $verse): void
    {
        $user->lastRead()->updateOrCreate([], ['verse_id' => $verse->id]);
    }

    /**
     * Tandai ayat dibaca dari $fromAyah s/d ayat $verse (dalam satu surah).
     * Mengembalikan jumlah ayat yang tercatat.
     */
    public function markRead(User $user, QuranVerse $verse, ?int $fromAyah = null): int
    {
        if ($user->profile && ! $user->profile->track_reading) {
            $this->setLastRead($user, $verse);

            return 0;
        }
        $from = max(1, min($fromAyah ?? $verse->ayah_number, $verse->ayah_number));
        $ids = QuranVerse::query()->where('surah_id', $verse->surah_id)
            ->whereBetween('ayah_number', [$from, $verse->ayah_number])->pluck('id');

        $now = now();
        DB::transaction(function () use ($user, $ids, $now) {
            foreach ($ids->chunk(200) as $chunk) {
                UserReadingProgress::query()->upsert(
                    $chunk->map(fn ($id) => ['user_id' => $user->id, 'verse_id' => $id, 'last_read_at' => $now])->all(),
                    ['user_id', 'verse_id'],
                    ['last_read_at']
                );
            }
        });
        $this->setLastRead($user, $verse);

        return $ids->count();
    }

    /** @return array{today: int, total: int, khatam_percent: float, target: ?int, target_percent: ?float, streak: int} */
    public function stats(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $today = $user->readingProgress()->where('last_read_at', '>=', $now->startOfDay())->count();
        $total = $user->readingProgress()->count();
        $goal = $user->activeReadingGoal;

        // Rangkaian hari berturut-turut dengan aktivitas baca (maks. 60 hari ke belakang)
        $days = $user->readingProgress()->where('last_read_at', '>=', $now->subDays(60)->startOfDay())
            ->pluck('last_read_at')->map(fn ($d) => $d->format('Y-m-d'))->unique()->flip();
        $streak = 0;
        for ($d = $now; isset($days[$d->format('Y-m-d')]); $d = $d->subDay()) {
            $streak++;
        }

        return [
            'today' => $today,
            'total' => $total,
            'khatam_percent' => round($total / QuranDataset::VERSE_COUNT * 100, 1),
            'target' => $goal?->daily_target,
            'target_percent' => $goal ? min(100, round($today / max(1, $goal->daily_target) * 100)) : null,
            'streak' => $streak,
        ];
    }

    public function reset(User $user): void
    {
        $user->readingProgress()->delete();
        $user->lastRead()->delete();
    }
}
