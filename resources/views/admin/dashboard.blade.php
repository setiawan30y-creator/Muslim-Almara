{{-- Admin: ringkasan — @2026-Alifiandra --}}
<x-layouts.admin title="Ringkasan">
    <x-page-head title="Panel admin" subtitle="Konten agama hanya tampil setelah ditinjau & diterbitkan" />
    <div class="stack">
        <div class="grid-2">
            @foreach (['doa' => $doa, 'dzikir' => $dzikir] as $type => $counts)
                <section class="card">
                    <div class="section-head"><h2>{{ ucfirst($type) }}</h2><a href="{{ route('admin.content.index', $type) }}">Kelola →</a></div>
                    <table class="ledger">
                        <tbody>
                            @foreach ($statuses as $st)
                                <tr><td><x-status-badge :status="$st" /></td>
                                    <td class="num"><a href="{{ route('admin.content.index', [$type, 'status' => $st->value]) }}">{{ $counts[$st->value] ?? 0 }}</a></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>
        @if (($doa['review'] ?? 0) + ($dzikir['review'] ?? 0) > 0)
            <div class="alert alert--warning"><x-icon name="alert" /><div><b>{{ ($doa['review'] ?? 0) + ($dzikir['review'] ?? 0) }} konten menunggu review.</b> Pembaca tidak akan melihatnya sampai disetujui reviewer agama dan diterbitkan.</div></div>
        @endif
        @if ($sourcesUnreviewed)
            <div class="alert"><x-icon name="database" /><div>{{ $sourcesUnreviewed }} sumber konten belum ditandai tinjau lisensinya. <a href="{{ route('admin.sources') }}">Periksa</a></div></div>
        @endif
        <section class="card">
            <div class="section-head"><h2>Aktivitas terbaru</h2>@can('audit.view')<a href="{{ route('admin.audit') }}">Log audit →</a>@endcan</div>
            <table class="ledger ledger--cards">
                <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Objek</th></tr></thead>
                <tbody>
                    @forelse ($recent as $log)
                        <tr><td data-label="Waktu">{{ $log->created_at?->translatedFormat('j M H:i') }}</td><td data-label="Pelaku">{{ $log->actor?->name ?? 'Sistem' }}</td>
                            <td data-label="Aksi"><code>{{ $log->action }}</code></td><td data-label="Objek">{{ $log->subject_type }} {{ $log->subject_id }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <p class="small muted">Pengguna terdaftar: {{ $users }}. Data ibadah pribadi pengguna tidak ditampilkan di panel admin.</p>
    </div>
</x-layouts.admin>
