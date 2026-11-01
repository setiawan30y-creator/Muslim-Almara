{{-- Jadwal Sholat — @2026-Alifiandra --}}
@php
    $icons = ['imsak' => 'moon-star', 'subuh' => 'moon', 'terbit' => 'sunrise', 'duha' => 'sun', 'zuhur' => 'sun', 'asar' => 'sun', 'magrib' => 'sunset', 'isya' => 'moon-star'];
    $minor = ['imsak', 'terbit', 'duha'];
    $prev = $month->subMonth()->format('Y-m');
    $nextM = $month->addMonth()->format('Y-m');
    $q = $loc['custom'] ? ['lat' => $loc['lat'], 'lng' => $loc['lng'], 'tz' => $loc['tz']] : ['kota' => $loc['slug']];
    $cityPage = $cityPage ?? false;
    $link = fn (array $extra = []) => $cityPage ? route('prayer.city', array_merge(['kota' => $loc['slug']], $extra)) : route('prayer', array_merge($q, $extra));
@endphp
<x-layouts.app :loc="$loc" title="Jadwal Sholat {{ $loc['nama'] }} Hari Ini, {{ $now->translatedFormat('j F Y') }}" :canonical="$cityPage ? route('prayer.city', $loc['slug']) : null" description="Jadwal sholat {{ $loc['nama'] }} hari ini dan sebulan penuh: imsak, subuh, terbit, duha, zuhur, asar, magrib, isya.">
    <x-page-head :title="$cityPage ? 'Jadwal Sholat '.$loc['nama'] : 'Jadwal Sholat'" subtitle="{{ $loc['nama'] }} · {{ $tzLabel }} · {{ $params->methodName }}">
        <a href="{{ route('settings.prayer') }}" class="icon-btn" aria-label="Pengaturan metode"><x-icon name="sliders" /></a>
        <a href="{{ route('location', ['kembali' => '/jadwal-sholat']) }}" class="btn btn--tonal btn--sm"><x-icon name="pin" />Lokasi</a>
    </x-page-head>

    <div class="layout-side">
        <div class="stack">
            <section aria-labelledby="bulanan">
                <div class="section-head" style="flex-wrap:wrap">
                    <h2 id="bulanan">{{ $month->translatedFormat('F Y') }}</h2>
                    <div style="display:flex;gap:6px">
                        <a class="icon-btn" href="{{ $link(['bulan' => $prev]) }}" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" /></a>
                        <a class="chip" href="{{ $link() }}">Bulan ini</a>
                        <a class="icon-btn" href="{{ $link(['bulan' => $nextM]) }}" aria-label="Bulan berikutnya"><x-icon name="chevron-right" /></a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <caption class="sr-only">Jadwal sholat {{ $loc['nama'] }} bulan {{ $month->translatedFormat('F Y') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Tanggal</th>
                                @foreach (\App\Services\Prayer\PrayerTimeCalculator::NAMES as $name)
                                    <th scope="col">{{ $name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $isToday = $row['tanggal']->isSameDay($now); @endphp
                                <tr class="{{ $isToday ? 'is-today' : '' }} {{ $row['tanggal']->isFriday() ? 'is-friday' : '' }}" @if ($isToday) id="hari-ini" @endif>
                                    <td>
                                        {{ $row['tanggal']->translatedFormat('D, j M') }}
                                        <span class="muted small" style="display:block;font-weight:500">{{ $row['hijri']['day'] }} {{ $row['hijri']['month_name'] }}</span>
                                    </td>
                                    @foreach ($row['waktu'] as $t)
                                        <td>{{ $t }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small muted" style="margin-top:12px">Geser tabel ke samping untuk melihat semua waktu. Jumat ditandai warna hijau.</p>
            </section>
        </div>

        <aside class="stack">
            <section class="card" aria-labelledby="hari-ini-h">
                <div class="section-head">
                    <h2 id="hari-ini-h">Hari ini</h2>
                    <span class="badge tabular">Pukul <span data-clock="{{ $loc['tz'] }}">{{ $now->format('H:i') }}</span></span>
                </div>
                <ul class="prayer-list">
                    @foreach ($today as $key => $time)
                        @php $isNext = $key === $next['key'] && $now->format('H:i') < $time; @endphp
                        <li class="{{ in_array($key, $minor) ? 'is-minor' : '' }} {{ $isNext ? 'is-next' : '' }}">
                            <span class="ico"><x-icon :name="$icons[$key]" /></span>
                            <span class="name">{{ \App\Services\Prayer\PrayerTimeCalculator::NAMES[$key] }}</span>
                            @if ($isNext)<span class="tag" data-countdown="{{ $next['timestamp'] }}" data-prayer="{{ $next['key'] }}" data-prayer-name="{{ $next['nama'] }}"></span>@endif
                            <span class="time">{{ $time }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="adzan-actions">
                    <button type="button" class="btn btn--sm" data-adzan-play="dzuhur"><x-icon name="volume" /><span data-adzan-play-label>Putar adzan</span></button>
                    <button type="button" class="btn btn--sm btn--outline" data-adzan-play="subuh"><x-icon name="volume" /><span data-adzan-play-label>Adzan Subuh</span></button>
                    <a href="{{ route('settings.prayer') }}#suara-adzan" class="small">Atur suara adzan</a>
                </div>
            </section>
            <section class="card card--flat">
                <h2><x-icon name="info" style="width:20px;height:20px;display:inline;vertical-align:-4px;color:var(--color-primary)" /> Metode perhitungan</h2>
                <p class="small" style="margin-bottom:6px"><b>{{ $params->methodName }}</b></p>
                <ul class="small muted" style="padding-left:1.1rem;margin:0">
                    <li>{{ $params->describe() }}.</li>
                    <li>Imsak {{ $params->imsakMinutes }} menit sebelum Subuh; Duha saat matahari ±{{ $params->duhaAltitude }}°.</li>
                    <li>Lokasi: {{ $loc['nama'] }} ({{ $loc['basis'] === 'device' ? 'lokasi perangkat' : ($loc['basis'] === 'default' ? 'bawaan aplikasi' : 'dipilih manual') }}), {{ number_format($loc['lat'], 3) }}, {{ number_format($loc['lng'], 3) }}.</li>
                    @if ($params->offsets)
                        <li>Koreksi manual: {{ collect($params->offsets)->map(fn ($m, $k) => ucfirst($k).' '.($m > 0 ? '+' : '').$m.' mnt')->join(', ') }}</li>
                    @endif
                </ul>
                <p class="small muted" style="margin:10px 0 0">Hasil perhitungan bisa berbeda 1–2 menit dengan jadwal resmi daerah. Untuk ibadah, ikuti jadwal masjid atau otoritas setempat. Pengingat/notifikasi bergantung pada dukungan peramban.</p>
                <a href="{{ route('settings.prayer') }}" class="btn btn--outline btn--sm" style="margin-top:12px"><x-icon name="sliders" />Ubah metode & koreksi</a>
                <a href="{{ route('settings.prayer') }}#pengingat" class="btn btn--tonal btn--sm" style="margin-top:12px"><x-icon name="clock" />Pengingat adzan</a>
            </section>
            <section class="card">
                <h2>Ramadan & puasa</h2>
                <div class="form-actions" style="margin:0"><a class="btn btn--sm btn--outline" href="{{ route('imsakiyah') }}">Imsakiyah</a><a class="btn btn--sm btn--outline" href="{{ route('fasting') }}">Kalender puasa</a></div>
            </section>
        </aside>
    </div>
    <section class="card card--flat" style="margin-top:var(--space-5)">
        <h2>Jadwal sholat kota lain</h2>
        <div class="city-links">@foreach ($cities as $c)<a href="{{ route('prayer.city', $c['slug']) }}" @if ($cityPage && $c['slug'] === $loc['slug']) aria-current="page" @endif>{{ $c['nama'] }}</a>@endforeach</div>
    </section>
</x-layouts.app>
