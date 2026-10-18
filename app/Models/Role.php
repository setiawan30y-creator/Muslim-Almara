<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// @2026-Alifiandra
class Role extends Model
{
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';
    public const REVIEWER = 'reviewer';

    protected $fillable = ['name', 'label', 'scope'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('assigned_at');
    }
}
