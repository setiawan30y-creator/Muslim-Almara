<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserBookmark extends Model
{
    
    protected $fillable = ['user_id', 'verse_id', 'label'];

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'verse_id');
    }
}
