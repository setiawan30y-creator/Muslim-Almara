@props(['title' => null, 'description' => null, 'loc' => null, 'canonical' => null, 'noindex' => false])
@php
    /** @var array $appearance diisi view composer dari PreferenceService */
    $ap = $appearance ?? \App\Services\Preferences\PreferenceService::APPEARANCE_DEFAULTS;
    $user = auth()->user();
    $nav = [
        ['route' => 'home', 'label' => 'Beranda', 'icon' => 'home', 'match' => ['home']],
        ['route' => 'quran.index', 'label' => "Qur'an", 'icon' => 'book', 'match' => ['quran.*']],
        ['route' => 'hub.learning', 'label' => 'Belajar', 'icon' => 'learn', 'match' => ['hub.learning', 'asmaul-husna']],
        ['route' => 'hub.worship', 'label' => 'Ibadah', 'icon' => 'pray', 'match' => ['hub.worship', 'prayer', 'location', 'qibla', 'doa', 'doa.show', 'tasbih', 'calendar', 'zakat']],
        ['route' => 'profile', 'label' => 'Profil', 'icon' => 'user', 'match' => ['profile', 'profile.*', 'settings.*', 'login', 'register', 'password.*', 'verification.*']],
    ];
@endphp
<!DOCTYPE html>
{{-- ALMARA MUSLIM — dikembangkan oleh @2026-Alifiandra --}}
<html lang="id"
      data-accent="{{ $ap['accent_color'] }}"
      data-ui-size="{{ $ap['interface_text_size'] }}"
      data-reading-size="{{ $ap['reading_text_size'] }}"
      data-translation-size="{{ $ap['translation_text_size'] }}"
      @if ($ap['theme_mode'] !== 'system') data-theme="{{ $ap['theme_mode'] }}" @endif
      data-theme-mode="{{ $ap['theme_mode'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? "Pendamping Muslim pribadi: Al-Qur'an, jadwal sholat, kiblat, dzikir & doa, kalender Hijriah." }}">
    <meta name="author" content="@2026-Alifiandra">
    @if ($noindex)<meta name="robots" content="noindex">@endif
    <meta name="theme-color" content="#0f6b5c" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0d1412" media="(prefers-color-scheme: dark)">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="application-name" content="{{ config('app.name') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body class="has-footer" data-auth="{{ $user ? '1' : '0' }}">
<a href="#konten" class="skip-link">Lewati ke konten</a>

<header class="appbar">
    <div class="appbar__inner">
        <a href="{{ route('home') }}" class="brand" aria-label="{{ config('app.name') }} — Beranda">
            <span class="brand__mark"><x-icon name="brand" /></span>
            <span>ALMARA <b>MUSLIM</b></span>
        </a>
        <nav class="nav-desktop" aria-label="Navigasi utama">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" @if (request()->routeIs(...$item['match'])) aria-current="page" @endif>{{ $item['label'] === "Qur'an" ? "Al-Qur'an" : $item['label'] }}</a>
            @endforeach
            @can('admin.access')
                <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.*')) aria-current="page" @endif>Admin</a>
            @endcan
        </nav>
        <div class="appbar__actions">
            @if ($loc)
                <a href="{{ route('location', ['kembali' => '/'.ltrim(request()->path(), '/')]) }}" class="chip chip--loc" aria-label="Ubah lokasi: {{ $loc['nama'] }}">
                    <x-icon name="pin" /><span>{{ $loc['nama'] }}</span>
                </a>
            @endif
            <button type="button" class="btn btn--sm" data-install hidden><x-icon name="download" />Pasang</button>
            <button type="button" class="icon-btn" data-theme-toggle aria-label="Ganti tema terang/gelap"><x-icon name="theme" /></button>
        </div>
    </div>
</header>

<main id="konten" class="main">
    @if (session('status'))
        <div class="alert alert--success flash" role="status"><x-icon name="check-circle" /><div>{{ session('status') }}</div></div>
    @endif
    @if ($errors->any() && ! ($hideErrorSummary ?? false))
        <div class="alert alert--danger flash" role="alert"><x-icon name="alert" />
            <div><b>Periksa kembali isian Anda.</b>
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif
    {{ $slot }}
</main>

<footer class="footer">
    <div class="footer__inner">
        <div>
            <a href="{{ route('home') }}" class="brand"><span class="brand__mark"><x-icon name="brand" /></span><span>ALMARA <b>MUSLIM</b></span></a>
            <p class="muted small" style="margin-top:12px;max-width:420px">Pendamping ibadah dan belajar pribadi. Konten agama ditampilkan dengan sumber dan melalui proses review.</p>
        </div>
        <div>
            <h3>Fitur</h3>
            <ul>
                <li><a href="{{ route('quran.index') }}">Al-Qur'an</a></li>
                <li><a href="{{ route('prayer') }}">Jadwal Sholat</a></li>
                <li><a href="{{ route('qibla') }}">Arah Kiblat</a></li>
                <li><a href="{{ route('doa') }}">Dzikir &amp; Doa</a></li>
                <li><a href="{{ route('calendar') }}">Kalender Hijriah</a></li>
            </ul>
        </div>
        <div>
            <h3>Informasi</h3>
            <ul>
                <li><a href="{{ route('settings.index') }}">Pengaturan</a></li>
                <li><a href="{{ route('about') }}">Tentang &amp; sumber data</a></li>
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
</nav>

<div class="toast" role="status" aria-live="polite" data-toast></div>
<div class="net-status" role="status" aria-live="polite" data-net-status hidden><x-icon name="info" />Offline — menampilkan data tersimpan</div>

<dialog class="dialog" data-confirm>
    <h2 data-confirm-title>Konfirmasi</h2>
    <p class="muted" data-confirm-text></p>
    <div class="dialog__actions">
        <button type="button" class="btn btn--outline" data-confirm-cancel>Batal</button>
        <button type="button" class="btn btn--danger" data-confirm-ok>Ya, lanjutkan</button>
    </div>
</dialog>

<script>
    window.ALMARA = {
        auth: {{ $user ? 'true' : 'false' }},
        routes: { appearance: @json(route('settings.appearance.save')), quranPrefs: @json(route('settings.quran.save')), login: @json(route('login')) },
        clearPrivateCache: {{ session('clear_private_cache') ? 'true' : 'false' }}
    };
</script>
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
