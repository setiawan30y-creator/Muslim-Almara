<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\SecurityHeaders;
use App\Services\Preferences\PreferenceService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// ALMARA MUSLIM — @2026-Alifiandra
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        // Di belakang tunnel HTTPS / reverse proxy (cloudflared, ngrok, Nginx, load balancer):
        // percayai header X-Forwarded-* agar URL aset & service worker memakai https.
        // Bawaan hanya proksi lokal; isi TRUSTED_PROXIES (mis. "*" atau daftar IP) di server produksi.
        $middleware->trustProxies(at: array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))))) ?: null);
        // Cookie preferensi tampilan dibaca & ditulis JS (tamu); nilainya divalidasi di server.
        $middleware->encryptCookies(except: PreferenceService::PLAIN_COOKIES);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('profile'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
