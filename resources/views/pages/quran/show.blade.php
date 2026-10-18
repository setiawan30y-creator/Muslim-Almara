{{-- Pembaca Surah — @2026-Alifiandra --}}
@php
    $n = $surah['nomor'];
    $showBasmalah = ! in_array($n, [1, 9]);
@endphp
<x-layouts.app title="Surah {{ $surah['nama_latin'] }}" description="Surah {{ $surah['nama_latin'] }} ({{ $surah['arti'] }}) — {{ $surah['jumlah_ayat'] }} ayat, {{ $surah['tempat_turun'] }}. Teks Arab, terjemahan, dan tafsir Kemenag RI.">
    <x-page-head :title="$n.'. '.$surah['nama_latin']" subtitle="{{ $surah['arti'] }} · {{ $surah['jumlah_ayat'] }} ayat · {{ $surah['tempat_turun'] }}" :back="route('quran.index')" />

    <div class="stack" data-reader data-surah="{{ $n }}" data-surah-name="{{ $surah['nama_latin'] }}">
        <section class="card card--pad-lg surah-head">
            <div class="arabic-title" lang="ar">{{ $surah['nama'] }}</div>
            <div class="muted small">Surah ke-{{ $n }} · {{ $surah['tempat_turun'] }}</div>
            @if ($showBasmalah)
                <p class="basmalah" lang="ar">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</p>
            @endif
        </section>

        <div class="reader-tools no-print" role="toolbar" aria-label="Pengaturan bacaan">
            <label for="lompat" class="sr-only">Lompat ke ayat</label>
            <select id="lompat" class="select grow" style="min-height:38px;border-radius:99px;max-width:180px" data-jump>
                @for ($i = 1; $i <= $surah['jumlah_ayat']; $i++)
                    <option value="{{ $i }}">Ayat {{ $i }}</option>
                @endfor
            </select>
            <span class="grow"></span>
            <button type="button" class="icon-btn" data-font="down" aria-label="Perkecil huruf Arab"><span style="font-weight:800;font-size:.85rem">A−</span></button>
            <button type="button" class="icon-btn" data-font="up" aria-label="Perbesar huruf Arab"><span style="font-weight:800;font-size:1.05rem">A+</span></button>
            <button type="button" class="chip" data-toggle-tr aria-pressed="true"><x-icon name="eye" />Terjemahan</button>
        </div>

        <section class="card card--list" aria-label="Ayat-ayat surah {{ $surah['nama_latin'] }}">
            @foreach ($surah['ayat'] as $a)
                <article class="ayah" id="ayat-{{ $a['nomor'] }}">
                    <div class="ayah__bar">
                        <span class="ayah__num">{{ $n }}:{{ $a['nomor'] }}</span>
                        <button type="button" class="icon-btn" data-mark="{{ $a['nomor'] }}" aria-label="Tandai terakhir dibaca ayat {{ $a['nomor'] }}" title="Tandai terakhir dibaca"><x-icon name="bookmark" /></button>
                        <button type="button" class="icon-btn" data-copy="salin-{{ $a['nomor'] }}" aria-label="Salin ayat {{ $a['nomor'] }}" title="Salin"><x-icon name="copy" /></button>
                        <button type="button" class="icon-btn" data-share="{{ route('quran.show', $n) }}#ayat-{{ $a['nomor'] }}" data-share-title="QS. {{ $surah['nama_latin'] }}: {{ $a['nomor'] }}" data-share-text="{{ $a['arti'] }} (QS. {{ $surah['nama_latin'] }}: {{ $a['nomor'] }})" aria-label="Bagikan ayat {{ $a['nomor'] }}" title="Bagikan"><x-icon name="share" /></button>
                    </div>
                    <div id="salin-{{ $a['nomor'] }}">
                        <p class="arabic" lang="ar">{{ $a['arab'] }}</p>
                        <p class="ayah__tr">{{ $a['arti'] }} <span class="sr-only">(QS. {{ $surah['nama_latin'] }}: {{ $a['nomor'] }})</span></p>
                    </div>
                    @if (! empty($a['tafsir']))
                        <details class="tafsir">
                            <summary><x-icon name="chevron-right" />Tafsir ringkas</summary>
                            <div class="tafsir__body">{{ $a['tafsir'] }}</div>
                        </details>
                    @endif
                </article>
            @endforeach
        </section>

        <nav class="pager no-print" aria-label="Navigasi surah">
            @if ($prev)
                <a class="btn btn--outline" href="{{ route('quran.show', $prev['nomor']) }}"><x-icon name="chevron-left" />{{ $prev['nama_latin'] }}</a>
            @else
                <span></span>
            @endif
            @if ($next)
                <a class="btn btn--tonal" href="{{ route('quran.show', $next['nomor']) }}">{{ $next['nama_latin'] }}<x-icon name="chevron-right" /></a>
            @endif
        </nav>
        <p class="small muted">Teks & terjemahan: Kementerian Agama RI. Tafsir ringkas: Tafsir Kemenag.</p>
    </div>
</x-layouts.app>
