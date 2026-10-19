<?php

namespace App\Services\Memorization;

use App\Models\QuranVerse;
use App\Models\User;
use App\Models\UserMemorization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hafalan & Murojaah dengan pengulangan berjarak (spaced repetition) sederhana:
 *   lupa   → ulang besok, kemudahan turun
 *   ragu   → jarak sedikit bertambah
 *   lancar → jarak bertambah (1 → 3 → ×kemudahan), maksimal 180 hari
 * Ayat berstatus "hafal" bila dinilai lancar dengan jarak ≥ 7 hari.
 * @2026-Alifiandra
 */
class MemorizationService
{
    public const GRADES = ['lupa' => 'Lupa', 'ragu' => 'Ragu', 'lancar' => 'Lancar'];
    public const MAX_INTERVAL = 180;
    public const MEMORIZED_AT = 7;

    public function grade(User $user, QuranVerse $verse, string $grade): UserMemorization
    {
        abort_unless(isset(self::GRADES[$grade]), 422);

        return DB::transaction(function () use ($user, $verse, $grade) {
            $m = UserMemorization::query()->lockForUpdate()->firstOrNew(['user_id' => $user->id, 'verse_id' => $verse->id]);
            $interval = (int) ($m->interval_days ?? 0);
            $ease = (float) ($m->ease ?? 2.5);

            switch ($grade) {
                case 'lupa':
                    $m->lapses = (int) $m->lapses + 1;
                    $interval = 1;
                    $ease = max(1.3, $ease - 0.2);
                    break;
                case 'ragu':
                    $interval = $interval < 1 ? 1 : max(1, (int) round($interval * 1.2));
                    $ease = max(1.3, $ease - 0.15);
                    break;
                case 'lancar':
                    $interval = match (true) {
                        $interval < 1 => 1,
                        $interval < 3 => 3,
                        default => (int) round($interval * $ease),
                    };
                    $ease = min(3.0, $ease + 0.05);
                    break;
            }
            $interval = min(self::MAX_INTERVAL, $interval);
            $wasMemorized = $m->status === 'memorized';

            $m->fill([
                'interval_days' => $interval,
                'ease' => round($ease, 2),
                'next_review_on' => today()->addDays($interval),
                'reviews' => (int) $m->reviews + 1,
                'last_grade' => $grade,
                'last_reviewed_at' => now(),
                'status' => ($grade === 'lancar' && $interval >= self::MEMORIZED_AT) || ($wasMemorized && $grade !== 'lupa') ? 'memorized' : 'learning',
            ])->save();

            return $m;
        });
    }

    /** @return array{memorized: int, learning: int, due: int, verses_total: int} */
    public function stats(User $user): array
    {
        $row = $user->memorizations()->selectRaw("sum(case when status = 'memorized' then 1 else 0 end) m, sum(case when status = 'learning' then 1 else 0 end) l, sum(case when next_review_on <= ? then 1 else 0 end) d", [today()->endOfDay()->toDateTimeString()])->first();

        return ['memorized' => (int) $row?->m, 'learning' => (int) $row?->l, 'due' => (int) $row?->d, 'verses_total' => 6236];
    }

    /** Ringkasan per surah: [surah_number => {name, ayah_count, memorized, learning, due}] */
    public function perSurah(User $user): Collection
    {
        return $user->memorizations()
            ->join('quran_verses', 'quran_verses.id', '=', 'user_memorizations.verse_id')
            ->join('quran_surahs', 'quran_surahs.id', '=', 'quran_verses.surah_id')
            ->groupBy('quran_surahs.number', 'quran_surahs.name_latin', 'quran_surahs.ayah_count')
            ->orderBy('quran_surahs.number')
            ->selectRaw("quran_surahs.number, quran_surahs.name_latin, quran_surahs.ayah_count,
                sum(case when user_memorizations.status = 'memorized' then 1 else 0 end) as memorized,
                sum(case when user_memorizations.status = 'learning' then 1 else 0 end) as learning,
                sum(case when user_memorizations.next_review_on <= ? then 1 else 0 end) as due", [today()->endOfDay()->toDateTimeString()])
            ->get();
    }

    /** @return array<int, UserMemorization> keyed by verse_id */
    public function forVerses(User $user, Collection $verseIds): array
    {
        return $user->memorizations()->whereIn('verse_id', $verseIds)->get()->keyBy('verse_id')->all();
    }
}
