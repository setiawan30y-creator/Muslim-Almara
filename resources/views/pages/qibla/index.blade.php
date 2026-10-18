{{-- Arah Kiblat — @2026-Alifiandra --}}
@php $deg = round($bearing, 1); @endphp
<x-layouts.app :loc="$loc" title="Arah Kiblat" description="Arah kiblat dari {{ $loc['nama'] }}: {{ $deg }}° dari utara sejati, dengan kompas digital.">
    <x-page-head title="Arah Kiblat" subtitle="Dari {{ $loc['nama'] }}">
        <a href="{{ route('location', ['kembali' => '/kiblat']) }}" class="btn btn--tonal btn--sm"><x-icon name="pin" />Ubah</a>
    </x-page-head>

    <div class="grid-2">
        <section class="card card--pad-lg compass-wrap" aria-labelledby="kompas-h" data-qibla="{{ $bearing }}">
            <h2 id="kompas-h" class="sr-only">Kompas kiblat</h2>
            <div class="compass">
                <span class="compass__fixed" aria-hidden="true"></span>
                <div class="compass__dial" data-dial>
                    <svg viewBox="0 0 200 200" role="img" aria-label="Kompas dengan arah kiblat {{ $deg }} derajat dari utara">
                        <g stroke="var(--color-border)" stroke-width="1">
                            @for ($i = 0; $i < 72; $i++)
                                <line x1="100" y1="{{ $i % 18 === 0 ? 8 : ($i % 2 === 0 ? 10 : 12) }}" x2="100" y2="16" transform="rotate({{ $i * 5 }} 100 100)" @if ($i % 18 === 0) stroke="var(--color-text-muted)" stroke-width="2" @endif />
                            @endfor
                        </g>
                        <g font-family="var(--font-sans)" font-weight="800" font-size="12" text-anchor="middle" fill="var(--color-text-muted)">
                            <text x="100" y="32" fill="var(--color-danger)">U</text>
                            <text x="174" y="104">T</text>
                            <text x="100" y="178">S</text>
                            <text x="26" y="104">B</text>
                        </g>
                        <g transform="rotate({{ $bearing }} 100 100)">
                            <line x1="100" y1="100" x2="100" y2="40" stroke="var(--color-primary)" stroke-width="4" stroke-linecap="round" />
                            <g transform="translate(100 36)">
                                <rect x="-11" y="-14" width="22" height="22" rx="3" fill="var(--color-text)" />
                                <rect x="-11" y="-8" width="22" height="3" fill="var(--color-accent)" />
                            </g>
                        </g>
                        <circle cx="100" cy="100" r="6" fill="var(--color-primary)" />
                    </svg>
                </div>
            </div>
            <div style="text-align:center">
                <div class="compass__deg">{{ $deg }}°</div>
                <div class="muted">dari utara sejati · {{ $label }}</div>
            </div>
            <div class="status" data-qibla-status role="status">
                <x-icon name="info" /><span>Kompas di atas statis (utara di atas). Aktifkan sensor untuk mengikuti arah perangkat.</span>
            </div>
            <button type="button" class="btn" data-qibla-start><x-icon name="compass" />Aktifkan kompas perangkat</button>
        </section>

        <div class="stack">
            <section class="card">
                <h2>Informasi</h2>
                <ul class="prayer-list">
                    <li><span class="ico"><x-icon name="compass" /></span><span class="name">Arah kiblat</span><span class="time">{{ $deg }}°</span></li>
                    <li><span class="ico"><x-icon name="kaaba" /></span><span class="name">Jarak ke Ka'bah</span><span class="time">{{ number_format($distance, 0, ',', '.') }} km</span></li>
                    <li><span class="ico"><x-icon name="pin" /></span><span class="name">Koordinat</span><span class="time small">{{ number_format($loc['lat'], 4) }}, {{ number_format($loc['lng'], 4) }}</span></li>
                </ul>
            </section>
            <section class="card card--flat">
                <h2>Tips penggunaan</h2>
                <ul class="small muted" style="padding-left:1.1rem;margin:0">
                    <li>Letakkan ponsel mendatar dan jauhkan dari benda logam atau magnet.</li>
                    <li>Jika arah tampak melompat, gerakkan ponsel membentuk angka 8 untuk kalibrasi.</li>
                    <li>Kompas ponsel menunjuk utara magnetis; selisihnya dengan utara sejati di Indonesia umumnya kecil (di bawah 2°).</li>
                    <li>Untuk akurasi terbaik, gunakan <a href="{{ route('location', ['kembali' => '/kiblat']) }}">lokasi perangkat</a> alih-alih kota.</li>
                </ul>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('[data-qibla]');
        var dial = root.querySelector('[data-dial]');
        var status = root.querySelector('[data-qibla-status] span');
        var statusBox = root.querySelector('[data-qibla-status]');
        var btn = root.querySelector('[data-qibla-start]');
        var qibla = parseFloat(root.getAttribute('data-qibla'));
        var got = false;

        function onOrient(e) {
            var heading = null;
            if (typeof e.webkitCompassHeading === 'number') heading = e.webkitCompassHeading;
            else if (e.absolute && typeof e.alpha === 'number') heading = 360 - e.alpha;
            if (heading === null) return;
            got = true;
            dial.style.transform = 'rotate(' + (-heading) + 'deg)';
            var diff = Math.abs(((qibla - heading + 540) % 360) - 180);
            var acc = typeof e.webkitCompassAccuracy === 'number' ? e.webkitCompassAccuracy : null;
            if (acc !== null && (acc < 0 || acc > 20)) {
                statusBox.className = 'status status--warn';
                status.textContent = 'Akurasi kompas rendah (±' + Math.round(Math.abs(acc)) + '°). Gerakkan ponsel membentuk angka 8 untuk kalibrasi dan jauhkan dari logam/magnet.';
                return;
            }
            statusBox.className = 'status ' + (diff <= 5 ? 'status--ok' : '');
            status.textContent = diff <= 5 ? 'Anda menghadap kiblat.' : 'Putar perangkat sampai tanda Ka\'bah berada di bawah segitiga merah (' + Math.round(diff) + '° lagi).';
        }

        function start() {
            var listen = function () {
                window.addEventListener('deviceorientationabsolute', onOrient, true);
                window.addEventListener('deviceorientation', onOrient, true);
                btn.hidden = true;
                status.textContent = 'Menunggu data sensor…';
                setTimeout(function () {
                    if (!got) { statusBox.className = 'status status--warn'; status.textContent = 'Sensor kompas tidak tersedia di perangkat ini. Gunakan angka derajat di atas dengan kompas fisik.'; btn.hidden = false; }
                }, 3000);
            };
            if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
                DeviceOrientationEvent.requestPermission().then(function (r) {
                    if (r === 'granted') listen(); else { statusBox.className = 'status status--warn'; status.textContent = 'Izin sensor ditolak.'; }
                }).catch(function () { statusBox.className = 'status status--warn'; status.textContent = 'Izin sensor tidak dapat diminta.'; });
            } else if ('DeviceOrientationEvent' in window) {
                listen();
            } else {
                statusBox.className = 'status status--warn';
                status.textContent = 'Perangkat tidak mendukung sensor orientasi.';
            }
        }
        btn.addEventListener('click', start);
        window.addEventListener('compassneedscalibration', function () {
            statusBox.className = 'status status--warn';
            status.textContent = 'Kompas perlu dikalibrasi: gerakkan ponsel membentuk angka 8.';
        });
    });
    </script>
    @endpush
</x-layouts.app>
