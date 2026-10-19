<?php

namespace App\Models;

use App\Models\Concerns\HasReviewWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Materi panduan bersumber — wajib review sebelum terbit. @2026-Alifiandra
class GuideItem extends Model
{
    use HasReviewWorkflow;

    public const CONTENT_TYPE = 'guide';

    protected $fillable = [
        'category_id', 'slug', 'title', 'body', 'arabic_text', 'transliteration', 'translation', 'translation_status',
        'count_target', 'context_note', 'example_ref', 'quiz_json', 'source_reference', 'source_kind', 'source_id',
        'review_status', 'content_version', 'created_by', 'submitted_by', 'reviewed_by', 'reviewed_at', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['quiz_json' => 'array'];
    }

    public function sourceKindLabel(): string
    {
        return match ($this->source_kind) {
            'quran' => "Berdasarkan Al-Qur'an",
            'hadith' => 'Berdasarkan hadis',
            default => 'Rujukan kitab/fikih',
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GuideCategory::class, 'category_id');
    }

    /** @return array{0: int, 1: int}|null */
    public function exampleRef(): ?array
    {
        if (! $this->example_ref || ! preg_match('/^(\d{1,3}):(\d{1,3})$/', $this->example_ref, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    /** Badan teks → blok paragraf & daftar (tanpa HTML mentah). @return array<int, array{type: string, lines: array<int, string>}> */
    public function bodyBlocks(): array
    {
        $blocks = [];
        foreach (preg_split('/\R{2,}/u', trim((string) $this->body)) as $chunk) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $chunk))));
            if (! $lines) {
                continue;
            }
            $isList = collect($lines)->every(fn ($l) => str_starts_with($l, '- '));
            $blocks[] = $isList
                ? ['type' => 'list', 'lines' => array_map(fn ($l) => substr($l, 2), $lines)]
                : ['type' => 'p', 'lines' => $lines];
        }

        return $blocks;
    }
}
