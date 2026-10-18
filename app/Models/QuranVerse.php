<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// @2026-Alifiandra
class QuranVerse extends Model
{
    protected $fillable = ['surah_id', 'ayah_number', 'juz_number', 'page_number', 'text_uthmani', 'source_id', 'version'];

    protected function casts(): array
    {
        return ['ayah_number' => 'integer', 'juz_number' => 'integer', 'page_number' => 'integer'];
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuranTranslation::class, 'verse_id');
    }

    public function translationId(): HasOne
    {
        return $this->hasOne(QuranTranslation::class, 'verse_id')->where('language', 'id');
    }

    public function tafsirId(): HasOne
    {
        return $this->hasOne(QuranTafsir::class, 'verse_id')->where('language', 'id');
    }

    public function reference(): string
    {
        return 'QS. '.$this->surah->name_latin.' ('.$this->surah->number.'): '.$this->ayah_number;
    }
}
