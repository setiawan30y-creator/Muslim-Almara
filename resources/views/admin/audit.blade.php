{{-- Admin: log audit — @2026-Alifiandra --}}
<x-layouts.admin title="Log audit">
    <x-page-head title="Log audit" subtitle="Tidak menyimpan kata sandi, token, atau isi catatan pribadi" />
    <form method="get" class="toolbar"><div class="input-wrap grow"><x-icon name="filter" /><label for="aksi" class="sr-only">Filter aksi</label><input id="aksi" name="aksi" class="input" value="{{ $action }}" placeholder="Awalan aksi, mis. content. atau user."></div></form>
    <div class="table-wrap">
        <table class="ledger ledger--cards">
            <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Objek</th><th>Detail</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td data-label="Waktu">{{ $log->created_at?->translatedFormat('j M Y H:i') }}</td>
                        <td data-label="Pelaku">{{ $log->actor?->name ?? 'Sistem' }}</td>
                        <td data-label="Aksi"><code>{{ $log->action }}</code></td>
                        <td data-label="Objek">{{ $log->subject_type }} {{ $log->subject_id }}</td>
                        <td data-label="Detail" class="wrap small muted">{{ $log->metadata_json ? \Illuminate\Support\Str::limit(json_encode($log->metadata_json, JSON_UNESCAPED_UNICODE), 160) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada entri.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links('partials.pagination') }}
</x-layouts.admin>
