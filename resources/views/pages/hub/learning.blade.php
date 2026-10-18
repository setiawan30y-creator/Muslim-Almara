{{-- Tab Belajar — @2026-Alifiandra --}}
@php
    $soon = [
        ['Belajar Tajwid', 'Kurikulum bertingkat, contoh ayat, kuis', 'learn'],
        ['Hafalan & Murojaah', 'Target hafalan, sembunyikan teks, jadwal ulang', 'repeat'],
        ['Translate', 'Terjemahan teks & percakapan', 'language'],
        ['Jurnal & Koleksi', 'Kumpulan catatan tadabur tematik', 'note'],
    ];
@endphp
<x-layouts.app title="Belajar">
    <x-page-head title="Belajar" subtitle="Materi pembelajaran pribadi" />
    <div class="stack">
        <div class="hub-grid">
            <a href="{{ route('asmaul-husna') }}" class="card hub-card">
                <span class="shortcut__ico shortcut__ico--gold"><x-icon name="star" /></span>
                <span><b style="display:block">Asmaul Husna</b><span class="desc">99 nama Allah beserta artinya</span></span>
            </a>
            <a href="{{ route('quran.index') }}" class="card hub-card">
                <span class="shortcut__ico"><x-icon name="book" /></span>
                <span><b style="display:block">Tafsir Ringkas</b><span class="desc">Tersedia di setiap ayat Al-Qur'an</span></span>
            </a>
            @auth
                <a href="{{ route('profile.notes') }}" class="card hub-card">
                    <span class="shortcut__ico"><x-icon name="note" /></span>
                    <span><b style="display:block">Catatan Tadabur</b><span class="desc">Catatan pribadi per ayat</span></span>
                </a>
            @endauth
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
            <p class="small muted" style="margin-top:12px">Materi Tajwid dan hafalan diterbitkan setelah ditinjau guru/ahli yang kompeten, sesuai roadmap.</p>
        </section>
    </div>
</x-layouts.app>
