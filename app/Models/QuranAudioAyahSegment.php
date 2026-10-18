<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Penanda waktu ayat dalam rekaman surah. @2026-Alifiandra
class QuranAudioAyahSegment extends Model
{
    public $timestamps = false;

    protected $fillable = ['track_id', 'ayah_number', 'start_ms', 'end_ms'];

    protected function casts(): array
    {
        return ['ayah_number' => 'integer', 'start_ms' => 'integer', 'end_ms' => 'integer'];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(QuranAudioTrack::class, 'track_id');
    }
}
