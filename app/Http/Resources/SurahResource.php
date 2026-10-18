<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @2026-Alifiandra
class SurahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'name_ar' => $this->name_ar,
            'name_latin' => $this->name_latin,
            'name_translation' => $this->name_translation,
            'revelation_type' => $this->revelation_type,
            'ayah_count' => $this->ayah_count,
        ];
    }
}
