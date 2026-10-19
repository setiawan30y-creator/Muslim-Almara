{{-- Terjemah & Frasa — @2026-Alifiandra --}}
<x-layouts.app title="Terjemah & Frasa" description="Kumpulan frasa Arab–Indonesia–Inggris untuk ibadah dan perjalanan umroh/haji, dengan pelafalan.">
    <x-page-head title="Terjemah & Frasa" subtitle="Arab · Indonesia · Inggris" :back="route('hub.learning')" />
    <div class="stack" style="max-width:900px">
        @if ($enabled)
            <form class="card form" data-translate aria-labelledby="tr-h">
                <h2 id="tr-h">Terjemahkan teks</h2>
                <div class="toolbar" style="margin:0">
                    <label for="tr-src" class="sr-only">Dari</label>
                    <select id="tr-src" class="select" style="max-width:160px" data-tr-src>@foreach ($languages as $k => $v)<option value="{{ $k }}" @selected($k === 'id')>{{ $v }}</option>@endforeach</select>
                    <button type="button" class="icon-btn" data-tr-swap aria-label="Tukar bahasa"><x-icon name="repeat" /></button>
                    <label for="tr-tgt" class="sr-only">Ke</label>
                    <select id="tr-tgt" class="select" style="max-width:160px" data-tr-tgt>@foreach ($languages as $k => $v)<option value="{{ $k }}" @selected($k === 'ar')>{{ $v }}</option>@endforeach</select>
                </div>
                <div class="field"><label for="tr-q" class="sr-only">Teks</label>
                    <textarea id="tr-q" class="input" maxlength="{{ $maxChars }}" required placeholder="Tulis teks yang ingin diterjemahkan…" data-tr-q></textarea></div>
                <div class="form-actions"><button class="btn" data-tr-go><x-icon name="language" />Terjemahkan</button></div>
                <div class="tr-out" data-tr-out hidden>
                    <p class="tr-out__text" data-tr-text></p>
                    <div style="display:flex;gap:6px">
                        <button type="button" class="chip" data-tr-speak><x-icon name="headphones" />Dengarkan</button>
                        <button type="button" class="chip" data-tr-copy><x-icon name="copy" />Salin</button>
                    </div>
                    <p class="small muted" style="margin:8px 0 0">Terjemahan mesin — periksa kembali untuk teks penting. Jangan gunakan untuk menerjemahkan ayat Al-Qur'an; pakai terjemahan resmi di menu Al-Qur'an.</p>
                </div>
            </form>
        @endif

        <section aria-labelledby="frasa-h" class="stack" data-phrasebook>
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
