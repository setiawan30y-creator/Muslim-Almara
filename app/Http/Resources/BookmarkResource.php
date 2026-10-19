<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @2026-Alifiandra
class BookmarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'verse_id' => $this->verse_id,
            'surah' => $this->verse?->surah?->number,
            'ayah' => $this->verse?->ayah_number,
            'label' => $this->label,
            'collection_id' => $this->collection_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
