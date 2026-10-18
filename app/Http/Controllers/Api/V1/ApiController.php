<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Basis API v1. Format respons (blueprint §7.1):
 *   berhasil → {"data": ..., "meta": {...}}
 *   gagal    → {"message": "...", "errors": {...}}
 * @2026-Alifiandra
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json(['data' => $data, 'meta' => (object) $meta], $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param class-string<\Illuminate\Http\Resources\Json\JsonResource> $resource */
    protected function paginated(LengthAwarePaginator $p, string $resource, array $meta = []): JsonResponse
    {
        return $this->ok($resource::collection($p->getCollection())->resolve(request()), $meta + [
            'current_page' => $p->currentPage(),
            'per_page' => $p->perPage(),
            'total' => $p->total(),
            'last_page' => $p->lastPage(),
        ]);
    }

    protected function perPage(int $default = 20, int $max = 100): int
    {
        return max(1, min($max, (int) request()->query('per_page', $default)));
    }
}
