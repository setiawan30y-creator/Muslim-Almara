{{-- Admin: daftar doa/dzikir — @2026-Alifiandra --}}
@php $label = ['doa' => 'Doa', 'dzikir' => 'Dzikir', 'panduan' => 'Panduan'][$type]; $u = auth()->user(); @endphp
<x-layouts.admin :title="$label">
    <x-page-head :title="$label" subtitle="Draft → review → terbit. Pembuat tidak dapat menyetujui kontennya sendiri.">
        @if ($u->hasPermission('content.edit'))<a href="{{ route('admin.content.create', $type) }}" class="btn btn--sm"><x-icon name="plus" />Tambah</a>@endif
    </x-page-head>

    <form method="get" class="toolbar">
        <div class="input-wrap grow"><x-icon name="search" /><label for="q" class="sr-only">Cari</label>
            <input id="q" name="q" class="input" value="{{ $q }}" placeholder="Cari judul atau arti…"></div>
        <label for="status" class="sr-only">Status</label>
        <select id="status" name="status" class="select" style="max-width:200px" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (\App\Enums\ContentStatus::cases() as $st)<option value="{{ $st->value }}" @selected($status === $st)>{{ $st->label() }}</option>@endforeach
        </select>
    </form>

    @if (session('bulk_errors'))
        <div class="alert alert--warning flash"><x-icon name="alert" /><div><b>Dilewati:</b><ul>@foreach (session('bulk_errors') as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
    @endif

    <form method="post" action="{{ route('admin.content.bulk', $type) }}" data-confirm-submit="Jalankan tindakan untuk item terpilih?">
        @csrf
        <div class="table-wrap">
            <table class="ledger ledger--cards">
                <thead><tr><th><span class="sr-only">Pilih</span><input type="checkbox" data-check-all aria-label="Pilih semua"></th><th>Judul</th><th>Kategori</th><th>Status</th><th>Versi</th><th>Reviewer</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $it)
                        <tr>
                            <td data-label="Pilih"><input type="checkbox" name="ids[]" value="{{ $it->id }}" aria-label="Pilih {{ $it->title }}"></td>
                            <td data-label="Judul" class="wrap"><b>{{ $it->title }}</b><br><span class="small muted">{{ $it->source_reference }}</span></td>
                            <td data-label="Kategori">@if ($type === 'panduan')<span class="small muted">{{ $it->category?->moduleLabel() }}</span><br>@endif{{ $it->category?->title }}</td>
                            <td data-label="Status"><x-status-badge :status="$it->review_status" /></td>
                            <td data-label="Versi" class="num">{{ $it->content_version }}</td>
                            <td data-label="Reviewer">{{ $it->reviewer?->name ?? '—' }}</td>
                            <td><a href="{{ route('admin.content.edit', [$type, $it->id]) }}" class="btn btn--outline btn--sm">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty title="Tidak ada konten" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->isNotEmpty())
            <div class="card" style="margin-top:12px">
                <div class="toolbar" style="margin:0">
                    <label for="bulk-action" class="small" style="font-weight:700">Tindakan untuk terpilih:</label>
                    <select id="bulk-action" name="action" class="select" style="max-width:260px">
                        @if ($u->hasPermission('content.edit'))<option value="submit">Ajukan review</option>@endif
                        @if ($u->hasPermission('content.review') && $u->hasPermission('content.publish'))<option value="approve-publish">Setujui &amp; terbitkan</option>@endif
                        @if ($u->hasPermission('content.publish'))<option value="publish">Terbitkan (yang sudah disetujui)</option><option value="suspend">Tangguhkan</option>@endif
                    </select>
                    <button class="btn btn--sm">Jalankan</button>
                </div>
                <label class="check" style="margin-top:10px"><input type="checkbox" name="confirm" value="1"> <span>Saya telah memeriksa {{ $type === 'panduan' ? 'penjelasan, bacaan (bila ada),' : 'teks Arab, transliterasi, arti,' }} dan rujukan setiap item terpilih terhadap sumbernya.</span></label>
            </div>
        @endif
    </form>
    {{ $items->links('partials.pagination') }}
</x-layouts.admin>
