{{-- Admin: buat/ubah doa/dzikir — @2026-Alifiandra --}}
@php
    $label = ['doa' => 'Doa', 'dzikir' => 'Dzikir', 'panduan' => 'Panduan'][$type];
    $guide = $type === 'panduan';
    $u = auth()->user();
    $st = $item?->review_status;
    $canEdit = $u->hasPermission('content.edit');
    $isOwn = $item && in_array($u->id, array_filter([$item->created_by, $item->submitted_by]), true);
@endphp
<x-layouts.admin :title="$item ? $item->title : $label.' baru'">
    <x-page-head :title="$item ? $item->title : $label.' baru'" :back="route('admin.content.index', $type)">
        @if ($item)<x-status-badge :status="$st" />@endif
    </x-page-head>

    <div class="layout-side">
        <form method="post" action="{{ $item ? route('admin.content.update', [$type, $item->id]) : route('admin.content.store', $type) }}" class="card form">
            @csrf @if ($item) @method('put') @endif
            @if ($item && $st === \App\Enums\ContentStatus::Published)
                <div class="alert alert--warning"><x-icon name="alert" /><div>Mengubah teks konten yang sudah terbit akan membuat <b>versi baru berstatus draf</b> dan menyembunyikannya dari pembaca sampai ditinjau ulang. Versi lama tercatat di log audit.</div></div>
            @endif
            <div class="form-grid form-grid--2">
                <div class="field"><label for="title">Judul</label><input id="title" name="title" class="input" value="{{ old('title', $item?->title) }}" required maxlength="160" @disabled(! $canEdit)>@error('title')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="slug">Slug</label><input id="slug" name="slug" class="input" value="{{ old('slug', $item?->slug) }}" required pattern="[a-z0-9]+(-[a-z0-9]+)*" @disabled(! $canEdit)><span class="hint">huruf kecil & tanda hubung</span>@error('slug')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" class="select" @disabled(! $canEdit)>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $item?->category_id) == $c->id)>{{ $c->title }}</option>@endforeach</select></div>
                <div class="field"><label for="source_kind">Jenis sumber</label>
                    <select id="source_kind" name="source_kind" class="select" @disabled(! $canEdit)>
                        @foreach (['quran' => "Ayat Al-Qur'an", 'hadith' => 'Hadis', 'general' => $guide ? 'Kitab/rujukan fikih' : 'Doa umum (tanpa atribusi khusus)'] as $k => $v)<option value="{{ $k }}" @selected(old('source_kind', $item?->source_kind ?? 'hadith') === $k)>{{ $v }}</option>@endforeach
                    </select></div>
            </div>
            @if ($guide)
                <div class="field"><label for="body">Penjelasan</label><textarea id="body" name="body" class="input" style="min-height:220px" required @disabled(! $canEdit)>{{ old('body', $item?->body) }}</textarea>
                    <span class="hint">Pisahkan paragraf dengan baris kosong. Baris yang diawali "- " ditampilkan sebagai daftar.</span>@error('body')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="example_ref">Contoh ayat (opsional)</label><input id="example_ref" name="example_ref" class="input" style="max-width:180px" value="{{ old('example_ref', $item?->example_ref) }}" placeholder="surah:ayat" pattern="\d{1,3}:\d{1,3}" @disabled(! $canEdit)>
                    <span class="hint">Ayat lengkap diambil dari teks Al-Qur'an Kemenag di aplikasi.</span>@error('example_ref')<span class="error">{{ $message }}</span>@enderror</div>
                @if ($item?->quiz_json)
                    <div class="field"><b class="small">Latihan ({{ count($item->quiz_json) }} soal)</b>
                        <ol class="small muted" style="margin:0;padding-left:1.2rem">@foreach ($item->quiz_json as $q)<li>{{ $q['q'] }} — <b>{{ $q['options'][$q['answer']] ?? '' }}</b></li>@endforeach</ol></div>
                @endif
                <p class="small muted" style="margin:0">Bacaan Arab di bawah opsional; bila diisi, transliterasi & arti wajib.</p>
            @endif
            <div class="field"><label for="arabic_text">Teks Arab</label><textarea id="arabic_text" name="arabic_text" class="input arabic-input" dir="rtl" lang="ar" @required(! $guide) @disabled(! $canEdit)>{{ old('arabic_text', $item?->arabic_text) }}</textarea>@error('arabic_text')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="transliteration">Transliterasi Latin <span class="muted">({{ $guide ? 'wajib bila ada teks Arab' : 'wajib' }})</span></label><textarea id="transliteration" name="transliteration" class="input" @required(! $guide) @disabled(! $canEdit)>{{ old('transliteration', $item?->transliteration) }}</textarea>@error('transliteration')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="translation">Arti Bahasa Indonesia <span class="muted">({{ $guide ? 'wajib bila ada teks Arab' : 'wajib' }})</span></label><textarea id="translation" name="translation" class="input" @required(! $guide) @disabled(! $canEdit)>{{ old('translation', $item?->translation) }}</textarea>@error('translation')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="source_reference">Rujukan sumber</label><input id="source_reference" name="source_reference" class="input" value="{{ old('source_reference', $item?->source_reference) }}" required maxlength="255" placeholder="Contoh: HR. Muslim no. 591" @disabled(! $canEdit)>@error('source_reference')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="count_target">Jumlah anjuran (opsional)</label><input id="count_target" name="count_target" type="number" min="1" max="1000" class="input" value="{{ old('count_target', $item?->count_target) }}" @disabled(! $canEdit)><span class="hint">Isi hanya bila ada dasarnya.</span></div>
            </div>
            <div class="field"><label for="context_note">Keterangan konteks (opsional)</label><textarea id="context_note" name="context_note" class="input" style="min-height:70px" @disabled(! $canEdit)>{{ old('context_note', $item?->context_note) }}</textarea><span class="hint">Jangan menyatakan waktu, jumlah, atau status wajib tanpa dasar.</span></div>
            <input type="hidden" name="sort_order" value="{{ old('sort_order', $item?->sort_order ?? 0) }}">
            @if ($canEdit)<div class="form-actions"><button class="btn" data-loading>{{ $item ? 'Simpan perubahan' : 'Simpan sebagai draf' }}</button></div>@endif
        </form>

        @if ($item)
            <aside class="stack">
                <section class="card">
                    <h2>Alur review</h2>
                    <ul class="small muted" style="padding-left:1.1rem;margin:0 0 12px">
                        <li>Versi: {{ $item->content_version }}</li>
                        <li>Dibuat/diubah oleh: {{ $item->creator?->name ?? 'Seeder sistem' }}</li>
                        <li>Diajukan oleh: {{ $item->submitter?->name ?? '—' }}</li>
                        <li>Reviewer: {{ $item->reviewer?->name ?? '—' }} {{ $item->reviewed_at ? '('.$item->reviewed_at->translatedFormat('j M Y').')' : '' }}</li>
                    </ul>
                    @php
                        $actions = [];
                        if ($st === \App\Enums\ContentStatus::Draft && $u->hasPermission('content.edit')) $actions[] = ['submit', 'Ajukan review', 'btn'];
                        if ($st === \App\Enums\ContentStatus::Review && $u->hasPermission('content.review') && ! $isOwn) {
                            if ($u->hasPermission('content.publish')) $actions[] = ['approve-publish', 'Setujui & terbitkan', 'btn'];
                            $actions[] = ['approve', 'Setujui saja', 'btn btn--tonal'];
                            $actions[] = ['reject', 'Kembalikan ke draf', 'btn btn--outline'];
                        }
                        if (in_array($st, [\App\Enums\ContentStatus::Approved, \App\Enums\ContentStatus::Suspended], true) && $u->hasPermission('content.publish')) $actions[] = ['publish', 'Terbitkan', 'btn'];
                        if (in_array($st, [\App\Enums\ContentStatus::Published, \App\Enums\ContentStatus::Approved], true) && $u->hasPermission('content.publish')) $actions[] = ['suspend', 'Tangguhkan', 'btn btn--danger'];
                    @endphp
                    @if ($st === \App\Enums\ContentStatus::Review && $isOwn)
                        <p class="small alert alert--warning" style="margin-bottom:10px"><x-icon name="info" />Anda pembuat/pengaju konten ini, sehingga persetujuan harus dilakukan reviewer lain.</p>
                    @endif
                    @forelse ($actions as [$act, $lbl, $cls])
                        <form method="post" action="{{ route('admin.content.transition', [$type, $item->id, $act]) }}" style="margin-bottom:8px" @if (in_array($act, ['suspend', 'reject'])) data-confirm-submit="{{ $lbl }}?" @endif>
                            @csrf
                            @if (in_array($act, ['approve', 'approve-publish', 'reject']))
                                <label class="sr-only" for="notes-{{ $act }}">Catatan review</label>
                                <input id="notes-{{ $act }}" name="notes" class="input" style="margin-bottom:6px;min-height:40px" placeholder="Catatan review (opsional)">
                            @endif
                            <button class="{{ $cls }} btn--block btn--sm">{{ $lbl }}</button>
                        </form>
                    @empty
                        <p class="small muted" style="margin:0">Tidak ada tindakan yang tersedia untuk peran Anda pada status ini.</p>
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
        @endif
    </div>
</x-layouts.admin>
