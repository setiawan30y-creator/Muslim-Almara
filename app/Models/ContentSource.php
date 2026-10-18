<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Asal-usul data (blueprint §5.2). @2026-Alifiandra
class ContentSource extends Model
{
    protected $fillable = ['key', 'name', 'content_type', 'url', 'documentation_url', 'license', 'attribution',
        'version', 'checksum', 'retrieved_at', 'reviewed_at', 'validation_notes'];

    protected function casts(): array
    {
        return ['retrieved_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
