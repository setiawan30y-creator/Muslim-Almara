{{-- Admin: Qari ALMARA — unggah & review rekaman (blueprint §4.12) — @2026-Alifiandra --}}
@php $u = auth()->user(); $canUpload = $u->hasPermission('audio.upload'); @endphp
<x-layouts.admin title="Audio qari">
    <x-page-head title="Audio qari" subtitle="Unggah → karantina (draf) → review → terbit. Hanya rekaman milik sendiri atau berizin." />

    <div class="layout-side">
        <div class="stack">
            <form method="get" class="toolbar" style="margin:0">
                <label for="f-qari" class="sr-only">Qari</label>
                <select id="f-qari" name="qari" class="select" style="max-width:240px" onchange="this.form.submit()">
                    <option value="">Semua qari</option>
                    @foreach ($reciters as $r)<option value="{{ $r->id }}" @selected($reciterId === $r->id)>{{ $r->name }}</option>@endforeach
                </select>
                <label for="f-status" class="sr-only">Status</label>
                <select id="f-status" name="status" class="select" style="max-width:200px" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\ContentStatus::cases() as $st)<option value="{{ $st->value }}" @selected($status === $st)>{{ $st->label() }}</option>@endforeach
                </select>
            </form>

            <div class="table-wrap">
                <table class="ledger ledger--cards">
                    <thead><tr><th>Surah</th><th>Qari</th><th>Versi</th><th>Durasi</th><th>Penanda ayat</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($tracks as $t)
                            <tr>
                                <td data-label="Surah" class="wrap"><b>{{ $t->surah_number }}. {{ $t->surah?->name_latin }}</b></td>
                                <td data-label="Qari">{{ $t->reciter?->name }}</td>
                                <td data-label="Versi" class="num">{{ $t->version }}</td>
                                <td data-label="Durasi" class="num">{{ $t->durationLabel() }}</td>
                                <td data-label="Penanda ayat" class="num">{{ $t->segments_count ?: '—' }}</td>
                                <td data-label="Status"><x-status-badge :status="$t->review_status" /></td>
                                <td><a href="{{ route('admin.audio.show', $t) }}" class="btn btn--outline btn--sm">Buka</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty title="Belum ada rekaman" text="Tambahkan qari lalu unggah rekaman per surah." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tracks->links('partials.pagination') }}

            <section class="card card--flat">
                <h2>Qari berlisensi (Quran.Foundation)</h2>
                @if ($qf['enabled'])
                    <p class="small" style="margin:0"><span class="badge badge--success">Aktif</span> {{ $qf['mock'] ? 'Mode simulasi (nada uji) — isi QF_CLIENT_ID & QF_CLIENT_SECRET lalu set QF_MOCK=false untuk qari asli.' : 'Daftar qari diambil dari API resmi dan di-cache maksimal sesuai ketentuan penyedia. Atribusi tampil otomatis di pemutar.' }}</p>
                @else
                    <p class="small muted" style="margin:0">Nonaktif. Untuk mengaktifkan, daftarkan aplikasi di Developer Console Quran.Foundation, lalu isi <code>QF_ENABLED=true</code>, <code>QF_CLIENT_ID</code>, dan <code>QF_CLIENT_SECRET</code> di <code>.env</code> server. Pastikan penggunaan Anda sesuai Developer Terms penyedia.</p>
                @endif
            </section>
        </div>

        <aside class="stack">
            @if ($canUpload)
                <form method="post" action="{{ route('admin.audio.store') }}" enctype="multipart/form-data" class="card form" data-audio-upload>
                    @csrf
                    <h2>Unggah rekaman</h2>
                    @if ($reciters->isEmpty())
                        <p class="small muted">Tambahkan qari terlebih dahulu (formulir di bawah).</p>
                    @else
                        <div class="field"><label for="reciter_id">Qari</label>
                            <select id="reciter_id" name="reciter_id" class="select" required>@foreach ($reciters as $r)<option value="{{ $r->id }}" @selected(old('reciter_id', $reciterId) == $r->id)>{{ $r->name }}</option>@endforeach</select>
                            @error('reciter_id')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="field"><label for="surah_number">Surah</label>
                            <select id="surah_number" name="surah_number" class="select" required>@foreach ($surahs as $s)<option value="{{ $s->number }}" @selected(old('surah_number') == $s->number)>{{ $s->number }}. {{ $s->name_latin }} ({{ $s->ayah_count }} ayat)</option>@endforeach</select>
                            @error('surah_number')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="field"><label for="audio">Berkas audio</label>
                            <input id="audio" name="audio" type="file" class="input" accept="audio/mpeg,audio/mp4,audio/x-m4a,audio/aac,audio/ogg,audio/wav,.mp3,.m4a,.ogg,.opus,.wav" required data-audio-file>
                            <span class="hint">MP3, M4A/AAC, OGG/Opus, atau WAV · maks. {{ config('almara.audio.max_upload_mb') }} MB</span>
                            @error('audio')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="field"><label for="duration_seconds">Durasi (detik)</label>
                            <input id="duration_seconds" name="duration_seconds" type="number" min="1" class="input" value="{{ old('duration_seconds') }}" data-audio-duration>
                            <span class="hint">Terisi otomatis dari berkas.</span>
                            @error('duration_seconds')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="field"><label for="segments">Penanda ayat (opsional)</label>
                            <textarea id="segments" name="segments" class="input" style="min-height:90px;font-family:ui-monospace,monospace" placeholder="1 0:00 0:06.5&#10;2 0:06.5 0:12">{{ old('segments') }}</textarea>
                            <span class="hint">Satu baris per ayat: <code>ayat mulai selesai</code>. Untuk sorot ayat & mode per ayat.</span>
                            @error('segments')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="field"><label for="notes">Catatan untuk reviewer (opsional)</label><input id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}"></div>
                        <label class="check"><input type="checkbox" name="confirm_rights" value="1" required> <span>Rekaman ini milik platform atau telah memperoleh izin penggunaan, dan bukan suara tiruan (AI).</span></label>
                        @error('confirm_rights')<span class="error">{{ $message }}</span>@enderror
                        <div class="form-actions"><button class="btn" data-loading><x-icon name="upload" />Unggah ke karantina</button></div>
                    @endif
                </form>

                <form method="post" action="{{ route('admin.audio.reciters.store') }}" class="card form">
                    @csrf
                    <h2>Tambah qari</h2>
                    <div class="field"><label for="r-name">Nama qari</label><input id="r-name" name="name" class="input" required maxlength="120" placeholder="Nama qari">@error('name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="r-style">Gaya bacaan</label>
                        <select id="r-style" name="style" class="select"><option value="Murattal">Murattal</option><option value="Mujawwad">Mujawwad</option><option value="Mu'allim">Mu'allim (pengajaran)</option><option value="Lainnya">Lainnya</option></select></div>
                    <div class="field"><label for="r-license">Dasar hak penggunaan</label><input id="r-license" name="license_note" class="input" required maxlength="255" placeholder="Contoh: rekaman internal, izin tertulis tgl …">@error('license_note')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="form-actions"><button class="btn btn--tonal btn--sm"><x-icon name="plus" />Tambah</button></div>
                </form>
            @endif

            @if ($reciters->isNotEmpty())
                <section class="card card--flat">
                    <h2>Daftar qari</h2>
                    @foreach ($reciters as $r)
                        <details style="margin-bottom:8px">
                            <summary><b>{{ $r->name }}</b> <span class="small muted">· {{ $r->published_count }} surah terbit{{ $r->is_active ? '' : ' · nonaktif' }}</span></summary>
                            @if ($u->hasPermission('content.publish'))
                                <form method="post" action="{{ route('admin.audio.reciters.update', $r) }}" class="form" style="margin-top:8px">
                                    @csrf @method('put')
                                    <div class="field"><label for="n-{{ $r->id }}">Nama</label><input id="n-{{ $r->id }}" name="name" class="input" value="{{ $r->name }}" required maxlength="120"></div>
                                    <div class="field"><label for="s-{{ $r->id }}">Gaya</label>
                                        <select id="s-{{ $r->id }}" name="style" class="select">@foreach (['Murattal', 'Mujawwad', "Mu'allim", 'Lainnya'] as $sty)<option value="{{ $sty }}" @selected($r->style === $sty)>{{ $sty }}</option>@endforeach</select></div>
                                    <div class="field"><label for="l-{{ $r->id }}">Dasar hak penggunaan</label><input id="l-{{ $r->id }}" name="license_note" class="input" value="{{ $r->license_note }}" required maxlength="255"></div>
                                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($r->is_active)> <span>Tampilkan ke pengguna</span></label>
                                    <div class="form-actions"><button class="btn btn--outline btn--sm">Simpan</button></div>
                                </form>
                            @else
                                <p class="small muted">{{ $r->license_note }}</p>
                            @endif
                        </details>
                    @endforeach
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
