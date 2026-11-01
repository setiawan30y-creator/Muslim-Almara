{{-- Rangkaian ayat dari teks Kemenag di database + transliterasi materi — @2026-Alifiandra --}}
@props(['verses', 'latin' => null, 'audioRef' => null])
@php $first = $verses->first(); $last = $verses->last(); @endphp
<div {{ $attributes->merge(['class' => 'verse-block']) }}>
    <div class="verse-block__head">
        <span class="badge badge--neutral">QS. {{ $first->surah->name_latin }}: {{ $first->ayah_number }}{{ $last->ayah_number !== $first->ayah_number ? '–'.$last->ayah_number : '' }}</span>
        @if ($audioRef && config('almara.audio.islamic_network.enabled'))
            <button type="button" class="icon-btn" data-ayat-audio="{{ $audioRef }}" aria-label="Dengarkan" title="Dengarkan"><x-icon name="play" class="i-play" /><x-icon name="pause" class="i-pause" /></button>
        @endif
    </div>
    <p class="arabic" lang="ar">@foreach ($verses as $v){{ $v->text_uthmani }} <span class="mushaf__num" aria-hidden="true">{{ strtr((string) $v->ayah_number, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']) }}</span> @endforeach</p>
    @if ($latin)<p class="reading-card__latin">{{ $latin }}</p>@endif
    <p class="reading-card__tr">@foreach ($verses as $v)<sup>{{ $v->ayah_number }}</sup> {{ $v->translationId?->text }} @endforeach</p>
</div>
