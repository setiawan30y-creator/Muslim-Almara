<?php

namespace App\Services\Account;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Ekspor & penghapusan data pribadi (blueprint §6.3, §7.3 data-export / delete-account).
 * @2026-Alifiandra
 */
class PersonalDataService
{
    /** @return array<string, mixed> */
    public function export(User $user): array
    {
        $user->loadMissing(['profile', 'appearance', 'quranPreference', 'prayerPreference.method', 'lastRead.verse.surah']);
        $verseRef = fn ($v) => $v ? ['surah' => $v->surah->number, 'ayah' => $v->ayah_number] : null;

        return [
            'exported_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'account' => ['name' => $user->name, 'email' => $user->email, 'created_at' => $user->created_at?->toIso8601String()],
            'preferences' => [
                'profile' => $user->profile?->only(['locale', 'timezone', 'dashboard_cards_json', 'track_reading']),
                'appearance' => $user->appearance?->toArray(),
                'quran' => $user->quranPreference?->toArray(),
                'prayer' => $user->prayerPreference ? [
                    'method' => $user->prayerPreference->method?->key,
                    'asr_method' => $user->prayerPreference->asr_method?->value,
                    'offsets' => $user->prayerPreference->offsets_json,
                ] : null,
            ],
            'last_read' => $verseRef($user->lastRead?->verse),
            'bookmarks' => $user->bookmarks()->with('verse.surah')->get()->map(fn ($b) => $verseRef($b->verse) + ['label' => $b->label, 'collection_id' => $b->collection_id, 'created_at' => $b->created_at?->toIso8601String()])->all(),
            'notes' => $user->notes()->with('verse.surah')->get()->map(fn ($n) => ['verse' => $verseRef($n->verse), 'body' => $n->body, 'tags' => $n->tags, 'updated_at' => $n->updated_at?->toIso8601String()])->all(),
            'reading_goals' => $user->readingGoals()->get(['daily_target', 'start_date', 'khatam_target_date', 'status'])->toArray(),
            'memorization' => $user->memorizations()->with('verse.surah')->get()->map(fn ($m) => $verseRef($m->verse) + [
                'status' => $m->status, 'interval_days' => $m->interval_days, 'next_review_on' => $m->next_review_on?->toDateString(),
                'reviews' => $m->reviews, 'lapses' => $m->lapses,
            ])->all(),
            'collections' => $user->collections()->get(['id', 'name', 'description'])->toArray(),
            'reading_progress' => $user->readingProgress()->with('verse.surah')->get()->map(fn ($p) => $verseRef($p->verse) + ['last_read_at' => $p->last_read_at?->toIso8601String()])->all(),
        ];
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            Audit::log('account.deleted', null, ['user_id' => $user->id], actor: null, useAuth: false);
            $user->tokens()->delete();
            $user->roles()->detach();
            $user->delete(); // data pribadi terhapus lewat cascade; log audit tetap (actor di-null-kan)
        });
    }
}
