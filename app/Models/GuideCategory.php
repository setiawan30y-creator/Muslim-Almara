<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Kategori panduan (Tajwid, Umroh, Haji, Ziarah). @2026-Alifiandra
class GuideCategory extends Model
{
    public const MODULES = [
        'tajwid' => ['Belajar Tajwid', 'Kurikulum bertingkat dengan contoh ayat dan latihan', 'learn'],
        'umroh' => ['Panduan Umroh', 'Rukun, wajib, dan langkah umroh beserta bacaannya', 'kaaba'],
        'haji' => ['Panduan Haji', 'Jenis haji, rukun & wajib, serta urutan hari-hari haji', 'kaaba'],
        'ziarah' => ['Panduan Ziarah Kubur', 'Tujuan, adab, salam, dan doa bersumber', 'map'],
    ];

    protected $fillable = ['module', 'slug', 'title', 'description', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(GuideItem::class, 'category_id')->orderBy('sort_order');
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->module][0] ?? ucfirst($this->module);
    }
}
