<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserAppearancePreference extends Model
{
    
    protected $fillable = ['user_id', 'theme_mode', 'accent_color', 'interface_text_size', 'reading_text_size', 'translation_text_size'];

    protected function casts(): array
    {
        return ['theme_mode' => \App\Enums\ThemeMode::class, 'accent_color' => \App\Enums\AccentColor::class, 'interface_text_size' => \App\Enums\TextSize::class, 'reading_text_size' => \App\Enums\TextSize::class, 'translation_text_size' => \App\Enums\TextSize::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
