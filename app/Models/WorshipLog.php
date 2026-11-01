<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Catatan ibadah harian — @2026-Alifiandra
class WorshipLog extends Model
{
    protected $fillable = ['user_id', 'date', 'data', 'score'];

    protected function casts(): array
    {
        return ['date' => 'date', 'data' => 'array', 'score' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
