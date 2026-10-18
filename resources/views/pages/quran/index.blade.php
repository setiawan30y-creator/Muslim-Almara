{{-- Daftar surah & juz — @2026-Alifiandra --}}
<x-layouts.app title="Al-Qur'an" description="Baca Al-Qur'an 114 surah dengan teks Arab, terjemahan, dan tafsir ringkas Kementerian Agama RI.">
    <x-page-head title="Al-Qur'an" subtitle="114 surah · teks, terjemahan & tafsir Kemenag RI">
        <a href="{{ route('settings.quran') }}" class="icon-btn" aria-label="Pengaturan Al-Qur'an"><x-icon name="sliders" /></a>
    </x-page-head>

    <div class="stack">
        @if ($lastRead)
            <a href="{{ route('quran.show', $lastRead->verse->surah->number) }}#ayat-{{ $lastRead->verse->ayah_number }}" class="card" style="display:flex;align-items:center;gap:14px;background:var(--color-primary-soft);border-color:transparent">
                <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                <span style="min-width:0;flex:1"><span class="small muted" style="display:block;font-weight:600">Terakhir dibaca</span>
                    <b>QS. {{ $lastRead->verse->surah->name_latin }} · ayat {{ $lastRead->verse->ayah_number }}</b></span>
                <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-primary)" />
            </a>
        @else
            <div data-last-read="{{ url('/al-quran/__N__') }}" hidden>
                <a href="#" class="card" style="display:flex;align-items:center;gap:14px;background:var(--color-primary-soft);border-color:transparent">
                    <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                    <span style="min-width:0;flex:1"><span class="small muted" style="display:block;font-weight:600">Terakhir dibaca</span><b data-last-read-text></b></span>
                    <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-primary)" />
                </a>
            </div>
        @endif

        <form action="{{ route('quran.search') }}" method="get" role="search" class="input-wrap">
            <x-icon name="search" />
            <label for="cari-quran" class="sr-only">Cari di terjemahan atau rujukan ayat</label>
            <input id="cari-quran" name="q" type="search" class="input" placeholder="Cari kata di terjemahan, atau rujukan seperti 2:255" minlength="2" maxlength="100">
        </form>

        <div class="tabs" role="tablist" aria-label="Tampilan daftar" style="max-width:360px">
            <a role="tab" href="{{ route('quran.index') }}" aria-selected="{{ $tab === 'surah' ? 'true' : 'false' }}" class="tab-link">Surah</a>
            <a role="tab" href="{{ route('quran.index', ['tab' => 'juz']) }}" aria-selected="{{ $tab === 'juz' ? 'true' : 'false' }}" class="tab-link">Juz</a>
        </div>

        @if ($tab === 'juz')
            <div class="card card--list">
                <ul class="list surah-grid">
                    @foreach ($juz as $j)
                        <li style="display:contents">
                            <a class="list-row" href="{{ route('quran.show', $j['surah']) }}#ayat-{{ $j['ayah'] }}">
                                <span class="list-row__num">{{ $j['juz'] }}</span>
                                <span class="list-row__main"><span class="list-row__title">Juz {{ $j['juz'] }}</span>
                                    <span class="list-row__meta" style="display:block">Mulai {{ $j['name'] }} ayat {{ $j['ayah'] }}</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="input-wrap">
                <x-icon name="filter" />
                <label for="saring-surah" class="sr-only">Saring surah</label>
                <input id="saring-surah" type="search" class="input" placeholder="Saring nama surah, arti, atau nomor…" autocomplete="off" data-filter-input="surah-list">
            </div>
            <div class="card card--list">
                @if ($surahs->isEmpty())
                    <x-empty icon="book" title="Data Al-Qur'an belum diimpor" text="Jalankan: php artisan almara:quran:import" />
                @endif
                <ul class="list surah-grid" id="surah-list">
                    @foreach ($surahs as $s)
                        <li data-filter-item data-search="{{ $s->number }} {{ $s->name_latin }} {{ str_replace(['-', "'"], '', $s->name_latin) }} {{ $s->name_translation }}" style="display:contents">
                            <a class="list-row" href="{{ route('quran.show', $s->number) }}">
                                <span class="list-row__num">{{ $s->number }}</span>
                                <span class="list-row__main"><span class="list-row__title">{{ $s->name_latin }}</span>
                                    <span class="list-row__meta" style="display:block">{{ $s->name_translation }} · {{ $s->ayah_count }} ayat · {{ $s->revelationLabel() }}</span></span>
                                <span class="list-row__ar" lang="ar">{{ $s->name_ar }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <x-empty data-filter-empty hidden title="Surah tidak ditemukan" text="Coba kata kunci lain, misalnya “Yasin” atau “67”." />
            </div>
        @endif

        <section class="card" id="offline" data-quran-offline data-urls="{{ $surahs->map(fn ($s) => route('quran.show', $s->number, false))->implode(',') }}" hidden>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <div style="flex:1;min-width:200px">
                    <b>Baca tanpa internet</b>
                    <span class="small muted" style="display:block" data-qo-text>Simpan teks & terjemahan 114 surah (±20 MB) di perangkat ini.</span>
                </div>
                <button type="button" class="btn btn--tonal btn--sm" data-qo-start><x-icon name="download" />Simpan semua</button>
                <button type="button" class="btn btn--ghost btn--sm" data-qo-clear hidden><x-icon name="trash" />Hapus unduhan</button>
            </div>
            <div class="progress" style="margin-top:12px" hidden data-qo-bar><span></span></div>
        </section>

        <p class="small muted">Sumber teks, terjemahan & tafsir: Kementerian Agama RI melalui dataset terbuka quran-json (MIT). Lihat <a href="{{ route('about') }}#sumber">detail sumber & versi</a>.</p>
    </div>
</x-layouts.app>
