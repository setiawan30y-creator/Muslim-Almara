<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar (blueprint §10.2) + larangan cache untuk respons pribadi (§9.1):
 * halaman yang dibuka saat login, halaman akun, dan admin tidak boleh disimpan cache publik/PWA.
 * @2026-Alifiandra
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=()');

        $private = $request->user() !== null
            || $request->is('admin', 'admin/*', 'profil', 'profil/*', 'masuk', 'daftar', 'lupa-sandi', 'reset-sandi/*', 'verifikasi-email*', 'api/v1/me*', 'api/v1/user/*', 'api/v1/auth/*');
        if ($private) {
            $h->set('Cache-Control', 'no-store, private');
            $h->set('X-Almara-Private', '1');
        }

        return $response;
    }
}
