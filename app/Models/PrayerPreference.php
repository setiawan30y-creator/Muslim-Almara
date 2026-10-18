<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class PrayerPreference extends Model
{
    
    protected $fillable = ['user_id', 'method_id', 'asr_method', 'offsets_json'];

    protected function casts(): array
    {
        return ['offsets_json' => 'array', 'asr_method' => \App\Enums\AsrMethod::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PrayerMethod::class, 'method_id');
    }
}
