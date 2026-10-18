{{-- Beranda — @2026-Alifiandra --}}
@php
    $icons = ['imsak' => 'moon-star', 'subuh' => 'moon', 'terbit' => 'sunrise', 'duha' => 'sun', 'zuhur' => 'sun', 'asar' => 'sun', 'magrib' => 'sunset', 'isya' => 'moon-star'];
    $minor = ['imsak', 'terbit', 'duha'];
    $shortcuts = [
        ['prayer', 'Jadwal Sholat', 'clock', false],
        ['quran.index', "Al-Qur'an", 'book', false],
        ['qibla', 'Arah Kiblat', 'compass', false],
        ['doa', 'Doa Harian', 'hands', false],
        ['asmaul-husna', 'Asmaul Husna', 'star', true],
        ['tasbih', 'Tasbih', 'beads', false],
        ['calendar', 'Kalender Hijriah', 'calendar', false],
        ['zakat', 'Zakat', 'coins', true],
    ];
@endphp
<x-layouts.app :loc="$loc" title="Beranda">
    <div class="stack">
        <section class="hero" aria-labelledby="hero-next">
            <div class="hero__top">
                <a href="{{ route('location', ['kembali' => '/']) }}" class="hero__loc"><x-icon name="pin" />{{ $loc['nama'] }}</a>
                <span>{{ $now->translatedFormat('l, j F Y') }}</span>
            </div>
            <div class="hero__label">Sholat berikutnya</div>
            <h1 class="hero__name" id="hero-next">{{ $next['nama'] }}</h1>
            <div class="hero__time tabular">{{ $next['waktu'] }} {{ $tzLabel }}</div>
            <div class="countdown" aria-live="off"><x-icon name="clock" /><span data-countdown="{{ $next['timestamp'] }}">—</span></div>
            <div class="hero__top" style="margin-top:var(--space-5)">
                <span>{{ $hijri['label'] }}</span>
                <span class="hero__hijri" lang="ar">{{ $hijri['month_ar'] }}</span>
            </div>
        </section>

        <nav aria-label="Pintasan fitur">
            <div class="shortcuts">
                @foreach ($shortcuts as [$route, $label, $icon, $gold])
                    <a href="{{ route($route) }}" class="shortcut">
                        <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>

        <div data-last-read="{{ url('/al-quran/__N__') }}" hidden>
            <a href="#" class="card" style="display:flex;align-items:center;gap:14px">
                <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                <span style="min-width:0;flex:1">
                    <span class="small muted" style="display:block;font-weight:600">Lanjutkan membaca</span>
                    <b data-last-read-text></b>
                </span>
                <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-text-muted)" />
            </a>
        </div>

        <div class="grid-home">
            <section class="card" aria-labelledby="jadwal-hari-ini">
                <div class="section-head">
                    <h2 id="jadwal-hari-ini">Jadwal hari ini</h2>
                    <a href="{{ route('prayer') }}">Sebulan <span aria-hidden="true">→</span></a>
                </div>
                <ul class="prayer-list">
                    @foreach ($times as $key => $time)
                        @php $isNext = $key === $next['key'] && $now->format('H:i') < $time; @endphp
                        <li class="{{ in_array($key, $minor) ? 'is-minor' : '' }} {{ $isNext ? 'is-next' : '' }}">
                            <span class="ico"><x-icon :name="$icons[$key]" /></span>
                            <span class="name">{{ \App\Services\PrayerTimeService::NAMES[$key] }}</span>
                            @if ($isNext)<span class="tag">Berikutnya</span>@endif
                            <span class="time">{{ $time }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="small muted" style="margin:12px 0 0">Waktu {{ $tzLabel }} · Metode Kemenag RI. Perkiraan, bisa berbeda 1–2 menit dengan jadwal resmi setempat.</p>
            </section>

            <div class="stack">
                <section class="card verse" aria-labelledby="ayat-hari-ini">
                    <div class="section-head">
                        <h2 id="ayat-hari-ini">Ayat hari ini</h2>
                        <span class="badge badge--accent">{{ $verse['surah']['tempat_turun'] }}</span>
                    </div>
                    <p class="arabic" lang="ar">{{ $verse['ayat']['arab'] }}</p>
                    <p class="verse__tr">“{{ $verse['ayat']['arti'] }}”</p>
                    <a class="verse__ref" href="{{ route('quran.show', $verse['surah']['nomor']) }}#ayat-{{ $verse['ayat']['nomor'] }}">QS. {{ $verse['surah']['nama_latin'] }} ({{ $verse['surah']['nomor'] }}): {{ $verse['ayat']['nomor'] }} →</a>
                </section>

                <section class="card" aria-labelledby="hari-besar">
                    <div class="section-head">
                        <h2 id="hari-besar">Hari besar Islam</h2>
                        <a href="{{ route('calendar') }}">Kalender <span aria-hidden="true">→</span></a>
                    </div>
                    <ul class="events">
                        @foreach ($events as $e)
                            <li>
                                <span class="date"><b>{{ $e['tanggal']->format('j') }}</b><small>{{ $e['tanggal']->translatedFormat('M') }}</small></span>
                                <span style="min-width:0">
                                    <span class="title">{{ $e['nama'] }}</span>
                                    <span class="sub" style="display:block">{{ $e['hijri'] }} · {{ $e['sisa_hari'] === 0 ? 'hari ini' : $e['sisa_hari'].' hari lagi' }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="small muted" style="margin:8px 0 0">Tanggal perkiraan hisab; penetapan resmi mengikuti keputusan pemerintah.</p>
                </section>
            </div>
        </div>
    </div>
</x-layouts.app>
