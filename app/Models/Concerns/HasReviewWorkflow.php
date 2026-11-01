<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Models\ContentSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konten agama yang wajib melewati review sebelum terbit (blueprint §5.3).
 * @2026-Alifiandra
 */
trait HasReviewWorkflow
{
    public function initializeHasReviewWorkflow(): void
    {
        $this->mergeCasts([
            'review_status' => ContentStatus::class,
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'count_target' => 'integer',
            'content_version' => 'integer',
        ]);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('review_status', ContentStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->review_status === ContentStatus::Published;
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Rujukan audio ayat ("2:201", "112:1-4") → [surah, dari, sampai] atau null. */
    public function audioRange(): ?array
    {
        $ref = $this->audio_ref ?? null;
        if (! $ref || ! preg_match('/^(\d{1,3}):(\d{1,3})(?:-(\d{1,3}))?$/', $ref, $m)) {
            return null;
        }
        $s = (int) $m[1];
        $from = (int) $m[2];
        $to = isset($m[3]) ? (int) $m[3] : $from;
        if ($s < 1 || $s > 114 || $from < 1 || $to < $from || $to > \App\Services\Quran\QuranDataset::AYAH_COUNTS[$s - 1]) {
            return null;
        }

        return [$s, $from, $to];
    }

    /** Label jenis sumber untuk ditampilkan ke pengguna. */
    public function sourceKindLabel(): string
    {
        return match ($this->source_kind) {
            'quran' => "Ayat Al-Qur'an",
            'hadith' => 'Bacaan bersumber hadis',
            default => 'Doa umum',
        };
    }
}
