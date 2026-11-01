{{-- Imsakiyah Ramadan — @2026-Alifiandra --}}
@php $first = $rows[0]['tanggal']; $last = end($rows)['tanggal']; @endphp
<x-layouts.app :loc="$loc" title="Jadwal Imsakiyah Ramadan {{ $year }} H / {{ $first->year }} {{ $loc['nama'] }}" description="Jadwal imsak, subuh, dan berbuka puasa (magrib) Ramadan {{ $year }} H untuk {{ $loc['nama'] }}, {{ $first->translatedFormat('j F') }} – {{ $last->translatedFormat('j F Y') }}.">
    <x-page-head title="Imsakiyah Ramadan {{ $year }} H" subtitle="{{ $loc['nama'] }} · {{ $tzLabel }} · {{ $params->methodName }}" :back="route('fasting')">
        <a href="{{ route('location', ['kembali' => '/imsakiyah']) }}" class="btn btn--tonal btn--sm"><x-icon name="pin" />Lokasi</a>
    </x-page-head>
    <div class="stack">
        <p class="small muted" style="margin:0">Perkiraan 1 Ramadan: <b>{{ $first->translatedFormat('l, j F Y') }}</b> (perhitungan urfi; ikuti hasil sidang isbat pemerintah).</p>
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Imsakiyah Ramadan {{ $year }} H {{ $loc['nama'] }}</caption>
                <thead><tr><th scope="col">Ramadan</th><th scope="col">Tanggal</th><th scope="col">Imsak</th><th scope="col">Subuh</th><th scope="col">Zuhur</th><th scope="col">Asar</th><th scope="col">Magrib (buka)</th><th scope="col">Isya</th></tr></thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="{{ $r['tanggal']->isSameDay($now) ? 'is-today' : '' }}">
                            <td><b>{{ $r['ramadan'] }}</b></td>
                            <td>{{ $r['tanggal']->translatedFormat('D, j M') }}</td>
                            <td><b>{{ $r['waktu']['imsak'] }}</b></td><td>{{ $r['waktu']['subuh'] }}</td><td>{{ $r['waktu']['zuhur'] }}</td>
                            <td>{{ $r['waktu']['asar'] }}</td><td><b>{{ $r['waktu']['magrib'] }}</b></td><td>{{ $r['waktu']['isya'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <section class="card card--flat">
            <h2>Imsakiyah kota lain</h2>
            <div class="city-links">@foreach ($cities as $c)<a href="{{ route('imsakiyah.city', $c['slug']) }}" @if ($c['slug'] === $loc['slug']) aria-current="page" @endif>{{ $c['nama'] }}</a>@endforeach</div>
        </section>
    </div>
</x-layouts.app>
