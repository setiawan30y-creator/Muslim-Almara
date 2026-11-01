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

    public function sitemap(\App\Services\Quran\QuranService $quran, \App\Services\Content\StaticContent $content)
    {
        $static = ['/', '/al-quran', '/jadwal-sholat', '/kiblat', '/doa-dzikir', '/ibadah', '/belajar', '/asmaul-husna', '/tasbih', '/kalender', '/zakat',
            '/panduan', '/panduan/tajwid', '/panduan/umroh', '/panduan/haji', '/panduan/ziarah', '/panduan/tahlil', '/hafalan', '/terjemah',
            '/masjid-terdekat', '/imsakiyah', '/puasa-sunnah', '/tentang', '/kebijakan-privasi', '/syarat-ketentuan'];
        $urls = collect($static)->map(fn ($u) => url($u));
        foreach ($content->cities() as $c) {
            $urls->push(route('prayer.city', $c['slug']), route('imsakiyah.city', $c['slug']));
        }
        foreach (\App\Models\GuideItem::query()->published()->with('category:id,module')->get(['slug', 'category_id']) as $g) {
            $urls->push(route('guide.show', [$g->category->module, $g->slug]));
        }
        foreach (\App\Services\Quran\QuranDataset::AYAH_COUNTS as $i => $count) {
            $urls->push(route('quran.show', $i + 1));
            for ($a = 1; $a <= $count; $a++) {
                $urls->push(route('quran.verse', [$i + 1, $a]));
            }
        }

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml')->header('Cache-Control', 'public, max-age=86400');
    }

    public function robots()
    {
        $lines = ['User-agent: *', 'Disallow: /api/', 'Disallow: /lokasi', 'Disallow: /admin', 'Disallow: /profil', 'Disallow: /pengaturan', '', 'Sitemap: '.url('/sitemap.xml')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
