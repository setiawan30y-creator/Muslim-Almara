<?php

namespace App\Models;

use App\Models\Concerns\HasReviewWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @2026-Alifiandra
class DuaItem extends Model
{
    use HasReviewWorkflow;

    public const CONTENT_TYPE = 'dua';

    protected $fillable = [
        'category_id', 'slug', 'title', 'arabic_text', 'transliteration', 'translation', 'translation_status',
        'count_target', 'context_note', 'source_reference', 'source_kind', 'source_id', 'review_status',
        'content_version', 'created_by', 'submitted_by', 'reviewed_by', 'reviewed_at', 'published_at', 'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DuaCategory::class, 'category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
