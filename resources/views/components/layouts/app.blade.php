@props(['title' => null, 'description' => null, 'loc' => null, 'canonical' => null])
<!DOCTYPE html>
{{-- Muslim Almara — dikembangkan oleh @2026-Alifiandra --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Jadwal sholat, Al-Qur\'an dan terjemahan, arah kiblat, doa harian, Asmaul Husna, tasbih digital, kalender Hijriah, dan kalkulator zakat.' }}">
    <meta name="author" content="@2026-Alifiandra">
    <meta name="theme-color" content="#0f6b5c" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0d1412" media="(prefers-color-scheme: dark)">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? 'Pendamping ibadah harian: jadwal sholat, Al-Qur\'an, kiblat, doa, dan lainnya.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    <script>
        // Terapkan tema sebelum render untuk mencegah kedip.
        try { var t = localStorage.getItem('ma-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
        try { var s = localStorage.getItem('ma-arabic-size'); if (s) document.documentElement.style.setProperty('--arabic-size', s + 'rem'); } catch (e) {}
    </script>
    @stack('head')
</head>
<body class="has-footer">
@php
    $nav = [
        ['route' => 'home', 'label' => 'Beranda', 'icon' => 'home', 'match' => ['home']],
        ['route' => 'prayer', 'label' => 'Sholat', 'icon' => 'clock', 'match' => ['prayer', 'location']],
        ['route' => 'quran.index', 'label' => "Qur'an", 'icon' => 'book', 'match' => ['quran.*']],
        ['route' => 'qibla', 'label' => 'Kiblat', 'icon' => 'compass', 'match' => ['qibla']],
    ];
    $more = ['menu', 'doa', 'asmaul-husna', 'tasbih', 'calendar', 'zakat', 'about', 'privacy', 'terms'];
@endphp
<a href="#konten" class="skip-link">Lewati ke konten</a>

<header class="appbar">
    <div class="appbar__inner">
        <a href="{{ route('home') }}" class="brand" aria-label="{{ config('app.name') }} — Beranda">
            <span class="brand__mark"><x-icon name="brand" /></span>
            <span>Muslim <b>Almara</b></span>
        </a>
        <nav class="nav-desktop" aria-label="Navigasi utama">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" @if (request()->routeIs(...$item['match'])) aria-current="page" @endif>{{ $item['label'] === "Qur'an" ? "Al-Qur'an" : ($item['label'] === 'Sholat' ? 'Jadwal Sholat' : $item['label']) }}</a>
            @endforeach
            <a href="{{ route('doa') }}" @if (request()->routeIs('doa')) aria-current="page" @endif>Doa</a>
            <a href="{{ route('calendar') }}" @if (request()->routeIs('calendar')) aria-current="page" @endif>Kalender</a>
            <a href="{{ route('menu') }}" @if (request()->routeIs('menu', 'asmaul-husna', 'tasbih', 'zakat')) aria-current="page" @endif>Lainnya</a>
        </nav>
        <div class="appbar__actions">
            @if ($loc)
                <a href="{{ route('location', ['kembali' => '/'.ltrim(request()->path(), '/')]) }}" class="chip chip--loc" aria-label="Ubah lokasi: {{ $loc['nama'] }}">
                    <x-icon name="pin" /><span>{{ $loc['nama'] }}</span>
                </a>
            @endif
            <button type="button" class="icon-btn" data-theme-toggle aria-label="Ganti tema terang/gelap"><x-icon name="theme" /></button>
        </div>
    </div>
</header>

<main id="konten" class="main">
    {{ $slot }}
</main>

<footer class="footer">
    <div class="footer__inner">
        <div>
            <a href="{{ route('home') }}" class="brand"><span class="brand__mark"><x-icon name="brand" /></span><span>Muslim <b>Almara</b></span></a>
            <p class="muted small" style="margin-top:12px;max-width:420px">Pendamping ibadah harian: jadwal sholat, Al-Qur'an dan terjemahan, arah kiblat, doa, dan berbagai alat bantu ibadah lainnya.</p>
        </div>
        <div>
            <h3>Fitur</h3>
            <ul>
                <li><a href="{{ route('prayer') }}">Jadwal Sholat</a></li>
                <li><a href="{{ route('quran.index') }}">Al-Qur'an</a></li>
                <li><a href="{{ route('qibla') }}">Arah Kiblat</a></li>
                <li><a href="{{ route('doa') }}">Doa Harian</a></li>
                <li><a href="{{ route('zakat') }}">Kalkulator Zakat</a></li>
            </ul>
        </div>
        <div>
            <h3>Informasi</h3>
            <ul>
                <li><a href="{{ route('about') }}">Tentang</a></li>
                <li><a href="{{ route('privacy') }}">Kebijakan Privasi</a></li>
                <li><a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a></li>
            </ul>
        </div>
        <div class="footer__bottom" style="grid-column:1/-1">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <span>Dikembangkan oleh @2026-Alifiandra</span>
        </div>
    </div>
</footer>

<nav class="bottom-nav" aria-label="Navigasi bawah">
    @foreach ($nav as $item)
        <a href="{{ route($item['route']) }}" @if (request()->routeIs(...$item['match'])) aria-current="page" @endif>
            <span class="pill"><x-icon :name="$item['icon']" /></span>{{ $item['label'] }}
        </a>
    @endforeach
    <a href="{{ route('menu') }}" @if (request()->routeIs(...$more)) aria-current="page" @endif>
        <span class="pill"><x-icon name="grid" /></span>Lainnya
    </a>
</nav>

<div class="toast" role="status" aria-live="polite" data-toast></div>

<dialog class="dialog" data-confirm>
    <h2 data-confirm-title>Konfirmasi</h2>
    <p class="muted" data-confirm-text></p>
    <div class="dialog__actions">
        <button type="button" class="btn btn--outline" value="cancel" data-confirm-cancel>Batal</button>
        <button type="button" class="btn btn--danger" value="ok" data-confirm-ok>Ya, lanjutkan</button>
    </div>
</dialog>

<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
