<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @2026-Alifiandra
class QuranSurah extends Model
{
    protected $fillable = ['number', 'name_ar', 'name_latin', 'name_translation', 'revelation_type', 'ayah_count', 'source_id'];

    protected function casts(): array
    {
        return ['number' => 'integer', 'ayah_count' => 'integer'];
    }

    public function verses(): HasMany
    {
        return $this->hasMany(QuranVerse::class, 'surah_id')->orderBy('ayah_number');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'source_id');
    }

    public function revelationLabel(): string
    {
        return $this->revelation_type === 'madaniyah' ? 'Madaniyah' : 'Makkiyah';
    }
}
