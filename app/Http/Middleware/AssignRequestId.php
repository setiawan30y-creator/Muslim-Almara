<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Correlation/request ID untuk debugging (blueprint §7.5). @2026-Alifiandra
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->headers->get('X-Request-Id');
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9\-]{8,64}$/', $id)) {
            $id = (string) Str::ulid();
        }
        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
