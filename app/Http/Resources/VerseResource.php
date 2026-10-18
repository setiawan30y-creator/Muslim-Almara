<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @2026-Alifiandra
class VerseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'surah' => $this->whenLoaded('surah', fn () => $this->surah->number, $this->surah_id),
            'ayah' => $this->ayah_number,
            'juz' => $this->juz_number,
            'page' => $this->page_number,
            'text_uthmani' => $this->text_uthmani,
            'translation' => $this->whenLoaded('translationId', fn () => $this->translationId ? [
                'language' => 'id', 'translator' => $this->translationId->translator, 'text' => $this->translationId->text,
                'source_id' => $this->translationId->source_id,
            ] : null),
            'tafsir' => $this->when($request->boolean('tafsir') && $this->relationLoaded('tafsirId') && $this->tafsirId, fn () => [
                'work' => $this->tafsirId->work_name, 'text' => $this->tafsirId->text,
            ]),
            'source_version' => $this->version,
        ];
    }
}
