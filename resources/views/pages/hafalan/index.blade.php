{{-- Hafalan & Murojaah — @2026-Alifiandra --}}
<x-layouts.app title="Hafalan & Murojaah" description="Latihan menghafal Al-Qur'an dengan sembunyikan teks dan jadwal murojaah berjarak.">
    <x-page-head title="Hafalan & Murojaah" subtitle="Sembunyikan teks, nilai hafalan, ulang sesuai jadwal" :back="route('hub.learning')" />
    <div class="stack" style="max-width:900px">
        @if ($stats)
            <section class="stats" aria-label="Ringkasan hafalan">
                <div class="stat"><b>{{ $stats['memorized'] }}</b><span>ayat hafal</span></div>
                <div class="stat"><b>{{ $stats['learning'] }}</b><span>sedang dihafal</span></div>
                <div class="stat"><b>{{ $stats['due'] }}</b><span>perlu murojaah hari ini</span></div>
                <div class="stat"><b>{{ number_format(($stats['memorized'] / $stats['verses_total']) * 100, 1, ',', '.') }}%</b><span>dari 6.236 ayat</span></div>
            </section>
        @else
            <section class="card card--flat">
                <div class="section-head"><h2>Simpan progres hafalan</h2></div>
                <p class="muted" style="margin-bottom:12px">Latihan dapat dipakai tanpa akun. Masuk untuk menyimpan penilaian ayat dan mendapatkan jadwal murojaah otomatis.</p>
                <div class="form-actions"><a href="{{ route('login') }}" class="btn btn--sm">Masuk</a><a href="{{ route('register') }}" class="btn btn--outline btn--sm">Daftar gratis</a></div>
            </section>
        @endif

        <form method="get" action="{{ route('hafalan') }}" class="card form" data-hafalan-start>
            <h2>Mulai hafalan</h2>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="h-surah">Surah</label>
                    <select id="h-surah" class="select" data-hafalan-surah>
                        @foreach ($surahs as $s)<option value="{{ $s->number }}" data-ayat="{{ $s->ayah_count }}" @selected($s->number === 78)>{{ $s->number }}. {{ $s->name_latin }} ({{ $s->ayah_count }} ayat)</option>@endforeach
                    </select></div>
                <div class="form-grid form-grid--2">
                    <div class="field"><label for="h-dari">Dari ayat</label><input id="h-dari" type="number" min="1" class="input" value="1" data-hafalan-from></div>
                    <div class="field"><label for="h-sampai">Sampai ayat</label><input id="h-sampai" type="number" min="1" class="input" placeholder="akhir" data-hafalan-to></div>
                </div>
            </div>
            <div class="form-actions"><button class="btn"><x-icon name="repeat" />Mulai latihan</button></div>
            <p class="small muted" style="margin:0">Tips: mulai dari surah pendek (Juz 30), 3–5 ayat per hari, lalu ulangi ayat yang dijadwalkan.</p>
        </form>

        @if ($perSurah->isNotEmpty())
            <section class="card" aria-labelledby="progres-surah">
                <div class="section-head"><h2 id="progres-surah">Progres per surah</h2></div>
                <ul class="mem-list">
                    @foreach ($perSurah as $row)
                        @php $pct = $row->ayah_count ? round($row->memorized / $row->ayah_count * 100) : 0; @endphp
                        <li>
                            <div class="mem-list__head">
                                <a href="{{ route('hafalan.practice', $row->number) }}"><b>{{ $row->number }}. {{ $row->name_latin }}</b></a>
                                <span class="small muted tabular">{{ $row->memorized }}/{{ $row->ayah_count }} hafal{{ $row->learning ? ' · '.$row->learning.' dihafal' : '' }}</span>
                            </div>
                            <div class="meter" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Hafalan {{ $row->name_latin }}"><span style="width:{{ $pct }}%"></span></div>
                            @if ($row->due)
                                <a class="btn btn--tonal btn--sm" style="margin-top:8px" href="{{ route('hafalan.practice', [$row->number, 'murojaah' => 1]) }}"><x-icon name="repeat" />Murojaah {{ $row->due }} ayat</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
