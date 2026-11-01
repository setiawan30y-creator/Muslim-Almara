<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Langganan pengingat adzan per perangkat. @2026-Alifiandra
class PushSubscription extends Model
{
    public const PRAYERS = ['imsak' => 'Imsak', 'subuh' => 'Subuh', 'zuhur' => 'Zuhur', 'asar' => 'Asar', 'magrib' => 'Magrib', 'isya' => 'Isya'];

    protected $fillable = [
        'user_id', 'endpoint_hash', 'endpoint', 'p256dh', 'auth', 'lat', 'lng', 'tz', 'location_name', 'method', 'asr',
        'offsets_json', 'prayers_json', 'minutes_before', 'last_sent_key', 'failures', 'last_sent_at',
    ];

    protected $hidden = ['endpoint', 'p256dh', 'auth'];

    protected function casts(): array
    {
        return [
            'lat' => 'float', 'lng' => 'float', 'tz' => 'float', 'offsets_json' => 'array', 'prayers_json' => 'array',
            'minutes_before' => 'integer', 'failures' => 'integer', 'last_sent_at' => 'datetime',
        ];
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
