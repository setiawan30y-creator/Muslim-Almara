/*!
 * Service worker {{ config('app.name') }} — @2026-Alifiandra
 * Strategi cache terpisah (blueprint §9.1):
 *  - Aset statis: stale-while-revalidate (cache berversi).
 *  - Halaman & API publik: network-first, cadangan cache saat offline.
 *  - Halaman/API pribadi (akun, admin, /api/v1/me, login): TIDAK PERNAH dicache.
 *  - Respons ber-header Cache-Control no-store/private atau X-Almara-Private tidak disimpan.
 */
const VERSION = '{{ $version }}';
const STATIC_CACHE = 'almara-static-' + VERSION;
const PAGE_CACHE = 'almara-pages-v2';
const FONT_CACHE = 'almara-fonts-v1';
const OFFLINE_URL = '/offline';

const PRECACHE = [
    OFFLINE_URL,
@foreach ($assets as $a)
    '/{{ $a }}',
@endforeach
    '/icons/maskable-512.png',
    '/manifest.webmanifest',
];

const WARM_PAGES = ['/', '/al-quran', '/jadwal-sholat', '/kiblat', '/doa-dzikir', '/ibadah', '/belajar', '/tasbih', '/kalender', '/terjemah', '/panduan'];

const PRIVATE_PATHS = [/^\/admin/, /^\/profil/, /^\/masuk/, /^\/daftar/, /^\/keluar/, /^\/lupa-sandi/, /^\/reset-sandi/, /^\/verifikasi-email/,
    /^\/api\/v1\/me/, /^\/api\/v1\/user/, /^\/api\/v1\/auth/, /^\/api\/v1\/quran\/reading-preferences/, /^\/pengaturan/,
    // Audio: data penyedia tidak boleh disimpan permanen; berkas besar & permintaan Range tidak dicache.
    /^\/api\/v1\/quran\/audio/, /^\/al-quran\/audio/];

function isPrivate(url) {
    return PRIVATE_PATHS.some((re) => re.test(url.pathname));
}

function cacheable(res) {
    if (!res || !res.ok || res.type === 'opaqueredirect') return false;
    const cc = (res.headers.get('Cache-Control') || '').toLowerCase();
    return !cc.includes('no-store') && !cc.includes('private') && !res.headers.get('X-Almara-Private');
}

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        await Promise.all(PRECACHE.map((u) => cache.add(new Request(u, { cache: 'reload' })).catch(() => null)));
        const pages = await caches.open(PAGE_CACHE);
        await Promise.all(WARM_PAGES.map(async (u) => {
            try {
                // Tanpa cookie agar yang tersimpan selalu versi publik (bukan versi akun).
                const r = await fetch(u, { credentials: 'omit' });
                if (cacheable(r)) await pages.put(u, r);
            } catch (e) {}
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => (k.startsWith('almara-static-') && k !== STATIC_CACHE) || k.startsWith('ma-')).map((k) => caches.delete(k)));
        if (self.registration.navigationPreload) { try { await self.registration.navigationPreload.enable(); } catch (e) {} }
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
    if (event.data === 'CLEAR_PRIVATE') event.waitUntil(caches.delete(PAGE_CACHE));
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
        if (cacheable(res) && !key.searchParams.has('lat')) cache.put(key.toString(), res.clone());
        return res;
    } catch (e) {
        return (await cache.match(key.toString()))
            || (await cache.match(key.pathname))
            || (await caches.match(OFFLINE_URL))
            || new Response('<h1>Offline</h1>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }
}

async function staleWhileRevalidate(req) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(req, { ignoreSearch: true });
    const network = fetch(req).then((res) => { if (res.ok) cache.put(req, res.clone()); return res; }).catch(() => null);
    return cached || (await network) || Response.error();
}

async function cacheFirst(req) {
    const cache = await caches.open(FONT_CACHE);
    const cached = await cache.match(req);
    if (cached) return cached;
    try { const res = await fetch(req); if (res.ok || res.type === 'opaque') cache.put(req, res.clone()); return res; }
    catch (e) { return Response.error(); }
}

async function networkFirstApi(req) {
    const cache = await caches.open(PAGE_CACHE);
    try { const res = await fetch(req); if (cacheable(res)) cache.put(req, res.clone()); return res; }
    catch (e) {
        return (await cache.match(req)) || new Response(JSON.stringify({ message: 'Anda sedang offline.' }), { status: 503, headers: { 'Content-Type': 'application/json' } });
    }
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') { event.respondWith(cacheFirst(req)); return; }
    if (url.origin !== self.location.origin) return;
    if (url.pathname === '/service-worker.js' || url.pathname === '/sw.js') return;
    if (isPrivate(url)) return; // langsung ke jaringan, tidak pernah disimpan

    if (req.mode === 'navigate') { event.respondWith(networkFirstPage(event)); return; }
    if (url.pathname.startsWith('/api/')) { event.respondWith(networkFirstApi(req)); return; }
    if (isStatic(url)) { event.respondWith(staleWhileRevalidate(req)); }
});
