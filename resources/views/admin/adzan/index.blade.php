{{-- Admin: suara adzan — @2026-Alifiandra --}}
@php $u = auth()->user(); @endphp
<x-layouts.admin title="Suara adzan">
    <x-page-head title="Suara adzan" subtitle="Unggah rekaman milik sendiri atau berizin → dengarkan → setujui. Rekaman aktif muncul di Pengaturan → Jadwal Sholat." />
    <div class="layout-side">
        <div class="stack">
            <div class="table-wrap">
                <table class="ledger ledger--cards">
                    <thead><tr><th>Nama</th><th>Jenis</th><th>Durasi</th><th>Izin</th><th>Status</th><th>Dengarkan</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($sounds as $s)
                            <tr>
                                <td data-label="Nama" class="wrap"><b>{{ $s->name }}</b><br><span class="small muted">oleh {{ $s->creator?->name ?? '—' }}@if ($s->approver) · disetujui {{ $s->approver->name }}@endif</span></td>
                                <td data-label="Jenis">{{ $s->kind === 'subuh' ? 'Subuh' : 'Umum' }}</td>
                                <td data-label="Durasi" class="num">{{ $s->duration_seconds ? gmdate('i:s', $s->duration_seconds) : '—' }}</td>
                                <td data-label="Izin" class="wrap small">{{ $s->license_note }}</td>
                                <td data-label="Status"><span class="badge {{ $s->status === 'published' ? 'badge--success' : 'badge--neutral' }}">{{ \App\Models\AdzanSound::STATUSES[$s->status] ?? $s->status }}</span></td>
                                <td data-label="Dengarkan"><audio controls preload="none" src="{{ route('adzan.audio', $s) }}" style="max-width:220px"></audio></td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        @if ($s->status !== 'published' && $u->hasPermission('content.publish'))
                                            <form method="post" action="{{ route('admin.adzan.transition', [$s, 'approve']) }}">@csrf<button class="btn btn--sm">Setujui</button></form>
                                        @endif
                                        @if ($s->status === 'published' && $u->hasPermission('content.publish'))
                                            <form method="post" action="{{ route('admin.adzan.transition', [$s, 'deactivate']) }}">@csrf<button class="btn btn--outline btn--sm">Nonaktifkan</button></form>
                                        @endif
                                        @if ($u->hasPermission('content.publish') || ($s->created_by === $u->id && $s->status === 'draft'))
                                            <form method="post" action="{{ route('admin.adzan.transition', [$s, 'delete']) }}" data-confirm-submit="Hapus rekaman ini secara permanen?">@csrf<button class="btn btn--ghost btn--sm">Hapus</button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty title="Belum ada rekaman adzan" text="Pengguna saat ini memakai nada pengingat. Unggah rekaman adzan umum dan adzan Subuh." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="small muted">Aturan: rekaman disetujui oleh pengguna lain. Admin platform boleh menyetujui unggahannya sendiri; tindakan ini tercatat di log audit.</p>
        </div>
        @if ($u->hasPermission('audio.upload'))
            <form method="post" action="{{ route('admin.adzan.store') }}" enctype="multipart/form-data" class="card form">
                @csrf
                <h2>Unggah rekaman</h2>
                <div class="field"><label for="az-name">Nama tampilan</label>
                    <input id="az-name" name="name" class="input" maxlength="120" required value="{{ old('name') }}" placeholder="Contoh: Adzan Masjid Raya"></div>
                <div class="field"><label for="az-kind">Jenis</label>
                    <select id="az-kind" name="kind" class="select">@foreach (\App\Models\AdzanSound::KINDS as $k => $l)<option value="{{ $k }}" @selected(old('kind') === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label for="az-file">Berkas audio</label>
                    <input id="az-file" name="audio" type="file" class="input" accept="audio/mpeg,audio/mp4,audio/ogg,audio/wav,.mp3,.m4a,.ogg,.opus,.wav" required>
                    <span class="hint">MP3, M4A, OGG, atau WAV · maks {{ config('almara.adzan.max_upload_mb', 20) }} MB. Disarankan MP3 128 kbps, 2–5 menit.</span></div>
                <div class="field"><label for="az-dur">Durasi (detik, opsional)</label>
                    <input id="az-dur" name="duration_seconds" type="number" min="10" max="900" class="input" value="{{ old('duration_seconds') }}" placeholder="Contoh: 180"></div>
                <div class="field"><label for="az-lic">Keterangan izin</label>
                    <input id="az-lic" name="license_note" class="input" maxlength="500" required value="{{ old('license_note') }}" placeholder="Contoh: Rekaman milik sendiri, direkam 2026"></div>
                <label class="check"><input type="checkbox" name="confirm_rights" value="1"> <span>Rekaman ini milik saya atau sudah ada izin tertulis untuk dipakai di aplikasi beriklan.</span></label>
                <div class="form-actions"><button class="btn" data-loading><x-icon name="upload" />Unggah</button></div>
            </form>
        @endif
    </div>
</x-layouts.admin>
