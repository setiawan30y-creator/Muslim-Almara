{{-- Tab Belajar — @2026-Alifiandra --}}
@php
    $items = [
        [route('guide.module', 'tajwid'), 'Belajar Tajwid', 'Hukum bacaan, contoh ayat, dan latihan', 'learn', false],
        [route('hafalan'), 'Hafalan & Murojaah', 'Sembunyikan teks, nilai, jadwal ulang', 'repeat', false],
        [route('translate'), 'Terjemah & Frasa', 'Frasa Arab–Indonesia untuk ibadah & perjalanan', 'language', false],
        [route('profile.notes'), 'Jurnal & Koleksi', 'Catatan tadabur bertag & koleksi ayat', 'note', false],
        [route('asmaul-husna'), 'Asmaul Husna', '99 nama Allah beserta artinya', 'star', true],
        [route('quran.index'), 'Tafsir Ringkas', 'Tersedia di setiap ayat Al-Qur\'an', 'book', false],
    ];
@endphp
<x-layouts.app title="Belajar">
    <x-page-head title="Belajar" subtitle="Materi pembelajaran pribadi" />
    <div class="stack">
        <div class="hub-grid">
            @foreach ($items as [$href, $label, $desc, $icon, $gold])
                <a href="{{ $href }}" class="card hub-card">
                    <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>
                    <span><b style="display:block">{{ $label }}</b><span class="desc">{{ $desc }}</span></span>
                </a>
            @endforeach
        </div>
        <p class="small muted">Materi tajwid diterbitkan setelah ditinjau guru/ahli yang kompeten. Tetap berguru langsung (talaqqi) untuk memperbaiki bacaan.</p>
    </div>
</x-layouts.app>
