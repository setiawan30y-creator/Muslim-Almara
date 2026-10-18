<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Progressive Web App: manifest, service worker, dan halaman offline.
 * Manifest & service worker dibuat dinamis agar nama aplikasi mengikuti APP_NAME
 * dan versi cache otomatis berganti saat aset diperbarui.
 *
 * @2026-Alifiandra
 */
class PwaController extends Controller
{
    public function manifest(): Response
    {
        $name = config('app.name');

        $manifest = [
            'id' => '/',
            'name' => $name,
            'short_name' => mb_strimwidth($name, 0, 14),
            'description' => "Jadwal sholat, Al-Qur'an, arah kiblat, doa harian, dan alat bantu ibadah lainnya.",
            'lang' => 'id',
            'dir' => 'ltr',
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'portrait',
            'background_color' => '#f6f4ee',
            'theme_color' => '#0f6b5c',
            'categories' => ['lifestyle', 'education', 'books'],
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'Jadwal Sholat', 'url' => '/jadwal-sholat', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
                ["name" => "Al-Qur'an", 'url' => '/al-quran', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Arah Kiblat', 'url' => '/kiblat', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Tasbih', 'url' => '/tasbih', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function serviceWorker(): Response
    {
        $assets = ['css/app.css', 'js/app.js', 'favicon.svg', 'icons/icon-192.png', 'icons/icon-512.png'];
        $hash = substr(md5(implode('|', array_map(fn ($f) => $f.@filemtime(public_path($f)), $assets)).config('muslim.version').filemtime(resource_path('views/pwa/sw.blade.php'))), 0, 10);

        return response()
            ->view('pwa.sw', ['version' => $hash, 'assets' => $assets])
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Service-Worker-Allowed', '/');
    }

    public function offline()
    {
        return view('pages.offline');
    }
}
