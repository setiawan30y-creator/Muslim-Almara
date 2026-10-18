{{-- Menu Lainnya — @2026-Alifiandra --}}
@php
    $items = [
        ['prayer', 'Jadwal Sholat', 'Hari ini & sebulan', 'clock', false],
        ['quran.index', "Al-Qur'an", '114 surah & terjemahan', 'book', false],
        ['qibla', 'Arah Kiblat', 'Kompas digital', 'compass', false],
        ['doa', 'Doa Harian', 'Lengkap dengan dalil', 'hands', false],
        ['asmaul-husna', 'Asmaul Husna', '99 nama Allah', 'star', true],
        ['tasbih', 'Tasbih Digital', 'Penghitung dzikir', 'beads', false],
        ['calendar', 'Kalender Hijriah', 'Hari besar Islam', 'calendar', false],
        ['zakat', 'Kalkulator Zakat', 'Maal, penghasilan, fitrah', 'coins', true],
        ['location', 'Lokasi', 'Ubah kota atau GPS', 'pin', false],
    ];
@endphp
<x-layouts.app title="Semua Fitur">
    <x-page-head title="Semua fitur" subtitle="Alat bantu ibadah harian" />
    <div class="stack">
        <div class="menu-grid">
            @foreach ($items as [$route, $label, $sub, $icon, $gold])
                <a href="{{ route($route) }}" class="card menu-item">
                    <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>
                    <span><b style="display:block">{{ $label }}</b><span>{{ $sub }}</span></span>
                </a>
            @endforeach
        </div>
        <section class="card" aria-labelledby="pwa-h">
            <h2 id="pwa-h"><x-icon name="phone" style="width:20px;height:20px;display:inline;vertical-align:-4px;color:var(--color-primary)" /> Aplikasi &amp; offline</h2>
            <p class="small muted" data-pwa-state>Memeriksa dukungan aplikasi…</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" class="btn btn--sm" data-install-action hidden><x-icon name="download" />Pasang aplikasi</button>
                <a href="{{ route('quran.index') }}#offline" class="btn btn--tonal btn--sm"><x-icon name="book" />Simpan Al-Qur'an untuk offline</a>
            </div>
        </section>

        <div class="card card--list">
            <ul class="list">
                <li><button type="button" class="list-row" data-theme-toggle style="width:100%;background:none;border:0;font:inherit;cursor:pointer;text-align:left"><x-icon name="theme" style="width:22px;height:22px;color:var(--color-primary)" /><span class="list-row__main list-row__title">Tema terang / gelap</span><x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></button></li>
                <li><a class="list-row" href="{{ route('about') }}"><x-icon name="info" style="width:22px;height:22px;color:var(--color-primary)" /><span class="list-row__main list-row__title">Tentang aplikasi</span><x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                <li><a class="list-row" href="{{ route('privacy') }}"><x-icon name="shield" style="width:22px;height:22px;color:var(--color-primary)" /><span class="list-row__main list-row__title">Kebijakan privasi</span><x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                <li><a class="list-row" href="{{ route('terms') }}"><x-icon name="file" style="width:22px;height:22px;color:var(--color-primary)" /><span class="list-row__main list-row__title">Syarat &amp; ketentuan</span><x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
            </ul>
        </div>
    </div>
</x-layouts.app>
