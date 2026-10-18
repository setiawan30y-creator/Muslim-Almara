{{-- Pembaca surah — @2026-Alifiandra --}}
@php
    $n = $surah->number;
    $showBasmalah = ! in_array($n, [1, 9]);
    $auth = auth()->check();
    $hasAudio = (bool) $audio['current'];
@endphp
<x-layouts.app title="Surah {{ $surah->name_latin }}" description="Surah {{ $surah->name_latin }} ({{ $surah->name_translation }}) — {{ $surah->ayah_count }} ayat, {{ $surah->revelationLabel() }}.">
    <x-page-head :title="$n.'. '.$surah->name_latin" subtitle="{{ $surah->name_translation }} · {{ $surah->ayah_count }} ayat · {{ $surah->revelationLabel() }}" :back="route('quran.index')" />

    <div class="stack {{ $prefs['show_translation'] ? '' : 'is-hide-tr' }}" data-reader data-surah="{{ $n }}" data-surah-name="{{ $surah->name_latin }}"
         data-show-translation="{{ $prefs['show_translation'] ? '1' : '0' }}" data-show-tafsir="{{ $prefs['show_tafsir'] ? '1' : '0' }}">
        <section class="card card--pad-lg surah-head">
            <div class="arabic-title" lang="ar">{{ $surah->name_ar }}</div>
            <div class="muted small">Surah ke-{{ $n }} · {{ $surah->revelationLabel() }} · Juz {{ $verses->first()?->juz_number }}{{ $verses->last()?->juz_number !== $verses->first()?->juz_number ? '–'.$verses->last()?->juz_number : '' }}</div>
            @if ($showBasmalah)
                <p class="basmalah" lang="ar">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</p>
            @endif
        </section>

        <div class="reader-tools no-print" role="toolbar" aria-label="Pengaturan bacaan">
            <label for="lompat" class="sr-only">Lompat ke ayat</label>
            <select id="lompat" class="select" style="min-height:38px;border-radius:99px;max-width:140px" data-jump>
                @foreach ($verses as $v)<option value="{{ $v->ayah_number }}">Ayat {{ $v->ayah_number }}</option>@endforeach
            </select>
            <span class="grow"></span>
            <button type="button" class="icon-btn" data-reading-size="down" aria-label="Perkecil huruf Arab"><span style="font-weight:800;font-size:.85rem">A−</span></button>
            <button type="button" class="icon-btn" data-reading-size="up" aria-label="Perbesar huruf Arab"><span style="font-weight:800;font-size:1.05rem">A+</span></button>
            <button type="button" class="chip" data-pref-toggle="show_translation" aria-pressed="{{ $prefs['show_translation'] ? 'true' : 'false' }}"><x-icon name="eye" />Terjemahan</button>
            <button type="button" class="chip" data-pref-toggle="show_tafsir" aria-pressed="{{ $prefs['show_tafsir'] ? 'true' : 'false' }}">Tafsir</button>
        </div>

        @if ($prefs['reading_mode'] === 'mushaf' && ! $mushafAvailable)
            <div class="alert mode-note" role="note"><x-icon name="info" />
                <div>Mode <b>Mushaf penuh</b> akan aktif setelah aset halaman Mushaf berlisensi & terverifikasi tersedia. Sementara ditampilkan mode <b>potongan ayat</b>. <a href="{{ route('settings.quran') }}">Pengaturan</a></div>
            </div>
        @endif

        <section class="card card--list" aria-label="Ayat-ayat surah {{ $surah->name_latin }}">
            @foreach ($verses as $v)
                @php $bm = isset($bookmarked[$v->id]); @endphp
                <article class="ayah" id="ayat-{{ $v->ayah_number }}" data-verse-id="{{ $v->id }}" data-ayah="{{ $v->ayah_number }}">
                    <div class="ayah__bar">
                        <span class="ayah__num">{{ $n }}:{{ $v->ayah_number }}</span>
                        @if ($hasAudio)<button type="button" class="icon-btn" data-play-ayah="{{ $v->ayah_number }}" aria-label="Putar murattal mulai ayat {{ $v->ayah_number }}" title="Putar dari ayat ini"><x-icon name="play" /></button>@endif
                        <button type="button" class="icon-btn {{ $bm ? 'is-on' : '' }}" data-bookmark aria-pressed="{{ $bm ? 'true' : 'false' }}" aria-label="Bookmark ayat {{ $v->ayah_number }}" title="Bookmark"><x-icon name="bookmark" /></button>
                        <button type="button" class="icon-btn" data-mark-read aria-label="Tandai dibaca sampai ayat {{ $v->ayah_number }}" title="Tandai dibaca sampai sini"><x-icon name="check-circle" /></button>
                        <button type="button" class="icon-btn" data-note aria-label="Catatan untuk ayat {{ $v->ayah_number }}" title="Catatan pribadi"><x-icon name="note" /></button>
                        <button type="button" class="icon-btn" data-copy="salin-{{ $v->ayah_number }}" aria-label="Salin ayat {{ $v->ayah_number }}" title="Salin"><x-icon name="copy" /></button>
                        <button type="button" class="icon-btn" data-share="{{ route('quran.show', $n) }}#ayat-{{ $v->ayah_number }}" data-share-title="QS. {{ $surah->name_latin }}: {{ $v->ayah_number }}" data-share-text="{{ $v->translationId?->text }} (QS. {{ $surah->name_latin }}: {{ $v->ayah_number }})" aria-label="Bagikan ayat {{ $v->ayah_number }}" title="Bagikan"><x-icon name="share" /></button>
                    </div>
                    <div id="salin-{{ $v->ayah_number }}">
                        <p class="arabic" lang="ar">{{ $v->text_uthmani }}</p>
                        <p class="ayah__tr" @if (! $prefs['show_translation']) hidden @endif>{{ $v->translationId?->text }} <span class="sr-only">(QS. {{ $surah->name_latin }}: {{ $v->ayah_number }})</span></p>
                    </div>
                    @if (isset($noteCounts[$v->id]))
                        <p class="note-inline"><x-icon name="note" style="width:16px;height:16px;display:inline;vertical-align:-3px" /> {{ $noteCounts[$v->id] }} catatan pribadi · <a href="{{ route('profile.notes') }}">lihat</a></p>
                    @endif
                    @if ($v->tafsirId)
                        <details class="tafsir" @if (! $prefs['show_tafsir']) hidden @endif>
                            <summary><x-icon name="chevron-right" />Tafsir ringkas</summary>
                            <div class="tafsir__body">{{ $v->tafsirId->text }}</div>
                        </details>
                    @endif
                </article>
            @endforeach
        </section>

        <nav class="pager no-print" aria-label="Navigasi surah">
            @if ($prev)<a class="btn btn--outline" href="{{ route('quran.show', $prev->number) }}"><x-icon name="chevron-left" />{{ $prev->name_latin }}</a>@else<span></span>@endif
            @if ($next)<a class="btn btn--tonal" href="{{ route('quran.show', $next->number) }}">{{ $next->name_latin }}<x-icon name="chevron-right" /></a>@endif
        </nav>
        @if (! $hasAudio)
            <p class="small muted" style="margin:0">Audio murattal untuk surah ini belum tersedia.</p>
        @endif
        <p class="small muted">Teks: {{ $source?->name ?? 'Kemenag RI' }} · versi {{ $verses->first()?->version }}. Terjemahan & tafsir: Kementerian Agama RI.</p>
    </div>

    @if ($hasAudio)
        {{-- Pemutar murattal (blueprint §4.12 / Dua Mode: audio_mode surah|verse) --}}
        <section class="player no-print" data-audio data-surah="{{ $n }}" data-surah-name="{{ $surah->name_latin }}"
                 data-api="{{ url('/api/v1/quran/audio/surah/'.$n) }}" data-mode="{{ $prefs['audio_mode'] }}" aria-label="Pemutar murattal">
            <div class="player__row">
                <button type="button" class="player__play" data-audio-play aria-label="Putar murattal"><x-icon name="play" class="i-play" /><x-icon name="pause" class="i-pause" /></button>
                <div class="player__info">
                    <div class="player__title"><b>{{ $surah->name_latin }}</b> <span class="muted" data-audio-ayah></span></div>
                    <label for="qari" class="sr-only">Pilih qari</label>
                    <select id="qari" class="player__select" data-audio-reciter>
                        @foreach ($audio['reciters'] as $r)
                            <option value="{{ $r['key'] }}" @selected($audio['current'] === $r['key']) @disabled(! $r['available'])>{{ $r['name'] }}{{ $r['style'] ? ' · '.$r['style'] : '' }}{{ $r['available'] ? '' : ' (belum ada surah ini)' }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="icon-btn" data-audio-mode aria-pressed="{{ $prefs['audio_mode'] === 'verse' ? 'true' : 'false' }}" title="Mode per ayat" aria-label="Mode per ayat"><x-icon name="list" /></button>
                <button type="button" class="icon-btn" data-audio-repeat aria-pressed="false" title="Ulang" aria-label="Ulang"><x-icon name="repeat" /></button>
            </div>
            <div class="player__seek">
                <span class="tabular small" data-audio-cur>0:00</span>
                <input type="range" min="0" max="0" step="0.1" value="0" data-audio-seek aria-label="Posisi murattal">
                <span class="tabular small" data-audio-dur>--:--</span>
            </div>
            <p class="player__note small muted" data-audio-note hidden></p>
        </section>
    @endif

    <dialog class="dialog" data-note-dialog>
        <form method="dialog" data-note-form>
            <h2>Catatan pribadi</h2>
            <p class="small muted" data-note-ref></p>
            <div class="field">
                <label for="note-body">Catatan (hanya terlihat oleh Anda)</label>
                <textarea id="note-body" class="input" maxlength="5000" required data-note-body></textarea>
            </div>
            <div class="dialog__actions">
                <button type="button" class="btn btn--outline" data-note-cancel>Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </dialog>
</x-layouts.app>
