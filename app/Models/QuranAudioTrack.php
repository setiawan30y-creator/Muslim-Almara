<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Rekaman surah — berversi & wajib review sebelum terbit. @2026-Alifiandra
class QuranAudioTrack extends Model
{
    protected $fillable = [
        'reciter_id', 'surah_number', 'version', 'disk', 'path', 'original_name', 'mime', 'size_bytes',
        'duration_seconds', 'checksum', 'review_status', 'notes', 'created_by', 'submitted_by', 'reviewed_by',
        'reviewed_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'review_status' => ContentStatus::class,
            'surah_number' => 'integer',
            'version' => 'integer',
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('review_status', ContentStatus::Published->value);
    }

    public function reciter(): BelongsTo
    {
        return $this->belongsTo(QuranReciter::class, 'reciter_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(QuranAudioAyahSegment::class, 'track_id')->orderBy('ayah_number');
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

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_number', 'number');
    }

    public function sizeLabel(): string
    {
        return number_format($this->size_bytes / 1048576, 1, ',', '.').' MB';
    }

    public function durationLabel(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }
        $s = $this->duration_seconds;

        return ($s >= 3600 ? intdiv($s, 3600).':' : '').sprintf($s >= 3600 ? '%02d:%02d' : '%d:%02d', intdiv($s % 3600, 60), $s % 60);
    }
}
