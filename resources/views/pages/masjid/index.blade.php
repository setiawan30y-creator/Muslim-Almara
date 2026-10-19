{{-- Masjid terdekat (OpenStreetMap) — @2026-Alifiandra --}}
@php $loc = \App\Support\Location::resolve(request(), app(\App\Services\Content\StaticContent::class), false); @endphp
@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous">
@endpush
<x-layouts.app title="Masjid Terdekat" description="Temukan masjid dan musala di sekitar Anda dari data OpenStreetMap.">
    <x-page-head title="Masjid Terdekat" subtitle="Data peta komunitas OpenStreetMap" :back="route('hub.worship')" />
    <div class="stack" data-mosques data-overpass="{{ config('almara.mosque_finder.overpass_url') }}"
         data-lat="{{ $loc['lat'] }}" data-lng="{{ $loc['lng'] }}" data-name="{{ $loc['nama'] }}">
        <div class="reader-tools" style="position:static" role="toolbar" aria-label="Pencarian masjid">
            <button type="button" class="btn btn--sm" data-mosque-locate><x-icon name="locate" />Lokasi saya</button>
            <span class="small muted" data-mosque-where>Sekitar {{ $loc['nama'] }}</span>
            <span class="grow"></span>
            <label for="radius" class="sr-only">Radius</label>
            <select id="radius" class="select" style="min-height:38px;border-radius:99px;max-width:120px" data-mosque-radius>
                <option value="1000">1 km</option><option value="3000" selected>3 km</option><option value="5000">5 km</option><option value="10000">10 km</option>
            </select>
        </div>
        <div class="mosque-map card" data-mosque-map role="region" aria-label="Peta masjid"></div>
        <p class="small muted" data-mosque-status aria-live="polite">Memuat data…</p>
        <ol class="card card--list mosque-list" data-mosque-list hidden></ol>
        <p class="small muted">Data © kontributor <a href="https://www.openstreetmap.org/copyright" rel="noopener" target="_blank">OpenStreetMap</a> (ODbL), diambil lewat Overpass API. Koordinat pencarian dikirim ke layanan tersebut dan tidak disimpan oleh {{ config('app.name') }}. Data komunitas bisa belum lengkap.</p>
    </div>
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" defer></script>
    @endpush
</x-layouts.app>
