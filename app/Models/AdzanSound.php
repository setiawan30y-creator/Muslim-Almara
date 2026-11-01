<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Rekaman suara adzan — @2026-Alifiandra
class AdzanSound extends Model
{
    public const KINDS = ['regular' => 'Adzan (Dzuhur–Isya)', 'subuh' => 'Adzan Subuh (dengan “ash-shalātu khairun minan-naum”)'];

    public const STATUSES = ['draft' => 'Draf', 'published' => 'Aktif', 'inactive' => 'Nonaktif'];

    protected $fillable = ['name', 'kind', 'disk', 'path', 'mime', 'size_bytes', 'duration_seconds', 'checksum', 'license_note', 'status', 'created_by', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'size_bytes' => 'integer', 'duration_seconds' => 'integer'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function key(): string
    {
        return 'almara:'.$this->id;
    }
}
