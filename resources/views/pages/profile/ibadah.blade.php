{{-- Tracker ibadah harian — privat, tanpa papan peringkat — @2026-Alifiandra --}}
@php
    use App\Services\Worship\WorshipTracker as WT;
    $isToday = $date->isSameDay($today);
    $s = $summary;
@endphp
<x-layouts.app title="Tracker ibadah harian" :noindex="true">
    <x-page-head title="Tracker ibadah harian" subtitle="Privat — hanya untuk Anda, tanpa peringkat" :back="route('profile')" />
    <div class="stack" style="max-width:820px">
        <section class="stats" aria-label="Ringkasan 30 hari">
            <div class="stat"><b>{{ $s['streak'] }}</b><span>hari 5 waktu berturut-turut</span></div>
            <div class="stat"><b>{{ $s['complete_days'] }}/{{ $s['days'] }}</b><span>hari lengkap (30 hari)</span></div>
            <div class="stat"><b>{{ $s['jamaah'] }}</b><span>sholat berjamaah</span></div>
            <div class="stat"><b>{{ $s['habits']['tilawah'] }}</b><span>hari tilawah</span></div>
        </section>

        <nav class="week-pills" aria-label="Pilih hari">
            @foreach ($week as $d)
                <a href="{{ route('profile.worship', $d->isSameDay($today) ? [] : ['tanggal' => $d->toDateString()]) }}" @if ($d->isSameDay($date)) aria-current="date" @endif>
                    <span>{{ $d->translatedFormat('D') }}</span><b>{{ $d->format('j') }}</b>
                </a>
            @endforeach
        </nav>

        <form method="post" action="{{ route('profile.worship.save') }}" class="card form" data-worship-form>
            @csrf
            <input type="hidden" name="tanggal" value="{{ $date->toDateString() }}">
            <h2>{{ $isToday ? 'Hari ini' : $date->translatedFormat('l') }}, {{ $date->translatedFormat('j F Y') }}</h2>

            <h3 class="small muted" style="margin:6px 0 8px;text-transform:uppercase;letter-spacing:.06em">Sholat fardhu</h3>
            <div class="prayer-track">
                @foreach (WT::PRAYERS as $k => $label)
                    <fieldset class="prayer-track__row">
                        <legend>{{ $label }}</legend>
                        <div class="seg">
                            @foreach (WT::PRAYER_STATES as $v => $vl)
                                <label><input type="radio" name="prayers[{{ $k }}]" value="{{ $v }}" @checked(($data['prayers'][$k] ?? null) === $v)><span>{{ $vl }}</span></label>
                            @endforeach
                            <label><input type="radio" name="prayers[{{ $k }}]" value="" @checked(($data['prayers'][$k] ?? null) === null)><span>Belum</span></label>
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <h3 class="small muted" style="margin:16px 0 8px;text-transform:uppercase;letter-spacing:.06em">Amalan harian</h3>
            <div class="habit-grid">
                @foreach (WT::HABITS as $k => [$label, $icon])
                    <label class="habit"><input type="hidden" name="habits[{{ $k }}]" value="0"><input type="checkbox" name="habits[{{ $k }}]" value="1" @checked($data['habits'][$k] ?? false)>
                        <span><x-icon :name="$icon" />{{ $label }}</span></label>
                @endforeach
            </div>

            <div class="form-actions"><button class="btn" data-loading>Simpan</button><span class="small muted" data-worship-status aria-live="polite"></span></div>
        </form>

        <section class="card" aria-labelledby="grid-h">
            <h2 id="grid-h">30 hari terakhir</h2>
            <div class="heat" role="list">
                @foreach ($s['grid'] as $g)
                    @php $lvl = $g['score'] === null ? 0 : ($g['score'] >= 85 ? 4 : ($g['score'] >= 60 ? 3 : ($g['score'] >= 30 ? 2 : 1))); @endphp
                    <a role="listitem" class="heat__cell heat__cell--{{ $lvl }}" href="{{ route('profile.worship', $g['date']->isSameDay($today) ? [] : ['tanggal' => $g['date']->toDateString()]) }}"
                       title="{{ $g['date']->translatedFormat('j M') }}: {{ $g['score'] === null ? 'belum dicatat' : $g['score'].'%' }}"><span class="sr-only">{{ $g['date']->translatedFormat('j F') }} {{ $g['score'] ?? '-' }}</span></a>
                @endforeach
            </div>
            <p class="small muted" style="margin:10px 0 0">Warna lebih tua = catatan lebih lengkap. Amalan sunnah yang paling sering: 
                {{ collect($s['habits'])->sortDesc()->filter()->keys()->take(3)->map(fn ($k) => WT::HABITS[$k][0])->join(', ') ?: '—' }}.</p>
        </section>

        <p class="small muted">Catatan ini untuk muhasabah pribadi. Anda dapat mengisi hingga {{ WT::BACK_DAYS }} hari ke belakang, mengekspor, atau menghapusnya bersama akun di <a href="{{ route('profile.data') }}">Data &amp; privasi</a>.</p>
    </div>
</x-layouts.app>
