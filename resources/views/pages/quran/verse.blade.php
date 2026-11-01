{{-- Halaman satu ayat (SEO & berbagi) — @2026-Alifiandra --}}
@php
    $n = $surah->number; $a = $verse->ayah_number;
    $ref = 'QS. '.$surah->name_latin.' Ayat '.$a;
    $title = ($famous ? $famous.' — ' : '').$ref.': Arab, Latin & Arti';
    $desc = \Illuminate\Support\Str::limit(($famous ? $famous.' ('.$ref.'). ' : $ref.'. ').'Artinya: '.$verse->translationId?->text, 155);
    $jsonld = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => "Al-Qur'an", 'item' => route('quran.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Surah '.$surah->name_latin, 'item' => route('quran.show', $n)],
            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Ayat '.$a, 'item' => route('quran.verse', [$n, $a])],
        ],
    ];
@endphp
@push('head')
    <script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
<x-layouts.app :title="$title" :description="$desc" :canonical="route('quran.verse', [$n, $a])">
    <x-page-head :title="$famous ?? ('Surah '.$surah->name_latin.' Ayat '.$a)" :subtitle="$ref.' · '.$surah->name_translation.' · '.$surah->revelationLabel()" :back="route('quran.show', $n).'#ayat-'.$a" />
    <div class="stack" style="max-width:820px">
        <article class="card verse-page" data-share-card data-url="{{ route('quran.verse', [$n, $a]) }}" data-ref="{{ $ref }}" data-ar="{{ $verse->text_uthmani }}" data-tr="{{ $verse->translationId?->text }}">
            <div class="section-head">
                <span class="badge">{{ $n }}:{{ $a }}</span>
                <span style="display:flex;gap:4px">
                    @if (config('almara.audio.islamic_network.enabled'))
                        <button type="button" class="icon-btn" data-ayat-audio="{{ $n }}:{{ $a }}" aria-label="Dengarkan ayat" title="Dengarkan"><x-icon name="play" class="i-play" /><x-icon name="pause" class="i-pause" /></button>
                    @endif
                    <button type="button" class="icon-btn" data-share-image aria-label="Bagikan sebagai gambar" title="Bagikan sebagai gambar"><x-icon name="image" /></button>
                    <button type="button" class="icon-btn" data-copy="ayat-teks" aria-label="Salin ayat" title="Salin"><x-icon name="copy" /></button>
                    <button type="button" class="icon-btn" data-share="{{ route('quran.verse', [$n, $a]) }}" data-share-title="{{ $ref }}" data-share-text="{{ $verse->translationId?->text }} ({{ $ref }})" aria-label="Bagikan tautan" title="Bagikan"><x-icon name="share" /></button>
                </span>
            </div>
            <div id="ayat-teks">
                <p class="arabic" lang="ar">{{ $verse->text_uthmani }}</p>
                @if ($showLatin && $verse->text_latin)<p class="ayah__latin">{{ $verse->text_latin }}</p>@endif
                <h2 class="small muted" style="margin:12px 0 4px">Artinya</h2>
                <p class="verse__tr">“{{ $verse->translationId?->text }}” <span class="muted">({{ $ref }})</span></p>
            </div>
            @if (config('almara.audio.islamic_network.enabled'))
                <p class="small muted" style="margin:6px 0 0" data-ayat-audio-note="{{ $n }}:{{ $a }}"><x-icon name="headphones" style="width:14px;height:14px;display:inline;vertical-align:-2px" /> Tekan ▶ untuk mendengarkan bacaan qari</p>
            @endif
        </article>

        @if ($verse->tafsirId)
            <section class="card" aria-labelledby="tafsir-h">
                <h2 id="tafsir-h">Tafsir ringkas {{ $ref }}</h2>
                <div class="guide-body"><p style="white-space:pre-line">{{ $verse->tafsirId->text }}</p></div>
                <p class="small muted" style="margin:8px 0 0">Sumber: {{ $verse->tafsirId->work_name ?? 'Tafsir Kemenag RI' }}</p>
            </section>
        @endif

        <nav class="pager" aria-label="Navigasi ayat">
            @if ($prevRef)<a class="btn btn--outline" href="{{ route('quran.verse', $prevRef) }}"><x-icon name="chevron-left" />{{ $prevRef[0] }}:{{ $prevRef[1] }}</a>@else<span></span>@endif
            <a class="btn btn--ghost" href="{{ route('quran.show', $n) }}#ayat-{{ $a }}">Baca surah</a>
            @if ($nextRef)<a class="btn btn--tonal" href="{{ route('quran.verse', $nextRef) }}">{{ $nextRef[0] }}:{{ $nextRef[1] }}<x-icon name="chevron-right" /></a>@endif
        </nav>
        <p class="small muted">Teks Al-Qur'an, terjemahan & tafsir: Kementerian Agama RI ({{ $source?->name ?? 'Kemenag RI' }}).</p>
    </div>
</x-layouts.app>
