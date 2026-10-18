{{-- Admin: sumber & lisensi (blueprint §5) — @2026-Alifiandra --}}
<x-layouts.admin title="Sumber & lisensi">
    <x-page-head title="Sumber & lisensi konten" subtitle="Periksa ulang lisensi, atribusi, dan izin redistribusi sebelum produksi" />
    <div class="table-wrap">
        <table class="ledger ledger--cards">
            <thead><tr><th>Sumber</th><th>Jenis</th><th>Versi</th><th>Checksum</th><th>Lisensi & atribusi</th><th>Ditinjau</th></tr></thead>
            <tbody>
                @foreach ($sources as $s)
                    <tr>
                        <td data-label="Sumber"><b>{{ $s->name }}</b><br><code class="small">{{ $s->key }}</code>@if ($s->url)<br><a class="small" href="{{ $s->url }}" target="_blank" rel="noopener">{{ parse_url($s->url, PHP_URL_HOST) }}</a>@endif</td>
                        <td data-label="Jenis">{{ $s->content_type }}</td>
                        <td data-label="Versi">{{ $s->version ?? '—' }}</td>
                        <td data-label="Checksum"><code class="small" title="{{ $s->checksum }}">{{ $s->checksum ? substr($s->checksum, 0, 12).'…' : '—' }}</code></td>
                        <td data-label="Lisensi" class="wrap small">{{ $s->license ?? '—' }}@if ($s->attribution)<br><span class="muted">{{ $s->attribution }}</span>@endif</td>
                        <td data-label="Ditinjau">
                            @if ($s->reviewed_at){{ $s->reviewed_at->translatedFormat('j M Y') }}
                            @elseif (auth()->user()->hasPermission('settings.manage'))
                                <form method="post" action="{{ route('admin.sources.review', $s) }}" data-confirm-submit="Tandai lisensi & atribusi sumber ini telah diperiksa?">@csrf<button class="btn btn--outline btn--sm">Tandai</button></form>
                            @else <span class="badge badge--warning">Belum</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="small muted" style="margin-top:12px">Integritas dataset Al-Qur'an: jalankan <code>php artisan almara:quran:validate</code>. Ringkasan lisensi: <code>php artisan almara:content:check-licenses</code>.</p>
</x-layouts.admin>
