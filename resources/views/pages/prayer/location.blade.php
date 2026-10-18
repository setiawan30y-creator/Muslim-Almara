{{-- Pilih lokasi — @2026-Alifiandra --}}
@php $sep = str_contains($back, '?') ? '&' : '?'; @endphp
<x-layouts.app title="Pilih Lokasi" :noindex="true">
    <x-page-head title="Pilih lokasi" subtitle="Saat ini: {{ $current['nama'] }} ({{ $current['basis'] === 'device' ? 'perangkat' : ($current['basis'] === 'default' ? 'bawaan' : 'manual') }})" :back="$back" />

    <div class="stack" style="max-width:720px">
        <div class="card">
            <p style="margin-bottom:12px"><b>Lokasi perangkat (opsional).</b> Dipakai hanya untuk menghitung jadwal sholat dan arah kiblat yang lebih tepat. Peramban akan meminta izin Anda.</p>
            <button type="button" class="btn btn--block" data-geolocate="{{ $back }}"><x-icon name="locate" />Gunakan lokasi perangkat</button>
            <p class="small muted" style="margin:10px 0 0">Koordinat dibulatkan (±100 m) dan hanya disimpan di cookie peramban Anda — tidak disimpan di database atau akun. Anda tetap dapat memilih kota secara manual.</p>
        </div>

        <section aria-labelledby="daftar-kota">
            <h2 id="daftar-kota">Atau pilih kota</h2>
            <div class="input-wrap" style="margin-bottom:12px">
                <x-icon name="search" />
                <label for="cari-kota" class="sr-only">Cari kota</label>
                <input id="cari-kota" type="search" class="input" placeholder="Cari kota…" autocomplete="off" data-filter-input="kota-list">
            </div>
            <div class="card card--list">
                <ul class="list" id="kota-list">
                    @foreach ($cities as $c)
                        <li data-filter-item data-search="{{ $c['nama'] }}">
                            <a class="list-row" href="{{ $back.$sep }}kota={{ $c['slug'] }}">
                                <x-icon name="pin" style="width:20px;height:20px;color:var(--color-text-muted);flex:none" />
                                <span class="list-row__main">
                                    <span class="list-row__title">{{ $c['nama'] }}</span>
                                    <span class="list-row__meta" style="display:block">{{ match ($c['tz']) { 7 => 'WIB', 8 => 'WITA', 9 => 'WIT', default => 'UTC+'.$c['tz'] } }}</span>
                                </span>
                                @if ($current['slug'] === $c['slug'])
                                    <span class="badge"><x-icon name="check" style="width:14px;height:14px" />Dipilih</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <x-empty data-filter-empty hidden title="Kota tidak ditemukan" text="Gunakan tombol lokasi saya untuk kota lain." />
            </div>
        </section>
    </div>
</x-layouts.app>
