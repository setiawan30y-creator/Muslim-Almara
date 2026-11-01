{{-- Kalender puasa sunnah — @2026-Alifiandra --}}
<x-layouts.app title="Kalender Puasa Sunnah & Jadwal Puasa {{ $today->year }}" description="Jadwal puasa sunnah terdekat: Senin-Kamis, Ayyamul Bidh, Arafah, Tasu'a-Asyura, 6 hari Syawal, serta hari yang dilarang berpuasa.">
    <x-page-head title="Kalender Puasa" subtitle="Puasa wajib & sunnah 90 hari ke depan" :back="route('hub.worship')" />
    <div class="layout-side">
        <section class="card card--list" aria-label="Daftar hari puasa">
            <ul class="fast-list">
                @forelse ($days as $d)
                    <li class="{{ $d['tanggal']->isSameDay($today) ? 'is-today' : '' }}">
                        <span class="date"><b>{{ $d['tanggal']->format('j') }}</b><small>{{ $d['tanggal']->translatedFormat('M') }}</small></span>
                        <span class="info"><b>{{ $d['tanggal']->translatedFormat('l') }}{{ $d['tanggal']->isSameDay($today) ? ' · hari ini' : '' }}</b>
                            <span class="small muted">{{ $d['hijri']['label'] ?? ($d['hijri']['day'].' '.$d['hijri']['month_name'].' '.$d['hijri']['year'].' H') }}</span></span>
                        <span class="tags">@foreach ($d['tags'] as $t)<span class="badge badge--{{ $kinds[$t][1] }}">{{ $kinds[$t][0] }}</span>@endforeach</span>
                    </li>
                @empty
                    <li><x-empty title="Tidak ada data" /></li>
                @endforelse
            </ul>
        </section>
        <aside class="stack">
            <section class="card card--flat small">
                <h2>Dasar & keterangan</h2>
                <ul class="muted" style="padding-left:1.1rem;margin:0">
                    @foreach ($kinds as [$label, $tone, $ref])<li><b style="color:var(--color-text)">{{ $label }}</b> — {{ $ref }}</li>@endforeach
                </ul>
                <p class="muted" style="margin:10px 0 0">Tanggal Hijriah dihitung secara aritmetika (urfi) dan dapat berbeda ±1 hari dari penetapan resmi pemerintah (sidang isbat). Untuk Ramadan, Idul Fitri, dan Idul Adha, ikuti pengumuman resmi.</p>
            </section>
            <section class="card">
                <h2>Imsakiyah Ramadan</h2>
                <p class="small muted">Jadwal imsak & berbuka sebulan penuh untuk lokasi Anda.</p>
                <a class="btn btn--sm" href="{{ route('imsakiyah') }}"><x-icon name="calendar" />Lihat imsakiyah</a>
            </section>
        </aside>
    </div>
</x-layouts.app>
