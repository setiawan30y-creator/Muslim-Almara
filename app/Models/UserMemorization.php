<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Status hafalan per ayat (data pribadi — hanya pemilik). @2026-Alifiandra
class UserMemorization extends Model
{
    protected $fillable = ['user_id', 'verse_id', 'status', 'interval_days', 'ease', 'next_review_on', 'reviews', 'lapses', 'last_grade', 'last_reviewed_at'];

    protected function casts(): array
    {
        return [
            'interval_days' => 'integer', 'ease' => 'float', 'next_review_on' => 'date', 'reviews' => 'integer',
            'lapses' => 'integer', 'last_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class);
    }

    public function isDue(): bool
    {
        return $this->next_review_on !== null && $this->next_review_on->lte(today());
    }
}
