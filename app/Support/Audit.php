<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencatat audit (blueprint §10.3). Tidak pernah menyimpan password/token/isi catatan.
 * @2026-Alifiandra
 */
class Audit
{
    private const REDACT = ['password', 'token', 'body', 'remember_token', 'secret'];

    /** @param array<string, mixed> $meta */
    public static function log(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null, bool $useAuth = true): AuditLog
    {
        foreach (self::REDACT as $k) {
            unset($meta[$k]);
        }
        $actor ??= $useAuth ? auth()->user() : null;
        $ip = request()?->ip();

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'metadata_json' => $meta ?: null,
            'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
        ]);
    }
}
