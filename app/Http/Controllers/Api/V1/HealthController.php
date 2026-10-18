<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\DB;

// Status layanan tanpa rahasia. @2026-Alifiandra
class HealthController extends ApiController
{
    public function __invoke()
    {
        try {
            DB::connection()->getPdo();
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'unavailable';
        }

        return $this->ok(['status' => $db === 'ok' ? 'ok' : 'degraded', 'database' => $db, 'version' => config('almara.version'), 'time' => now()->toIso8601String()],
            [], $db === 'ok' ? 200 : 503);
    }
}
