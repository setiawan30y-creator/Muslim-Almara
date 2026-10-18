<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @2026-Alifiandra
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_user_id', 'action', 'subject_type', 'subject_id', 'metadata_json', 'ip_hash'];

    protected function casts(): array
    {
        return ['metadata_json' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
