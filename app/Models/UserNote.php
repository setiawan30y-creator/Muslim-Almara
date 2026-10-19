<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Data pribadi — hanya boleh diakses pemiliknya. @2026-Alifiandra
class UserNote extends Model
{
    
    protected $fillable = ['user_id', 'verse_id', 'body', 'tags'];

    /** "Sabar, Syukur ,doa" → "sabar,syukur,doa" (maks. 5 tag, huruf/angka/spasi/tanda hubung). */
    public static function normalizeTags(?string $raw): ?string
    {
        $tags = collect(explode(',', strip_tags((string) $raw)))
            ->map(fn ($t) => trim(preg_replace('/[^\p{L}\p{N} \-]/u', '', mb_strtolower(trim(strip_tags($t))))))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) <= 30)->unique()->take(5)->values();

        return $tags->isEmpty() ? null : $tags->implode(',');
    }

    /** @return array<int, string> */
    public function tagList(): array
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'verse_id');
    }
}
