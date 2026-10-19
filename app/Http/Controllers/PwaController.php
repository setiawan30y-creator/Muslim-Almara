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
            'short_name' => mb_strimwidth($name, 0, 14, ''),
            'description' => "Pendamping Muslim pribadi: Al-Qur'an, jadwal sholat, kiblat, dzikir & doa.",
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
                ['name' => 'Dzikir & Doa', 'url' => '/doa-dzikir', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function serviceWorker(): Response
    {
        $assets = ['css/app.css', 'js/app.js', 'favicon.svg', 'icons/icon-192.png', 'icons/icon-512.png'];
        $hash = substr(md5(implode('|', array_map(fn ($f) => $f.@filemtime(public_path($f)), $assets)).config('almara.version').filemtime(resource_path('views/pwa/sw.blade.php'))), 0, 10);

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

    public function sitemap(\App\Services\Quran\QuranService $quran)
    {
        $urls = collect(['/', '/al-quran', '/jadwal-sholat', '/kiblat', '/doa-dzikir', '/ibadah', '/belajar', '/asmaul-husna', '/tasbih', '/kalender', '/zakat', '/panduan', '/panduan/tajwid', '/panduan/umroh', '/panduan/haji', '/panduan/ziarah', '/hafalan', '/terjemah', '/masjid-terdekat', '/tentang', '/kebijakan-privasi', '/syarat-ketentuan'])
            ->merge($quran->surahs()->map(fn ($s) => '/al-quran/'.$s->number))
            ->map(fn ($u) => url($u));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
