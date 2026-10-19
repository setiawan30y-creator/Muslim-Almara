{{-- Tab Ibadah — @2026-Alifiandra --}}
@php
    $items = [
        [route('prayer'), 'Jadwal Sholat', 'Harian & bulanan, metode dapat dipilih', 'clock', false],
        [route('qibla'), 'Arah Kiblat', 'Kompas perangkat & derajat', 'compass', false],
        [route('mosques'), 'Masjid Terdekat', 'Peta & daftar masjid/musala sekitar', 'pin', false],
        [route('doa'), 'Dzikir & Doa', 'Arab, Latin, arti, dan sumber', 'hands', false],
        [route('tasbih'), 'Tasbih Digital', 'Penghitung dzikir', 'beads', false],
        [route('calendar'), 'Kalender Hijriah', 'Hari besar Islam', 'calendar', false],
        [route('zakat'), 'Kalkulator Zakat', 'Maal, penghasilan, fitrah', 'coins', true],
        [route('guide.module', 'umroh'), 'Panduan Umroh', 'Rukun, langkah & bacaan', 'kaaba', false],
        [route('guide.module', 'haji'), 'Panduan Haji', 'Jenis haji & urutan hari-hari haji', 'kaaba', false],
        [route('guide.module', 'ziarah'), 'Ziarah Kubur', 'Adab, salam, dan doa bersumber', 'map', false],
    ];
@endphp
<x-layouts.app title="Ibadah">
    <x-page-head title="Ibadah" subtitle="Alat bantu ibadah harian" />
    <div class="stack">
        <div class="hub-grid">
            @foreach ($items as [$href, $label, $desc, $icon, $gold])
                <a href="{{ $href }}" class="card hub-card">
                    <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>
                    <span><b style="display:block">{{ $label }}</b><span class="desc">{{ $desc }}</span></span>
                </a>
            @endforeach
        </div>
        <p class="small muted">Panduan manasik dan ziarah bersifat ringkasan; ikuti arahan pembimbing ibadah dan ketentuan resmi penyelenggara.</p>
    </div>
</x-layouts.app>
