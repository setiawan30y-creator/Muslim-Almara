<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class QuranReadingPreference extends Model
{
    
    protected $fillable = ['user_id', 'reading_mode', 'show_translation', 'show_transliteration', 'audio_mode', 'reciter', 'show_tafsir'];

    protected function casts(): array
    {
        return ['reading_mode' => \App\Enums\QuranReadingMode::class, 'show_translation' => 'boolean', 'show_transliteration' => 'boolean', 'show_tafsir' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
