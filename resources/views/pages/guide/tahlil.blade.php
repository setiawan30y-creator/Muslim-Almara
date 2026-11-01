{{-- Tahlil: bacaan lengkap berurutan + penghitung — @2026-Alifiandra --}}
@php [$label, $desc] = $meta; @endphp
<x-layouts.app title="Tahlil Lengkap — Arab, Latin & Arti" description="Bacaan tahlil lengkap dari hadiah Al-Fatihah, surah dan ayat pilihan, tahlil, tasbih, shalawat, sampai doa tahlil, dengan teks Arab, Latin, arti, dan audio.">
    <x-page-head title="Tahlil" :subtitle="$desc" :back="route('hub.worship')" />
    <div class="stack" style="max-width:860px" data-tahlil>
        @if ($items->isEmpty())
            <div class="card">
                <x-empty icon="beads" title="Bacaan tahlil sedang ditinjau" text="Setiap bacaan wajib ditinjau guru/ahli sebelum tampil. Bacaan menunggu review di panel admin." />
                @can('content.review')
                    <p style="text-align:center;margin:0"><a class="btn btn--sm" href="{{ route('admin.content.index', ['panduan', 'status' => 'review']) }}">Tinjau sekarang</a></p>
                @endcan
            </div>
        @else
            <div class="alert" role="note"><x-icon name="info" /><div>Susunan tahlil yang lazim di Indonesia. Urutan dan jumlah bacaan dapat berbeda antarmajelis; amalan ini termasuk tradisi yang diperselisihkan sebagian ulama — ikuti bimbingan guru Anda.</div></div>
            <div class="reader-tools no-print" role="toolbar" aria-label="Pengaturan bacaan tahlil">
                <label for="tahlil-jump" class="sr-only">Lompat ke bacaan</label>
                <select id="tahlil-jump" class="select" style="min-height:38px;border-radius:99px;max-width:220px" data-tahlil-jump>
                    @foreach ($items as $it)<option value="{{ $it->slug }}">{{ $it->title }}</option>@endforeach
                </select>
                <span class="grow"></span>
                <label class="switch"><input type="checkbox" data-toggle-latin checked><span class="switch__track"></span><span class="small">Latin</span></label>
                <button type="button" class="chip" data-tahlil-reset><x-icon name="reset" />Ulang hitungan</button>
            </div>

            @foreach ($items as $it)
                <section class="card tahlil-step" id="{{ $it->slug }}" aria-labelledby="h-{{ $it->slug }}">
                    <div class="section-head" style="margin-bottom:4px">
                        <h2 id="h-{{ $it->slug }}" style="margin:0">{{ $it->title }}</h2>
                        @if ($it->count_target && $it->count_target > 1)
                            <button type="button" class="count-btn" data-count-target="{{ $it->count_target }}" aria-label="Hitung bacaan {{ $it->title }}">
                                <b data-count>0</b><span>/{{ $it->count_target }}</span>
                            </button>
                        @endif
                    </div>
                    @if ($it->body && $it->body !== $it->title)<p class="small muted" style="margin:0 0 8px">{{ $it->body }}</p>@endif
                    @if (isset($verses[$it->id]) && $verses[$it->id]->isNotEmpty())
                        <x-verse-block :verses="$verses[$it->id]" :latin="$it->quran_latin" :audio-ref="$it->audio_ref" />
                    @endif
                    @if ($it->arabic_text)
                        <div class="tahlil-reading">
                            @if (! $it->show_verses && $it->audio_ref && config('almara.audio.islamic_network.enabled'))
                                <div class="verse-block__head"><span class="badge badge--neutral">QS {{ $it->audio_ref }}</span>
                                    <button type="button" class="icon-btn" data-ayat-audio="{{ $it->audio_ref }}" aria-label="Dengarkan" title="Dengarkan"><x-icon name="play" class="i-play" /><x-icon name="pause" class="i-pause" /></button></div>
                            @endif
                            <p class="arabic" lang="ar">{{ $it->arabic_text }}</p>
                            <p class="reading-card__latin">{{ $it->transliteration }}</p>
                            <p class="reading-card__tr">{{ $it->translation }}</p>
                        </div>
                    @endif
                    @if ($it->context_note)<p class="small muted" style="margin:8px 0 0"><x-icon name="info" style="width:14px;height:14px;display:inline;vertical-align:-2px" /> {{ $it->context_note }}</p>@endif
                    <p class="small muted" style="margin:8px 0 0">Rujukan: {{ $it->source_reference }}</p>
                </section>
            @endforeach
            <p class="small muted">Teks ayat & arti: Al-Qur'an Kemenag RI. @if ($source) Kompilasi: {{ $source->name }}. @endif Audio ayat dibacakan qari pilihan Anda.</p>
        @endif
    </div>
</x-layouts.app>
