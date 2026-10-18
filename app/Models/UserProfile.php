<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserProfile extends Model
{
    
    protected $fillable = ['user_id', 'locale', 'timezone', 'dashboard_cards_json', 'track_reading'];

    protected function casts(): array
    {
        return ['dashboard_cards_json' => 'array', 'track_reading' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
