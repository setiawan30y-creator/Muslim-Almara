<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @2026-Alifiandra
class QuranTafsir extends Model
{
    protected $fillable = ['verse_id', 'work_name', 'author', 'language', 'text', 'source_id'];

    public function verse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'verse_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'source_id');
    }
}
