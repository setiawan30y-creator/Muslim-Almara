<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @2026-Alifiandra
class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'verse_id' => $this->verse_id,
            'surah' => $this->verse?->surah?->number,
            'ayah' => $this->verse?->ayah_number,
            'body' => $this->body,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
