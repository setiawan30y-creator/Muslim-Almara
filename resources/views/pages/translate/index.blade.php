{{-- Terjemah & Frasa — @2026-Alifiandra --}}
<x-layouts.app title="Terjemah & Frasa" description="Kumpulan frasa Arab–Indonesia–Inggris untuk ibadah dan perjalanan umroh/haji, dengan pelafalan.">
    <x-page-head title="Terjemah & Frasa" subtitle="Teks · percakapan suara · frasa siap pakai" :back="route('hub.learning')" />
    <div class="stack" style="max-width:900px">
        @php
            $langOpts = fn ($sel) => collect($languages)->map(fn ($n, $k) => '<option value="'.e($k).'"'.($k === $sel ? ' selected' : '').'>'.e($n).'</option>')->implode('');
        @endphp
        <div class="tabs" role="tablist" aria-label="Mode terjemah" data-tr-tabs>
            @if ($enabled)
                <button type="button" role="tab" aria-selected="true" data-tab="teks">Teks</button>
                <button type="button" role="tab" aria-selected="false" data-tab="percakapan">Percakapan</button>
            @endif
            <button type="button" role="tab" aria-selected="{{ $enabled ? 'false' : 'true' }}" data-tab="frasa">Frasa</button>
        </div>

        @if ($enabled)
            {{-- ===== Terjemah teks (mirip Google Translate) ===== --}}
            <section class="card tr" data-tab-panel="teks" data-translate data-speech='@json($speech)' aria-label="Terjemah teks">
                <div class="tr__langs">
                    <label class="sr-only" for="tr-src">Dari</label>
                    <select id="tr-src" class="select" data-tr-src>{!! $langOpts('id') !!}</select>
                    <button type="button" class="icon-btn" data-tr-swap aria-label="Tukar bahasa" title="Tukar bahasa"><x-icon name="repeat" /></button>
                    <label class="sr-only" for="tr-tgt">Ke</label>
                    <select id="tr-tgt" class="select" data-tr-tgt>{!! $langOpts('ar') !!}</select>
                </div>
                <div class="tr__panes">
                    <div class="tr__pane">
                        <label for="tr-q" class="sr-only">Teks asal</label>
                        <textarea id="tr-q" class="tr__input" maxlength="{{ $maxChars }}" placeholder="Ketik atau tekan mikrofon…" data-tr-q></textarea>
                        <div class="tr__bar">
                            <button type="button" class="icon-btn tr__mic" data-tr-mic aria-label="Bicara (masukan suara)" title="Bicara"><x-icon name="mic" /></button>
                            <button type="button" class="icon-btn" data-tr-speak-src aria-label="Dengarkan teks asal" title="Dengarkan"><x-icon name="volume" /></button>
                            <span class="grow"></span>
                            <span class="small muted tabular" data-tr-count>0/{{ $maxChars }}</span>
                            <button type="button" class="icon-btn" data-tr-clear aria-label="Hapus teks" title="Hapus"><x-icon name="x" /></button>
                        </div>
                    </div>
                    <div class="tr__pane tr__pane--out">
                        <div class="tr__output" data-tr-text aria-live="polite"><span class="muted">Terjemahan</span></div>
                        <div class="tr__bar">
                            <button type="button" class="icon-btn" data-tr-speak aria-label="Dengarkan terjemahan" title="Dengarkan"><x-icon name="volume" /></button>
                            <button type="button" class="icon-btn" data-tr-copy aria-label="Salin terjemahan" title="Salin"><x-icon name="copy" /></button>
                            <span class="grow"></span>
                            <span class="small muted" data-tr-status></span>
                        </div>
                    </div>
                </div>
                <p class="small muted" style="margin:10px 0 0">Terjemahan mesin{{ $provider ? ' ('.$provider.')' : '' }} — periksa kembali untuk teks penting. Untuk ayat Al-Qur'an gunakan terjemahan resmi di menu Al-Qur'an.</p>
            </section>

            {{-- ===== Percakapan dua arah dengan suara ===== --}}
            <section class="card convo" data-tab-panel="percakapan" hidden data-convo data-speech='@json($speech)' aria-label="Percakapan">
                <div class="convo__langs">
                    <div class="field"><label for="cv-a">Saya</label><select id="cv-a" class="select" data-cv-lang="a">{!! $langOpts('id') !!}</select></div>
                    <div class="field"><label for="cv-b">Lawan bicara</label><select id="cv-b" class="select" data-cv-lang="b">{!! $langOpts('ar') !!}</select></div>
                </div>
                <ol class="convo__log" data-cv-log aria-live="polite">
                    <li class="convo__empty muted small">Tekan tombol mikrofon lalu bicara. Terjemahan tampil dan dibacakan untuk lawan bicara.</li>
                </ol>
                <div class="convo__mics">
                    <div class="convo__side">
                        <button type="button" class="convo__mic" data-cv-mic="a"><x-icon name="mic" /><span data-cv-label="a">Indonesia</span></button>
                        <form class="convo__type" data-cv-form="a"><label class="sr-only" for="cv-ta">Ketik (saya)</label><input id="cv-ta" class="input" placeholder="atau ketik…" maxlength="500"><button class="icon-btn" aria-label="Kirim"><x-icon name="chevron-right" /></button></form>
                    </div>
                    <div class="convo__side convo__side--b">
                        <button type="button" class="convo__mic convo__mic--b" data-cv-mic="b"><x-icon name="mic" /><span data-cv-label="b">Arab</span></button>
                        <form class="convo__type" data-cv-form="b"><label class="sr-only" for="cv-tb">Ketik (lawan bicara)</label><input id="cv-tb" class="input" placeholder="atau ketik…" maxlength="500"><button class="icon-btn" aria-label="Kirim"><x-icon name="chevron-right" /></button></form>
                    </div>
                </div>
                <div class="convo__opts small">
                    <label class="check"><input type="checkbox" data-cv-autospeak checked> <span>Bacakan terjemahan otomatis</span></label>
                    <button type="button" class="btn btn--ghost btn--sm" data-cv-clear>Bersihkan</button>
                </div>
                <p class="small muted" data-cv-note style="margin:8px 0 0">Mikrofon & suara memakai fitur peramban/HP Anda (paling baik di Chrome Android/desktop). Pengenalan suara dapat diproses oleh penyedia peramban.</p>
            </section>
        @endif

        <section aria-labelledby="frasa-h" class="stack" data-phrasebook data-tab-panel="frasa" @if ($enabled) hidden @endif>
            <div class="section-head"><h2 id="frasa-h">Kumpulan frasa</h2><span class="small muted">{{ collect($phrases)->sum(fn ($c) => count($c['items'])) }} frasa · dapat dipakai offline</span></div>
            <div class="input-wrap"><x-icon name="search" /><label for="frasa-q" class="sr-only">Cari frasa</label>
                <input id="frasa-q" class="input" type="search" placeholder="Cari: toilet, terima kasih, masjid…" data-phrase-search></div>
            <div class="chips" role="group" aria-label="Kategori frasa">
                @foreach ($phrases as $cat)<a class="chip" href="#frasa-{{ $cat['slug'] }}">{{ $cat['title'] }}</a>@endforeach
            </div>
            @foreach ($phrases as $cat)
                <section class="stack" style="--gap:10px" data-phrase-group>
                    <h3 id="frasa-{{ $cat['slug'] }}" style="margin:0;scroll-margin-top:80px">{{ $cat['title'] }}</h3>
                    <div class="grid-2">
                        @foreach ($cat['items'] as $i => $p)
                            <article class="card phrase" data-phrase="{{ mb_strtolower($p['id'].' '.$p['latin'].' '.$p['arti'].' '.$p['en']) }}">
                                <div class="section-head" style="margin-bottom:0">
                                    <b>{{ $p['id'] }}</b>
                                    <span style="display:flex;gap:2px">
                                        <button type="button" class="icon-btn" data-speak="{{ $p['ar'] }}" aria-label="Dengarkan {{ $p['id'] }}"><x-icon name="headphones" /></button>
                                        <button type="button" class="icon-btn" data-copy="fr-{{ $cat['slug'] }}-{{ $i }}" aria-label="Salin {{ $p['id'] }}"><x-icon name="copy" /></button>
                                    </span>
                                </div>
                                <div id="fr-{{ $cat['slug'] }}-{{ $i }}">
                                    <p class="arabic phrase__ar" lang="ar">{{ $p['ar'] }}</p>
                                    <p class="reading-card__latin" style="margin:0">{{ $p['latin'] }}</p>
                                    <p class="small" style="margin:4px 0 0">{{ $p['arti'] }}</p>
                                </div>
                                <p class="small muted" lang="en" style="margin:2px 0 0">{{ $p['en'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
            <p class="small muted" data-phrase-empty hidden>Frasa tidak ditemukan.</p>
            <p class="small muted">{{ $source['note'] }} Pelafalan memakai suara bawaan perangkat (bila tersedia).</p>
        </section>
    </div>
</x-layouts.app>
