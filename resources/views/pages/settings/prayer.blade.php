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
</x-layouts.app>
