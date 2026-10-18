{{-- Daftar Surah — @2026-Alifiandra --}}
<x-layouts.app title="Al-Qur'an" description="Baca Al-Qur'an 114 surah lengkap dengan teks Arab, terjemahan, dan tafsir ringkas Kementerian Agama RI.">
    <x-page-head title="Al-Qur'an" subtitle="114 surah · teks, terjemahan & tafsir Kemenag RI" />

    <div class="stack">
        <div data-last-read="{{ url('/al-quran/__N__') }}" hidden>
            <a href="#" class="card" style="display:flex;align-items:center;gap:14px;background:var(--color-primary-soft);border-color:transparent">
                <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                <span style="min-width:0;flex:1">
                    <span class="small muted" style="display:block;font-weight:600">Terakhir dibaca</span>
                    <b data-last-read-text></b>
                </span>
                <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-primary)" />
            </a>
        </div>

        <div class="input-wrap">
            <x-icon name="search" />
            <label for="cari-surah" class="sr-only">Cari surah</label>
            <input id="cari-surah" type="search" class="input" placeholder="Cari nama surah, arti, atau nomor…" autocomplete="off" data-filter-input="surah-list">
        </div>

        <div class="card card--list">
            <ul class="list surah-grid" id="surah-list">
                @foreach ($surahs as $s)
                    <li data-filter-item data-search="{{ $s['nomor'] }} {{ $s['nama_latin'] }} {{ str_replace(['-', "'"], ['', ''], $s['nama_latin']) }} {{ $s['arti'] }}" style="display:contents">
                        <a class="list-row" href="{{ route('quran.show', $s['nomor']) }}">
                            <span class="list-row__num">{{ $s['nomor'] }}</span>
                            <span class="list-row__main">
                                <span class="list-row__title">{{ $s['nama_latin'] }}</span>
                                <span class="list-row__meta" style="display:block">{{ $s['arti'] }} · {{ $s['jumlah_ayat'] }} ayat · {{ $s['tempat_turun'] }}</span>
                            </span>
                            <span class="list-row__ar" lang="ar">{{ $s['nama'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <x-empty data-filter-empty hidden title="Surah tidak ditemukan" text="Coba kata kunci lain, misalnya “Yasin” atau “67”." />
        </div>

        <p class="small muted">Sumber teks dan terjemahan: Al-Qur'an Kementerian Agama RI (quran.kemenag.go.id), melalui dataset terbuka <a href="https://github.com/rioastamal/quran-json" rel="noopener" target="_blank">quran-json</a> (lisensi MIT).</p>
    </div>
</x-layouts.app>
