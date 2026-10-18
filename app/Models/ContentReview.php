<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Jejak review konten. @2026-Alifiandra
class ContentReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['content_type', 'content_id', 'content_version', 'reviewer_id', 'decision', 'notes'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
