{{-- Doa Harian — @2026-Alifiandra --}}
<x-layouts.app title="Doa Harian" description="Kumpulan doa harian dan doa dari Al-Qur'an dengan teks Arab, latin, terjemahan, dan sumber dalil.">
    <x-page-head title="Doa Harian" subtitle="{{ $items->count() }} doa pilihan beserta sumber dalilnya" />

    <div class="stack">
        <div class="input-wrap">
            <x-icon name="search" />
            <label for="cari-doa" class="sr-only">Cari doa</label>
            <input id="cari-doa" type="search" class="input" placeholder="Cari doa, misalnya “makan” atau “tidur”…" autocomplete="off" data-filter-input="doa-list">
        </div>
        <div class="chips" role="group" aria-label="Kategori doa">
            <button type="button" class="chip" aria-pressed="true" data-filter-chip="" data-filter-target="doa-list">Semua</button>
            @foreach ($categories as $key => $label)
                <button type="button" class="chip" aria-pressed="false" data-filter-chip="{{ $key }}" data-filter-target="doa-list">{{ $label }}</button>
            @endforeach
        </div>

        <div class="grid-2" id="doa-list">
            @foreach ($items as $d)
                <article class="card doa-card" id="{{ $d['slug'] }}" data-filter-item data-cat="{{ $d['kategori'] }}" data-search="{{ $d['judul'] }} {{ $d['arti'] }} {{ $d['kategori_label'] }}">
                    <div class="section-head" style="margin-bottom:0">
                        <h2 style="font-size:1.05rem">{{ $d['judul'] }}</h2>
                        <button type="button" class="icon-btn" data-copy="doa-{{ $d['id'] }}" aria-label="Salin doa {{ $d['judul'] }}"><x-icon name="copy" /></button>
                    </div>
                    <span class="badge">{{ $d['kategori_label'] }}</span>
                    <div id="doa-{{ $d['id'] }}">
                        <p class="arabic" lang="ar">{{ $d['arab'] }}</p>
                        <p class="doa-card__latin">{{ $d['latin'] }}</p>
                        <p style="margin:0">{{ $d['arti'] }}</p>
                    </div>
                    <p class="doa-card__src">Sumber: {{ $d['sumber'] }}</p>
                </article>
            @endforeach
        </div>
        <x-empty data-filter-empty hidden title="Doa tidak ditemukan" text="Coba kata kunci lain." />
    </div>
</x-layouts.app>
