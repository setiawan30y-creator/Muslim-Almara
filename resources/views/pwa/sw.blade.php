/*!
 * Service worker {{ config('app.name') }} — @2026-Alifiandra
 * - Halaman: network-first (selalu data terbaru), cadangan dari cache saat offline.
 * - Aset statis: stale-while-revalidate.
 * - Font Google: cache-first.
 */
const VERSION = '{{ $version }}';
const STATIC_CACHE = 'ma-static-' + VERSION;
const PAGE_CACHE = 'ma-pages-v1';
const FONT_CACHE = 'ma-fonts-v1';
const OFFLINE_URL = '/offline';

const PRECACHE = [
    OFFLINE_URL,
@foreach ($assets as $a)
    '/{{ $a }}',
@endforeach
    '/icons/maskable-512.png',
    '/manifest.webmanifest',
];

// Halaman yang disimpan saat pemasangan agar langsung bisa dibuka offline.
const WARM_PAGES = ['/', '/jadwal-sholat', '/al-quran', '/kiblat', '/doa', '/asmaul-husna', '/tasbih', '/kalender', '/zakat', '/menu'];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        await Promise.all(PRECACHE.map((u) => cache.add(new Request(u, { cache: 'reload' })).catch(() => null)));
        const pages = await caches.open(PAGE_CACHE);
        await Promise.all(WARM_PAGES.map(async (u) => {
            if (await pages.match(u)) return;
            try { const r = await fetch(u, { credentials: 'same-origin' }); if (r.ok) await pages.put(u, r); } catch (e) {}
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k.startsWith('ma-static-') && k !== STATIC_CACHE).map((k) => caches.delete(k)));
        if (self.registration.navigationPreload) { try { await self.registration.navigationPreload.enable(); } catch (e) {} }
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

function isStatic(url) {
    return /\.(?:css|js|png|svg|ico|webp|jpg|jpeg|woff2?)$/.test(url.pathname) || url.pathname === '/manifest.webmanifest';
}

async function networkFirstPage(event) {
    const req = event.request;
    const cache = await caches.open(PAGE_CACHE);
    const key = new URL(req.url);
    key.searchParams.delete('source');
    try {
        const preload = event.preloadResponse ? await event.preloadResponse : null;
        const res = preload || await fetch(req);
        // Simpan hanya halaman tanpa parameter lokasi agar cache tidak membengkak.
        if (res.ok && !key.searchParams.has('lat')) cache.put(key.toString(), res.clone());
        return res;
    } catch (e) {
        return (await cache.match(key.toString(), { ignoreSearch: false }))
            || (await cache.match(key.pathname))
            || (await caches.match(OFFLINE_URL))
            || new Response('<h1>Offline</h1>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }
}

async function staleWhileRevalidate(req, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(req, { ignoreSearch: true });
    const network = fetch(req).then((res) => { if (res.ok) cache.put(req, res.clone()); return res; }).catch(() => null);
    return cached || (await network) || Response.error();
}

async function cacheFirst(req, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(req);
    if (cached) return cached;
    try { const res = await fetch(req); if (res.ok || res.type === 'opaque') cache.put(req, res.clone()); return res; }
    catch (e) { return Response.error(); }
}

async function networkFirstApi(req) {
    const cache = await caches.open(PAGE_CACHE);
    try { const res = await fetch(req); if (res.ok) cache.put(req, res.clone()); return res; }
    catch (e) { return (await cache.match(req)) || new Response(JSON.stringify({ success: false, message: 'Offline' }), { status: 503, headers: { 'Content-Type': 'application/json' } }); }
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(cacheFirst(req, FONT_CACHE));
        return;
    }
    if (url.origin !== self.location.origin) return;
    if (url.pathname === '/sw.js') return;

    if (req.mode === 'navigate') { event.respondWith(networkFirstPage(event)); return; }
    if (url.pathname.startsWith('/api/')) { event.respondWith(networkFirstApi(req)); return; }
    if (isStatic(url)) { event.respondWith(staleWhileRevalidate(req, STATIC_CACHE)); return; }
});
