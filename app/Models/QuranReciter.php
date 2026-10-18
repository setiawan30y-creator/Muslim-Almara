<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Qari milik platform (rekaman sendiri, blueprint §4.12). @2026-Alifiandra
class QuranReciter extends Model
{
    protected $fillable = ['slug', 'name', 'style', 'license_note', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(QuranAudioTrack::class, 'reciter_id');
    }

    /** Kunci gabungan yang dipakai pemutar & preferensi: "almara:{slug}". */
    public function key(): string
    {
        return 'almara:'.$this->slug;
    }
}
