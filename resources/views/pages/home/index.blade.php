{{-- Beranda / dashboard (blueprint §8.3) — @2026-Alifiandra --}}
@php
    $icons = ['imsak' => 'moon-star', 'subuh' => 'moon', 'terbit' => 'sunrise', 'duha' => 'sun', 'zuhur' => 'sun', 'asar' => 'sun', 'magrib' => 'sunset', 'isya' => 'moon-star'];
    $minor = ['imsak', 'terbit', 'duha'];
    $hidden = array_flip($cards['hidden']);
    $user = auth()->user();
    $hour = (int) $now->format('G');
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $actions = [
        ['quran.index', "Al-Qur'an", 'book', false],
        ['qibla', 'Kiblat', 'compass', false],
        ['doa', 'Dzikir & Doa', 'hands', false],
        ['prayer', 'Jadwal Sholat', 'clock', false],
        ['tasbih', 'Tasbih', 'beads', false],
        ['calendar', 'Kalender', 'calendar', false],
        ['asmaul-husna', 'Asmaul Husna', 'star', true],
        ['zakat', 'Zakat', 'coins', true],
    ];
@endphp
<x-layouts.app :loc="$loc" title="Beranda">
    <div class="stack">
        <div class="card install-card" data-install-card hidden>
            <img src="{{ asset('icons/icon-192.png') }}" alt="" width="48" height="48">
            <div class="grow">
                <b style="display:block">Pasang {{ config('app.name') }}</b>
                <span class="small muted" data-install-hint>Buka lebih cepat dari layar utama dan tetap bisa dibaca saat offline.</span>
            </div>
            <button type="button" class="btn btn--sm" data-install-action>Pasang</button>
            <button type="button" class="icon-btn" data-install-dismiss aria-label="Tutup"><x-icon name="x" /></button>
        </div>

        <div class="home-grid">
        @foreach ($cards['order'] as $card)
            @continue(isset($hidden[$card]))

            @if ($card === 'next_prayer')
                <section class="hero span-all" aria-labelledby="hero-next">
                    <div class="hero__top">
                        <span><b>Assalamu'alaikum{{ $user ? ', '.\Illuminate\Support\Str::of($user->name)->before(' ') : '' }}</b> · {{ $greet }}</span>
                        <a href="{{ route('location', ['kembali' => '/']) }}" class="hero__loc"><x-icon name="pin" />{{ $loc['nama'] }}</a>
                    </div>
                    <div class="hero__label">Sholat berikutnya</div>
                    <h1 class="hero__name" id="hero-next">{{ $next['nama'] }}</h1>
                    <div class="hero__time tabular">{{ $next['waktu'] }} {{ $tzLabel }}</div>
                    <div class="countdown"><x-icon name="clock" /><span data-countdown="{{ $next['timestamp'] }}">—</span></div>
                    <div class="hero__top" style="margin-top:var(--space-5)">
                        <span>{{ $now->translatedFormat('l, j F Y') }} · {{ $hijri['label'] }}</span>
                        <a href="{{ route('prayer') }}" class="hero__loc">Jadwal lengkap →</a>
                    </div>
                </section>

                <section class="card" aria-labelledby="jadwal-hari-ini">
                    <div class="section-head">
                        <h2 id="jadwal-hari-ini">Jadwal hari ini</h2>
                        <a href="{{ route('settings.prayer') }}" class="small">{{ $params->methodName }}</a>
                    </div>
                    <ul class="prayer-list">
                        @foreach ($times as $key => $time)
                            @php $isNext = $key === $next['key'] && $now->format('H:i') < $time; @endphp
                            <li class="{{ in_array($key, $minor) ? 'is-minor' : '' }} {{ $isNext ? 'is-next' : '' }}">
                                <span class="ico"><x-icon :name="$icons[$key]" /></span>
                                <span class="name">{{ \App\Services\Prayer\PrayerTimeCalculator::NAMES[$key] }}</span>
                                @if ($isNext)<span class="tag">Berikutnya</span>@endif
                                <span class="time">{{ $time }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="small muted" style="margin:12px 0 0">Waktu {{ $tzLabel }} · {{ $params->describe() }} · Lokasi: {{ $loc['basis'] === 'device' ? 'perangkat' : ($loc['basis'] === 'default' ? 'bawaan aplikasi' : 'dipilih manual') }}. Perkiraan; ikuti jadwal resmi setempat.</p>
                </section>

            @elseif ($card === 'weather' && config('almara.weather.enabled'))
                <section class="card weather" data-weather data-api="{{ url('/api/v1/weather') }}" data-loc="{{ $loc['lat'] }},{{ $loc['lng'] }}" aria-labelledby="cuaca-h" aria-busy="true">
                    <div class="section-head">
                        <h2 id="cuaca-h">Cuaca hari ini</h2>
                        <a href="{{ route('location', ['kembali' => '/']) }}" class="small"><x-icon name="pin" style="width:14px;height:14px;display:inline;vertical-align:-2px" /> {{ $loc['nama'] }}</a>
                    </div>
                    <div class="weather__now">
                        <span class="weather__ico" data-wx-ico aria-hidden="true"><x-icon name="cloud" /></span>
                        <div class="weather__main">
                            <b class="weather__temp tabular" data-wx-temp>--°</b>
                            <span data-wx-label>Memuat cuaca…</span>
                        </div>
                        <ul class="weather__meta small muted" data-wx-meta></ul>
                    </div>
                    <ol class="weather__hours" data-wx-hours aria-label="Prakiraan per jam"></ol>
                    <p class="small muted weather__note" data-wx-note></p>
                    <template data-wx-icons>@foreach (['sun', 'moon', 'cloud', 'cloud-sun', 'rain', 'storm', 'fog', 'droplet', 'wind'] as $wi)<span data-i="{{ $wi }}"><x-icon :name="$wi" /></span>@endforeach</template>
                </section>

            @elseif ($card === 'quick_actions')
                <nav aria-label="Aksi cepat" class="span-all">
                    <div class="shortcuts">
                        @foreach ($actions as [$route, $label, $icon, $gold])
                            <a href="{{ route($route) }}" class="shortcut">
                                <span class="shortcut__ico {{ $gold ? 'shortcut__ico--gold' : '' }}"><x-icon :name="$icon" /></span>{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </nav>

            @elseif ($card === 'continue_reading')
                @if ($lastRead)
                    <a href="{{ route('quran.show', $lastRead->verse->surah->number) }}#ayat-{{ $lastRead->verse->ayah_number }}" class="card" style="display:flex;align-items:center;gap:14px">
                        <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                        <span style="min-width:0;flex:1"><span class="small muted" style="display:block;font-weight:600">Lanjutkan bacaan</span>
                            <b>QS. {{ $lastRead->verse->surah->name_latin }} · ayat {{ $lastRead->verse->ayah_number }}</b></span>
                        <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-text-muted)" />
                    </a>
                @else
                    <div data-last-read="{{ url('/al-quran/__N__') }}" hidden>
                        <a href="#" class="card" style="display:flex;align-items:center;gap:14px">
                            <span class="shortcut__ico" style="flex:none"><x-icon name="bookmark" /></span>
                            <span style="min-width:0;flex:1"><span class="small muted" style="display:block;font-weight:600">Lanjutkan bacaan</span><b data-last-read-text></b></span>
                            <x-icon name="chevron-right" style="width:22px;height:22px;color:var(--color-text-muted)" />
                        </a>
                    </div>
                @endif

            @elseif ($card === 'reading_goal')
                @if ($stats)
                    <section class="card" aria-labelledby="target-h">
                        <div class="section-head"><h2 id="target-h">Target tilawah</h2><a href="{{ route('profile.progress') }}">Atur <span aria-hidden="true">→</span></a></div>
                        @if ($stats['target'])
                            <p style="margin-bottom:8px"><b class="tabular">{{ $stats['today'] }}</b> dari {{ $stats['target'] }} ayat hari ini</p>
                            <div class="meter" role="progressbar" aria-valuenow="{{ $stats['target_percent'] }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $stats['target_percent'] }}%"></span></div>
                        @else
                            <p class="muted" style="margin:0">Belum ada target harian. <a href="{{ route('profile.progress') }}">Buat target</a>.</p>
                        @endif
                        <p class="small muted" style="margin:10px 0 0">Khatam: {{ $stats['khatam_percent'] }}% · Hari berturut-turut: {{ $stats['streak'] }}</p>
                    </section>
                @elseif (! $user)
                    <section class="card card--flat">
                        <div class="section-head"><h2>Target tilawah</h2></div>
                        <p class="muted" style="margin-bottom:12px">Masuk untuk mencatat progres bacaan, bookmark, dan catatan pribadi yang tersinkron antar perangkat.</p>
                        <div class="form-actions"><a href="{{ route('login') }}" class="btn btn--sm">Masuk</a><a href="{{ route('register') }}" class="btn btn--outline btn--sm">Daftar gratis</a></div>
                    </section>
                @endif

            @elseif ($card === 'verse_of_day' && $verse)
                <section class="card verse" aria-labelledby="ayat-hari-ini">
                    <div class="section-head">
                        <h2 id="ayat-hari-ini">Ayat hari ini</h2>
                        <span class="badge badge--neutral">{{ $verse->surah->revelationLabel() }}</span>
                    </div>
                    <p class="arabic" lang="ar">{{ $verse->text_uthmani }}</p>
                    <p class="verse__tr">“{{ $verse->translationId?->text }}”</p>
                    <a class="verse__ref" href="{{ route('quran.show', $verse->surah->number) }}#ayat-{{ $verse->ayah_number }}">{{ $verse->reference() }} →</a>
                    <p class="small muted" style="margin:8px 0 0">Terjemahan: {{ $verse->translationId?->translator }}</p>
                </section>

            @elseif ($card === 'events')
                <section class="card" aria-labelledby="hari-besar">
                    <div class="section-head"><h2 id="hari-besar">Hari besar Islam</h2><a href="{{ route('calendar') }}">Kalender <span aria-hidden="true">→</span></a></div>
                    <ul class="events">
                        @foreach ($events as $e)
                            <li>
                                <span class="date"><b>{{ $e['tanggal']->format('j') }}</b><small>{{ $e['tanggal']->translatedFormat('M') }}</small></span>
                                <span style="min-width:0"><span class="title">{{ $e['nama'] }}</span>
                                    <span class="sub" style="display:block">{{ $e['hijri'] }} · {{ $e['sisa_hari'] === 0 ? 'hari ini' : $e['sisa_hari'].' hari lagi' }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="small muted" style="margin:8px 0 0">Tanggal hasil perhitungan (urfi); penetapan resmi mengikuti keputusan pemerintah.</p>
                </section>

            @elseif ($card === 'journal' && $user)
                <section class="card" aria-labelledby="jurnal-h">
                    <div class="section-head"><h2 id="jurnal-h">Catatan pribadi</h2><a href="{{ route('profile.notes') }}">Semua <span aria-hidden="true">→</span></a></div>
                    @forelse ($notes as $n)
                        <p style="margin-bottom:10px"><span class="small muted">{{ $n->verse ? 'QS. '.$n->verse->surah->name_latin.': '.$n->verse->ayah_number : 'Catatan umum' }}</span><br>{{ \Illuminate\Support\Str::limit($n->body, 140) }}</p>
                    @empty
                        <p class="muted" style="margin:0">Belum ada catatan. Tambahkan catatan tadabur dari halaman ayat.</p>
                    @endforelse
                </section>
            @endif
        @endforeach
        </div>

        <p class="small muted" style="text-align:center"><a href="{{ route('settings.home') }}">Atur kartu beranda</a></p>
    </div>
</x-layouts.app>
