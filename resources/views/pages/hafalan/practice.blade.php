{{-- Latihan hafalan satu surah — @2026-Alifiandra --}}
@php
    $auth = auth()->check();
    $label = fn ($st) => $st ? ($st->status === 'memorized' ? 'Hafal' : 'Dihafal') : 'Baru';
@endphp
<x-layouts.app title="Hafalan {{ $surah->name_latin }}">
    <x-page-head :title="'Hafalan · '.$surah->name_latin" :subtitle="$reviewOnly ? 'Murojaah ayat yang dijadwalkan hari ini' : 'Ayat '.$from.'–'.$to.' dari '.$surah->ayah_count" :back="route('hafalan')" />

    <div class="stack" data-hafalan @if (! $auth) data-guest @endif>
        <div class="reader-tools" role="toolbar" aria-label="Pengaturan latihan">
            <div class="segmented" role="radiogroup" aria-label="Tampilan teks">
                <label><input type="radio" name="mem-mode" value="hide" checked data-mem-mode><span>Sembunyikan</span></label>
                <label><input type="radio" name="mem-mode" value="hint" data-mem-mode><span>Kata awal</span></label>
                <label><input type="radio" name="mem-mode" value="show" data-mem-mode><span>Tampilkan</span></label>
            </div>
            <span class="grow"></span>
            <button type="button" class="chip" data-mem-tr aria-pressed="true"><x-icon name="eye" />Arti</button>
        </div>

        @if (! $auth)
            <div class="alert" role="note"><x-icon name="info" /><div>Penilaian tidak disimpan. <a href="{{ route('login') }}">Masuk</a> untuk menyimpan progres dan jadwal murojaah.</div></div>
        @endif

        @if ($verses->isEmpty())
            <div class="card"><x-empty icon="check-circle" title="Tidak ada murojaah hari ini" text="Semua ayat surah ini sudah diulang sesuai jadwal." />
                <p style="text-align:center;margin:0"><a class="btn btn--sm btn--outline" href="{{ route('hafalan.practice', $surah->number) }}">Latihan seluruh surah</a></p></div>
        @else
            <section class="card card--list" aria-label="Ayat untuk dihafal">
                @foreach ($verses as $v)
                    @php $st = $states[$v->id] ?? null; @endphp
                    <article class="ayah mem-ayah" id="ayat-{{ $v->ayah_number }}" data-verse-id="{{ $v->id }}">
                        <div class="ayah__bar">
                            <span class="ayah__num">{{ $surah->number }}:{{ $v->ayah_number }}</span>
                            <span class="badge {{ $st?->status === 'memorized' ? 'badge--success' : 'badge--neutral' }}" data-mem-status>{{ $label($st) }}</span>
                            <span class="small muted" data-mem-next>{{ $st?->next_review_on ? 'Ulang: '.($st->next_review_on->lte(today()) ? 'hari ini' : $st->next_review_on->translatedFormat('j M')) : '' }}</span>
                        </div>
                        <button type="button" class="mem-text" data-mem-reveal aria-label="Tampilkan ayat {{ $v->ayah_number }}" aria-pressed="false">
                            <span class="arabic" lang="ar" data-mem-arabic data-first="{{ \Illuminate\Support\Str::of($v->text_uthmani)->explode(' ')->first() }}">{{ $v->text_uthmani }}</span>
                        </button>
                        <p class="ayah__tr" data-mem-tr-text>{{ $v->translationId?->text }}</p>
                        <div class="mem-grades" role="group" aria-label="Nilai hafalan ayat {{ $v->ayah_number }}">
                            <button type="button" class="btn btn--sm btn--outline mem-grade mem-grade--lupa" data-grade="lupa">Lupa</button>
                            <button type="button" class="btn btn--sm btn--outline mem-grade mem-grade--ragu" data-grade="ragu">Ragu</button>
                            <button type="button" class="btn btn--sm mem-grade" data-grade="lancar">Lancar</button>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        <nav class="pager" aria-label="Navigasi surah">
            @if ($prev)<a class="btn btn--outline" href="{{ route('hafalan.practice', $prev->number) }}"><x-icon name="chevron-left" />{{ $prev->name_latin }}</a>@else<span></span>@endif
            @if ($next)<a class="btn btn--tonal" href="{{ route('hafalan.practice', $next->number) }}">{{ $next->name_latin }}<x-icon name="chevron-right" /></a>@endif
        </nav>
        <p class="small muted">Ketuk teks yang tersamar untuk melihat ayat. Nilai jujur: <b>Lupa</b> diulang besok, <b>Ragu</b> diulang lebih cepat, <b>Lancar</b> jarak pengulangan bertambah.</p>
    </div>
</x-layouts.app>
