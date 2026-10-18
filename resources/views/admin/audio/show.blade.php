{{-- Admin: detail rekaman qari — @2026-Alifiandra --}}
@php
    use App\Enums\ContentStatus;
    $u = auth()->user();
    $st = $track->review_status;
    $isOwn = in_array($u->id, array_filter([$track->created_by, $track->submitted_by]), true);
    $title = 'QS. '.$track->surah_number.' '.($track->surah?->name_latin).' · v'.$track->version;
@endphp
<x-layouts.admin :title="$title">
    <x-page-head :title="$title" :subtitle="$track->reciter?->name" :back="route('admin.audio.index')">
        <x-status-badge :status="$st" />
    </x-page-head>

    <div class="layout-side">
        <div class="stack">
            <section class="card">
                <h2>Dengarkan</h2>
                <audio controls preload="metadata" style="width:100%" src="{{ route('quran.audio.stream', $track) }}"></audio>
                <dl class="small" style="display:grid;grid-template-columns:auto 1fr;gap:4px 16px;margin:12px 0 0">
                    <dt class="muted">Berkas asli</dt><dd style="margin:0;word-break:break-all">{{ $track->original_name }}</dd>
                    <dt class="muted">Format</dt><dd style="margin:0">{{ $track->mime }} · {{ $track->sizeLabel() }}</dd>
                    <dt class="muted">Durasi</dt><dd style="margin:0">{{ $track->durationLabel() }}</dd>
                    <dt class="muted">SHA-256</dt><dd style="margin:0;word-break:break-all;font-family:ui-monospace,monospace">{{ $track->checksum }}</dd>
                    <dt class="muted">Hak penggunaan</dt><dd style="margin:0">{{ $track->reciter?->license_note }}</dd>
                    @if ($track->notes)<dt class="muted">Catatan</dt><dd style="margin:0">{{ $track->notes }}</dd>@endif
                </dl>
                <p class="small muted" style="margin:12px 0 0">Periksa: bacaan sesuai surah {{ $track->surah?->name_latin }} ({{ $track->surah?->ayah_count }} ayat), lengkap dari awal sampai akhir, tanpa potongan/derau, dan suara asli qari (bukan tiruan AI).</p>
            </section>

            <form method="post" action="{{ route('admin.audio.segments', $track) }}" class="card form">
                @csrf @method('put')
                <h2>Penanda ayat</h2>
                <div class="field"><label for="segments">Satu baris per ayat: <code>ayat mulai selesai</code> (detik atau m:ss)</label>
                    <textarea id="segments" name="segments" class="input" style="min-height:160px;font-family:ui-monospace,monospace" @disabled($st !== ContentStatus::Draft || ! $u->hasPermission('audio.upload'))>{{ old('segments', $segmentsText) }}</textarea>
                    @error('segments')<span class="error">{{ $message }}</span>@enderror
                    <span class="hint">{{ $track->segments->count() }} dari {{ $track->surah?->ayah_count }} ayat bertanda. Dapat diubah hanya saat draf; perubahan wajib ditinjau.</span></div>
                @if ($st === ContentStatus::Draft && $u->hasPermission('audio.upload'))<div class="form-actions"><button class="btn btn--tonal btn--sm" data-loading>Simpan penanda</button></div>@endif
            </form>
        </div>

        <aside class="stack">
            <section class="card">
                <h2>Alur review</h2>
                <ul class="small muted" style="padding-left:1.1rem;margin:0 0 12px">
                    <li>Diunggah/diubah oleh: {{ $track->creator?->name ?? '—' }} · {{ $track->created_at?->translatedFormat('j M Y H:i') }}</li>
                    <li>Diajukan oleh: {{ $track->submitter?->name ?? '—' }}</li>
                    <li>Reviewer: {{ $track->reviewer?->name ?? '—' }} {{ $track->reviewed_at ? '('.$track->reviewed_at->translatedFormat('j M Y').')' : '' }}</li>
                    <li>Terbit: {{ $track->published_at?->translatedFormat('j M Y H:i') ?? '—' }}</li>
                </ul>
                @php
                    $actions = [];
                    if ($st === ContentStatus::Draft && $u->hasPermission('audio.upload')) { $actions[] = ['submit', 'Ajukan review', 'btn']; }
                    if ($st === ContentStatus::Review && $u->hasPermission('content.review') && ! $isOwn) {
                        if ($u->hasPermission('content.publish')) $actions[] = ['approve-publish', 'Setujui & terbitkan', 'btn'];
                        $actions[] = ['approve', 'Setujui saja', 'btn btn--tonal'];
                        $actions[] = ['reject', 'Kembalikan ke draf', 'btn btn--outline'];
                    }
                    if (in_array($st, [ContentStatus::Approved, ContentStatus::Suspended], true) && $u->hasPermission('content.publish')) $actions[] = ['publish', 'Terbitkan', 'btn'];
                    if (in_array($st, [ContentStatus::Published, ContentStatus::Approved], true) && $u->hasPermission('content.publish')) $actions[] = ['suspend', 'Tangguhkan', 'btn btn--danger'];
                    if ($st === ContentStatus::Draft && $u->hasPermission('audio.upload')) $actions[] = ['delete', 'Hapus draf', 'btn btn--danger'];
                @endphp
                @if ($st === ContentStatus::Review && $isOwn)
                    <p class="small alert alert--warning" style="margin-bottom:10px"><x-icon name="info" />Anda pengunggah/pengaju rekaman ini, sehingga persetujuan harus dilakukan reviewer lain.</p>
                @endif
                @if ($st === ContentStatus::Archived)
                    <p class="small muted">Versi ini diarsipkan karena versi yang lebih baru telah terbit.</p>
                @endif
                @forelse ($actions as [$act, $lbl, $cls])
                    <form method="post" action="{{ route('admin.audio.transition', [$track, $act]) }}" style="margin-bottom:8px" @if (in_array($act, ['suspend', 'reject', 'delete'])) data-confirm-submit="{{ $lbl }}?" @endif>
                        @csrf
                        @if (in_array($act, ['approve', 'approve-publish', 'reject']))
                            <label class="sr-only" for="notes-{{ $act }}">Catatan review</label>
                            <input id="notes-{{ $act }}" name="notes" class="input" style="margin-bottom:6px;min-height:40px" placeholder="Catatan review (opsional)">
                        @endif
                        <button class="{{ $cls }} btn--block btn--sm">{{ $lbl }}</button>
                    </form>
                @empty
                    @if ($st !== ContentStatus::Archived)<p class="small muted" style="margin:0">Tidak ada tindakan yang tersedia untuk peran Anda pada status ini.</p>@endif
                @endforelse
            </section>
            <section class="card card--flat">
                <h2>Riwayat review</h2>
                @forelse ($reviews as $r)
                    <p class="small" style="margin-bottom:8px"><b>{{ $r->decision === 'approved' ? 'Disetujui' : 'Dikembalikan' }}</b> · v{{ $r->content_version }} · {{ $r->reviewer?->name ?? '—' }} · {{ $r->created_at?->translatedFormat('j M Y H:i') }}@if ($r->notes)<br><span class="muted">{{ $r->notes }}</span>@endif</p>
                @empty
                    <p class="small muted" style="margin:0">Belum ada review.</p>
                @endforelse
            </section>
        </aside>
    </div>
</x-layouts.admin>
