<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserReadingProgress extends Model
{
    protected $table = 'user_reading_progress';
    public $timestamps = false;

    protected $fillable = ['user_id', 'verse_id', 'last_read_at'];

    protected function casts(): array
    {
        return ['last_read_at' => 'datetime'];
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
