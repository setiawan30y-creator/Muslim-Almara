{{-- Pengaturan — @2026-Alifiandra --}}
<x-layouts.app title="Pengaturan">
    <x-page-head title="Pengaturan" :subtitle="auth()->check() ? 'Tersimpan di akun & tersinkron antar perangkat' : 'Tersimpan di perangkat ini — masuk untuk menyinkronkan'" :back="route('profile')" />
    <div class="card card--list" style="max-width:760px">
        <ul class="list">
            @foreach ([
                ['settings.appearance', 'palette', 'Tampilan & Tema', 'Terang/gelap/sistem, warna aksen, ukuran teks'],
                ['settings.quran', 'book', "Al-Qur'an", 'Mode baca, terjemahan, tafsir'],
                ['settings.prayer', 'clock', 'Jadwal Sholat', 'Metode, mazhab Asar, koreksi menit'],
                ['settings.home', 'layout', 'Beranda', 'Urutan & visibilitas kartu'],
                ['location', 'pin', 'Lokasi', 'Kota manual atau lokasi perangkat'],
            ] as [$r, $i, $t, $d])
                <li><a class="list-row" href="{{ route($r) }}"><x-icon :name="$i" style="width:22px;height:22px;color:var(--color-primary);flex:none" />
                    <span class="list-row__main"><span class="list-row__title">{{ $t }}</span><span class="list-row__meta" style="display:block">{{ $d }}</span></span>
                    <x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
            @endforeach
        </ul>
    </div>
</x-layouts.app>
