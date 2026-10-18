<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @2026-Alifiandra
class DuaCategory extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(DuaItem::class, 'category_id')->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
