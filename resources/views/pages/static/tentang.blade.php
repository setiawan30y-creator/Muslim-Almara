{{-- Tentang & sumber data — @2026-Alifiandra --}}
<x-layouts.app title="Tentang & sumber data">
    <x-page-head title="Tentang {{ config('app.name') }}" subtitle="Versi {{ config('almara.version') }}" />
    <div class="stack prose" style="max-width:820px">
        <article class="card card--pad-lg">
            <p>{{ config('app.name') }} adalah pendamping Muslim pribadi untuk membaca Al-Qur'an, melihat jadwal sholat dan arah kiblat, berdzikir dan berdoa, serta mencatat progres ibadah secara privat. Fitur ibadah inti dapat digunakan gratis, tanpa akun.</p>
            <h2>Prinsip</h2>
            <ul>
                <li><b>Akurat & bersumber</b> — teks Al-Qur'an, terjemahan, doa, dan dzikir ditampilkan beserta sumber dan versinya; konten agama harus ditinjau sebelum terbit.</li>
                <li><b>Privasi sejak desain</b> — lokasi opsional dan tidak disimpan di server; progres, bookmark, dan catatan privat secara default.</li>
                <li><b>Transparan</b> — metode jadwal sholat dan dasar tanggal Hijriah selalu disebutkan.</li>
            </ul>
        </article>

        <section class="card card--pad-lg" id="sumber">
            <h2 style="margin-top:0">Sumber data & lisensi</h2>
            <div class="table-wrap">
                <table class="ledger ledger--cards">
                    <thead><tr><th>Sumber</th><th>Versi</th><th>Lisensi & atribusi</th></tr></thead>
                    <tbody>
                        @forelse ($sources as $s)
                            <tr><td data-label="Sumber"><b>{{ $s->name }}</b>@if ($s->documentation_url)<br><a class="small" href="{{ $s->documentation_url }}" target="_blank" rel="noopener">{{ parse_url($s->documentation_url, PHP_URL_HOST) }}</a>@endif</td>
                                <td data-label="Versi">{{ $s->version ?? '—' }}</td>
                                <td data-label="Lisensi" class="wrap small">{{ $s->license }}@if ($s->attribution)<br><span class="muted">{{ $s->attribution }}</span>@endif</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">Data belum diimpor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <h3>Metode jadwal sholat</h3>
            <ul class="small">@foreach ($methods as $m)<li><b>{{ $m->name }}</b> — {{ $m->source }}</li>@endforeach</ul>
            <p class="small muted">Jadwal sholat dan arah kiblat dihitung secara astronomis di server. Tanggal Hijriah memakai perhitungan aritmetika (urfi) dan dapat berbeda ±1 hari dengan penetapan resmi.</p>
        </section>

        <p class="small muted">Jika menemukan kekeliruan teks, terjemahan, atau rujukan, mohon laporkan kepada pengelola agar ditinjau ulang. Dikembangkan oleh @2026-Alifiandra.</p>
    </div>
</x-layouts.app>
