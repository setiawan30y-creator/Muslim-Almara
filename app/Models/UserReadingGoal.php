<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserReadingGoal extends Model
{
    
    protected $fillable = ['user_id', 'daily_target', 'start_date', 'khatam_target_date', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'khatam_target_date' => 'date', 'daily_target' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
