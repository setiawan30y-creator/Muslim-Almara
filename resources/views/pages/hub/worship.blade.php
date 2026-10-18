{{-- Tab Ibadah — @2026-Alifiandra --}}
@php
    $items = [
        ['prayer', 'Jadwal Sholat', 'Harian & bulanan, metode dapat dipilih', 'clock', false],
        ['qibla', 'Arah Kiblat', 'Kompas perangkat & derajat', 'compass', false],
        ['doa', 'Dzikir & Doa', 'Arab, Latin, arti, dan sumber', 'hands', false],
        ['tasbih', 'Tasbih Digital', 'Penghitung dzikir', 'beads', false],
        ['calendar', 'Kalender Hijriah', 'Hari besar Islam', 'calendar', false],
        ['zakat', 'Kalkulator Zakat', 'Maal, penghasilan, fitrah', 'coins', true],
    ];
    $soon = [
        ['Panduan Ziarah Kubur', 'Adab, salam, dan doa bersumber', 'map'],
        ['Masjid Terdekat', 'Daftar & peta masjid/musala', 'pin'],
        ['Panduan Umroh & Haji', 'Langkah manasik & bacaan', 'kaaba'],
    ];
@endphp
<x-layouts.app title="Ibadah">
    <x-page-head title="Ibadah" subtitle="Alat bantu ibadah harian" />
    <div class="stack">
        <div class="hub-grid">
            @foreach ($items as [$route, $label, $desc, $icon, $gold])
                <a href="{{ route($route) }}" class="card hub-card">
                    <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>
                    <span><b style="display:block">{{ $label }}</b><span class="desc">{{ $desc }}</span></span>
                </a>
            @endforeach
        </div>
        <section>
            <div class="section-head"><h2>Tahap berikutnya</h2></div>
            <div class="hub-grid">
                @foreach ($soon as [$label, $desc, $icon])
                    <div class="card hub-card is-soon" aria-disabled="true">
                        <span class="shortcut__ico"><x-icon :name="$icon" /></span>
                        <span><b style="display:block">{{ $label }}</b><span class="desc">{{ $desc }}</span></span>
                        <span class="badge badge--neutral" style="align-self:flex-start">Segera</span>
                    </div>
                @endforeach
            </div>
            <p class="small muted" style="margin-top:12px">Modul ini akan diterbitkan setelah konten bersumber lolos review ahli dan penyedia data terkonfigurasi.</p>
        </section>
    </div>
</x-layouts.app>
