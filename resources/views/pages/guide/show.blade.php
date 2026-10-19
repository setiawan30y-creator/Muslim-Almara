{{-- Detail materi panduan — @2026-Alifiandra --}}
@php [$label] = $meta; $desc = \Illuminate\Support\Str::limit(strtok($item->body, PHP_EOL), 150); @endphp
<x-layouts.app :title="$item->title.' — '.$label" :description="$desc">
    <x-page-head :title="$item->title" :subtitle="$label.' · '.$item->category->title.($position ? ' · '.$position.'/'.$total : '')" :back="route('guide.module', $modul)" />
    <div class="stack" style="max-width:820px">
        <article class="card guide-body">
            @foreach ($item->bodyBlocks() as $block)
                @if ($block['type'] === 'list')
                    <ul>@foreach ($block['lines'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @else
                    <p>{!! nl2br(e(implode("\n", $block['lines']))) !!}</p>
                @endif
            @endforeach
            @if ($item->context_note)
                <p class="alert" style="margin:0"><x-icon name="info" />{{ $item->context_note }}</p>
            @endif
        </article>

        @if ($item->arabic_text)
            <section class="card reading-card" aria-label="Bacaan">
                <div class="section-head" style="margin-bottom:0"><h2 style="margin:0">Bacaan</h2>
                    <button type="button" class="icon-btn" data-copy="baca-{{ $item->slug }}" aria-label="Salin bacaan"><x-icon name="copy" /></button></div>
                <div id="baca-{{ $item->slug }}">
                    <p class="arabic" lang="ar">{{ $item->arabic_text }}</p>
                    <p class="reading-card__latin">{{ $item->transliteration }}</p>
                    <p class="reading-card__tr">{{ $item->translation }}</p>
                </div>
            </section>
        @endif

        @if ($verse)
            <section class="card verse" aria-labelledby="contoh-ayat">
                <div class="section-head"><h2 id="contoh-ayat">Contoh ayat</h2><span class="badge badge--neutral">QS. {{ $verse->surah->name_latin }}: {{ $verse->ayah_number }}</span></div>
                <p class="arabic" lang="ar">{{ $verse->text_uthmani }}</p>
                <p class="verse__tr">“{{ $verse->translationId?->text }}”</p>
                <a class="verse__ref" href="{{ route('quran.show', $verse->surah->number) }}#ayat-{{ $verse->ayah_number }}">Buka di Al-Qur'an →</a>
            </section>
        @endif

        @if ($item->quiz_json)
            <section class="card" data-quiz aria-labelledby="latihan">
                <div class="section-head"><h2 id="latihan">Latihan</h2><span class="small muted" data-quiz-score></span></div>
                @foreach ($item->quiz_json as $i => $q)
                    <fieldset class="quiz" data-quiz-q data-answer="{{ (int) $q['answer'] }}">
                        <legend><b>{{ $i + 1 }}. {{ $q['q'] }}</b></legend>
                        @foreach ($q['options'] as $j => $opt)
                            <button type="button" class="quiz__opt" data-opt="{{ $j }}">{{ $opt }}</button>
                        @endforeach
                        <p class="small quiz__fb" data-quiz-fb aria-live="polite"></p>
                    </fieldset>
                @endforeach
            </section>
        @endif

        <section class="card card--flat small">
            <h2>Sumber & status</h2>
            <ul class="muted" style="padding-left:1.1rem;margin:0">
                <li>{{ $item->sourceKindLabel() }} · Rujukan: {{ $item->source_reference }}</li>
                <li>Versi materi: {{ $item->content_version }} · Terbit: {{ $item->published_at?->translatedFormat('j F Y') ?? '-' }}</li>
                @if ($item->source)<li>Kompilasi: {{ $item->source->name }}</li>@endif
            </ul>
        </section>

        <nav class="pager" aria-label="Navigasi materi">
            @if ($prev)<a class="btn btn--outline" href="{{ route('guide.show', [$modul, $prev->slug]) }}"><x-icon name="chevron-left" />Sebelumnya</a>@else<span></span>@endif
            @if ($next)<a class="btn btn--tonal" href="{{ route('guide.show', [$modul, $next->slug]) }}">Berikutnya<x-icon name="chevron-right" /></a>@endif
        </nav>
    </div>
</x-layouts.app>
