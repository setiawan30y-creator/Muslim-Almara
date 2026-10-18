<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Doa/dzikir terbit — lengkap dengan Latin, arti, sumber, versi (Standar Wajib Bacaan). @2026-Alifiandra
class WorshipItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', fn () => $this->category->slug),
            'title' => $this->title,
            'arabic_text' => $this->arabic_text,
            'transliteration' => $this->transliteration,
            'translation' => ['language' => 'id', 'text' => $this->translation, 'status' => $this->translation_status],
            'count_target' => $this->count_target,
            'context_note' => $this->context_note,
            'source' => ['reference' => $this->source_reference, 'kind' => $this->source_kind, 'source_id' => $this->source_id],
            'review_status' => $this->review_status->value,
            'content_version' => $this->content_version,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
