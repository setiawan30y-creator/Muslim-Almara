{{-- Asmaul Husna — @2026-Alifiandra --}}
<x-layouts.app title="Asmaul Husna" description="99 Asmaul Husna (nama-nama Allah yang indah) beserta tulisan Arab, latin, dan artinya.">
    <x-page-head title="Asmaul Husna" subtitle="99 nama Allah yang indah" />

    <div class="stack">
        <div class="card card--flat" style="text-align:center">
            <p class="arabic" lang="ar" style="text-align:center;margin:0">وَلِلّٰهِ الْاَسْمَاۤءُ الْحُسْنٰى فَادْعُوْهُ بِهَا</p>
            <p class="small muted" style="margin:0">“Dan Allah memiliki Asma'ul-husna (nama-nama yang terbaik), maka bermohonlah kepada-Nya dengan menyebutnya.” — QS. Al-A'raf: 180</p>
        </div>

        <div class="input-wrap">
            <x-icon name="search" />
            <label for="cari-nama" class="sr-only">Cari nama</label>
            <input id="cari-nama" type="search" class="input" placeholder="Cari nama atau arti…" autocomplete="off" data-filter-input="names-list">
        </div>

        <div class="names-grid" id="names-list">
            @foreach ($items as $it)
                <div class="card name-card" data-filter-item data-search="{{ $it['nomor'] }} {{ $it['latin'] }} {{ $it['arti'] }}">
                    <span class="name-card__no">{{ $it['nomor'] }}</span>
                    <div class="name-card__ar" lang="ar">{{ $it['arab'] }}</div>
                    <div class="name-card__lt">{{ $it['latin'] }}</div>
                    <div class="name-card__mn">{{ $it['arti'] }}</div>
                </div>
            @endforeach
        </div>
        <x-empty data-filter-empty hidden title="Nama tidak ditemukan" />
    </div>
</x-layouts.app>
