{{-- Pengaturan jadwal sholat (blueprint §4.2) — @2026-Alifiandra --}}
@php use App\Enums\AsrMethod; use App\Services\Prayer\PrayerTimeCalculator; @endphp
<x-layouts.app title="Pengaturan Jadwal Sholat">
    <x-page-head title="Pengaturan jadwal sholat" subtitle="Saat ini: {{ $params->methodName }}" :back="route('settings.index')" />
    <form method="post" action="{{ route('settings.prayer.save') }}" class="layout-side">
        @csrf
        <div class="card form">
            <div class="field">
                <label for="method">Metode perhitungan</label>
                <select id="method" name="method" class="select">
                    @foreach ($methods as $m)
                        <option value="{{ $m['key'] }}" @selected($current['method'] === $m['key'])>{{ $m['name'] }}</option>
                    @endforeach
                </select>
                <span class="hint">Untuk Indonesia, gunakan Kementerian Agama RI. Pilih metode yang dipakai otoritas di wilayah Anda.</span>
            </div>
            <div class="field">
                <span style="font-weight:600;font-size:.9rem">Perhitungan Asar</span>
                <div class="segmented" role="radiogroup" aria-label="Mazhab Asar">
                    @foreach (AsrMethod::cases() as $a)
                        <label><input type="radio" name="asr" value="{{ $a->value }}" @checked($current['asr'] === $a->value)><span>{{ $a->label() }}</span></label>
                    @endforeach
                </div>
            </div>
            <fieldset style="border:0;padding:0;margin:0">
                <legend style="font-weight:600;font-size:.9rem;margin-bottom:8px">Koreksi manual (menit, −30 s/d 30)</legend>
                <div class="form-grid" style="grid-template-columns:repeat(auto-fill,minmax(120px,1fr))">
                    @foreach (PrayerTimeCalculator::NAMES as $k => $label)
                        <div class="field"><label for="off-{{ $k }}">{{ $label }}</label>
                            <input id="off-{{ $k }}" type="number" name="offsets[{{ $k }}]" min="-30" max="30" step="1" class="input" value="{{ old('offsets.'.$k, $current['offsets'][$k] ?? 0) }}"></div>
                    @endforeach
                </div>
                <span class="hint">Gunakan bila jadwal masjid/otoritas setempat berbeda beberapa menit.</span>
            </fieldset>
            <div class="form-actions"><button class="btn" data-loading>Simpan</button><a href="{{ route('prayer') }}" class="btn btn--ghost">Lihat jadwal</a></div>
        </div>
        <aside class="card card--flat small">
            <h2>Parameter metode</h2>
            <ul class="muted" style="padding-left:1.1rem;margin:0">
                @foreach ($methods as $m)
                    <li style="margin-bottom:6px"><b style="color:var(--color-text)">{{ $m['name'] }}</b> — {{ $m['source'] }}</li>
                @endforeach
            </ul>
            <p class="muted" style="margin:10px 0 0">Jadwal adalah hasil perhitungan astronomis dan dapat berbeda dengan jadwal resmi. Pengingat bergantung pada izin & dukungan peramban.</p>
        </aside>
    </form>

    @php $pushKey = config('almara.push.public_key'); $pushPrayers = \App\Models\PushSubscription::PRAYERS; @endphp
    <section class="card form" id="pengingat" style="max-width:760px;margin-top:var(--space-5)" data-push
             data-key="{{ $pushKey }}" aria-labelledby="pengingat-h">
        <div class="section-head"><h2 id="pengingat-h">Pengingat adzan</h2><span class="badge badge--neutral" data-push-state>Nonaktif</span></div>
        @if (! $pushKey)
            <p class="small muted" style="margin:0">Pengingat adzan belum diaktifkan di server. Admin: jalankan <code>php artisan almara:push:keys</code>, isi kuncinya di <code>.env</code>, dan pastikan penjadwal berjalan.</p>
        @else
            <p class="small muted" style="margin-top:-6px">Notifikasi muncul di perangkat ini walau aplikasi ditutup. Memakai lokasi & metode yang sedang dipilih ({{ $params->methodName }}). Di iPhone, pasang aplikasi ke Layar Utama terlebih dahulu (iOS 16.4+).</p>
            <fieldset style="border:0;padding:0;margin:0">
                <legend class="small" style="font-weight:700;margin-bottom:6px">Ingatkan saya untuk</legend>
                <div role="group" style="display:flex;flex-wrap:wrap;gap:8px 16px">
                    @foreach ($pushPrayers as $k => $label)
                        <label class="check"><input type="checkbox" value="{{ $k }}" data-push-prayer @checked($k !== 'imsak')> <span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="field" style="max-width:260px"><label for="push-before">Waktu pengingat</label>
                <select id="push-before" class="select" data-push-before>
                    <option value="0">Tepat waktu adzan</option><option value="5">5 menit sebelumnya</option><option value="10">10 menit sebelumnya</option>
                    <option value="15">15 menit sebelumnya</option><option value="30">30 menit sebelumnya</option>
                </select></div>
            <div class="form-actions">
                <button type="button" class="btn" data-push-on><x-icon name="check" />Aktifkan / simpan</button>
                <button type="button" class="btn btn--outline" data-push-test hidden>Kirim uji</button>
                <button type="button" class="btn btn--ghost" data-push-off hidden>Matikan</button>
            </div>
            <p class="small muted" data-push-msg aria-live="polite" style="margin:0"></p>
        @endif
    </section>
</x-layouts.app>
