{{-- Jurnal & catatan pribadi — @2026-Alifiandra --}}
<x-layouts.app title="Jurnal & catatan" :noindex="true">
    <x-page-head title="Jurnal & catatan" subtitle="Catatan tadabur per ayat dan jurnal umum · hanya terlihat oleh Anda" :back="route('profile')" />
    <div class="stack">
        <details class="card" @if ($errors->any()) open @endif>
            <summary><b><x-icon name="plus" style="width:18px;height:18px;display:inline;vertical-align:-4px" /> Tulis jurnal</b></summary>
            <form method="post" action="{{ route('profile.notes.store') }}" class="form" style="margin-top:12px">
                @csrf
                <div class="field"><label for="body">Isi catatan</label><textarea id="body" name="body" class="input" maxlength="5000" required placeholder="Renungan, pelajaran hari ini, atau target ibadah…">{{ old('body') }}</textarea>@error('body')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="tags">Tag tema (opsional)</label><input id="tags" name="tags" class="input" maxlength="200" value="{{ old('tags', $tag) }}" placeholder="contoh: sabar, syukur, keluarga"><span class="hint">Pisahkan dengan koma, maksimal 5 tag.</span></div>
                <div class="form-actions"><button class="btn" data-loading>Simpan</button></div>
            </form>
        </details>

        <form method="get" class="input-wrap" role="search">
            <x-icon name="search" /><label for="q" class="sr-only">Cari catatan</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Cari di catatan…" maxlength="100">
            @if ($tag)<input type="hidden" name="tag" value="{{ $tag }}">@endif
        </form>
        @if ($tags->isNotEmpty())
            <div class="chips" role="group" aria-label="Filter tag">
                <a class="chip {{ $tag === '' ? 'is-active' : '' }}" href="{{ route('profile.notes', array_filter(['q' => $q])) }}">Semua</a>
                @foreach ($tags as $t => $c)<a class="chip {{ $tag === $t ? 'is-active' : '' }}" href="{{ route('profile.notes', array_filter(['tag' => $t, 'q' => $q])) }}">#{{ $t }} <span class="muted">{{ $c }}</span></a>@endforeach
            </div>
        @endif

        @forelse ($notes as $n)
            <article class="card" data-note-row="{{ $n->id }}">
                <div class="section-head" style="margin-bottom:6px">
                    @if ($n->verse)
                        <a href="{{ route('quran.show', $n->verse->surah->number) }}#ayat-{{ $n->verse->ayah_number }}" class="badge">QS. {{ $n->verse->surah->name_latin }} {{ $n->verse->surah->number }}:{{ $n->verse->ayah_number }}</a>
                    @else
                        <span class="badge badge--neutral">Jurnal</span>
                    @endif
                    <span style="display:flex;gap:4px;align-items:center">
                        <span class="small muted">{{ $n->updated_at->translatedFormat('j M Y') }}</span>
                        <button type="button" class="icon-btn" data-note-delete="{{ $n->id }}" aria-label="Hapus catatan"><x-icon name="trash" /></button>
                    </span>
                </div>
                <p style="margin:0;white-space:pre-line">{{ $n->body }}</p>
                <details class="note-tags">
                    <summary class="small">
                        @forelse ($n->tagList() as $t)<span class="tag-pill">#{{ $t }}</span>@empty<span class="muted">+ tag</span>@endforelse
                    </summary>
                    <form method="post" action="{{ route('profile.notes.tags', $n) }}" class="toolbar" style="margin:8px 0 0">
                        @csrf @method('put')
                        <label class="sr-only" for="tags-{{ $n->id }}">Tag</label>
                        <input id="tags-{{ $n->id }}" name="tags" class="input grow" value="{{ str_replace(',', ', ', (string) $n->tags) }}" maxlength="200" placeholder="sabar, syukur">
                        <button class="btn btn--sm btn--outline">Simpan tag</button>
                    </form>
                </details>
            </article>
        @empty
            <div class="card"><x-empty icon="note" title="{{ $q || $tag ? 'Tidak ada catatan yang cocok' : 'Belum ada catatan' }}" text="Tulis jurnal di atas, atau tambahkan catatan tadabur dari ikon catatan pada setiap ayat." /></div>
        @endforelse
        {{ $notes->links('partials.pagination') }}
        <p class="small muted">Catatan pribadi bukan tafsir resmi atau fatwa.</p>
    </div>
</x-layouts.app>
