{{-- Kartu bacaan: Arab + Latin + arti Indonesia + sumber + konteks (Standar Wajib Bacaan) — @2026-Alifiandra --}}
@props(['item', 'type', 'link' => true])
<article {{ $attributes->merge(['class' => 'card reading-card']) }} id="{{ $item->slug }}">
    <div class="section-head" style="margin-bottom:0">
        <h3 style="margin:0">
            @if ($link)<a href="{{ route('doa.show', [$type, $item->slug]) }}" style="color:inherit">{{ $item->title }}</a>@else{{ $item->title }}@endif
        </h3>
        <span style="display:flex;gap:4px;align-items:center">
            @if ($item->count_target)<span class="count-pill" title="Jumlah anjuran">{{ $item->count_target }}×</span>@endif
            <button type="button" class="icon-btn" data-copy="baca-{{ $item->slug }}" aria-label="Salin {{ $item->title }}"><x-icon name="copy" /></button>
        </span>
    </div>
    <div id="baca-{{ $item->slug }}">
        <p class="arabic" lang="ar">{{ $item->arabic_text }}</p>
        <p class="reading-card__latin">{{ $item->transliteration }}</p>
        <p class="reading-card__tr">{{ $item->translation }}</p>
    </div>
    @if ($item->context_note)
        <p class="small muted" style="margin:10px 0 0"><x-icon name="info" style="width:15px;height:15px;display:inline;vertical-align:-2px" /> {{ $item->context_note }}</p>
    @endif
    <div class="reading-card__meta">
        <span class="badge badge--neutral">{{ $item->sourceKindLabel() }}</span>
        <span>Sumber: {{ $item->source_reference }}</span>
        @if ($item->count_target && $item->count_target > 1)
            <a href="{{ route('tasbih', ['dzikir' => $item->slug]) }}">Hitung dengan tasbih →</a>
        @endif
    </div>
</article>
