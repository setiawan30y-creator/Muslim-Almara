{{-- Bookmark & koleksi — @2026-Alifiandra --}}
@php $activeId = $active instanceof \App\Models\UserCollection ? $active->id : null; @endphp
<x-layouts.app title="Bookmark & koleksi" :noindex="true">
    <x-page-head title="Bookmark & koleksi" :subtitle="$total.' ayat tersimpan'" :back="route('profile')" />
    <div class="stack">
        <div class="chips" role="group" aria-label="Koleksi">
            <a class="chip {{ ! $active ? 'is-active' : '' }}" href="{{ route('profile.bookmarks') }}">Semua</a>
            @foreach ($collections as $c)
                <a class="chip {{ $activeId === $c->id ? 'is-active' : '' }}" href="{{ route('profile.bookmarks', ['koleksi' => $c->id]) }}">{{ $c->name }} <span class="muted">{{ $c->bookmarks_count }}</span></a>
            @endforeach
            @if ($collections->isNotEmpty())<a class="chip {{ $active === 'tanpa' ? 'is-active' : '' }}" href="{{ route('profile.bookmarks', ['koleksi' => 'tanpa']) }}">Tanpa koleksi</a>@endif
        </div>

        <details class="card" @if ($errors->has('name')) open @endif>
            <summary><b><x-icon name="plus" style="width:18px;height:18px;display:inline;vertical-align:-4px" /> Koleksi baru</b> <span class="small muted">— kelompokkan ayat per tema, misalnya "Ayat tentang sabar"</span></summary>
            <form method="post" action="{{ route('profile.collections.store') }}" class="form" style="margin-top:12px">
                @csrf
                <div class="form-grid form-grid--2">
                    <div class="field"><label for="c-name">Nama koleksi</label><input id="c-name" name="name" class="input" maxlength="80" required placeholder="Nama koleksi">@error('name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="c-desc">Keterangan (opsional)</label><input id="c-desc" name="description" class="input" maxlength="255"></div>
                </div>
                <div class="form-actions"><button class="btn btn--sm">Buat koleksi</button></div>
            </form>
        </details>

        @if ($activeId)
            <div class="section-head">
                <div><h2 style="margin:0">{{ $active->name }}</h2>@if ($active->description)<span class="small muted">{{ $active->description }}</span>@endif</div>
                <form method="post" action="{{ route('profile.collections.destroy', $active) }}" data-confirm-submit="Hapus koleksi ini? Bookmark di dalamnya tetap tersimpan.">
                    @csrf @method('delete')<button class="btn btn--ghost btn--sm"><x-icon name="trash" />Hapus koleksi</button>
                </form>
            </div>
        @endif

        @forelse ($items as $b)
            <article class="card" data-bookmark-row="{{ $b->id }}">
                <div class="section-head" style="margin-bottom:6px">
                    <a href="{{ route('quran.show', $b->verse->surah->number) }}#ayat-{{ $b->verse->ayah_number }}" class="badge">QS. {{ $b->verse->surah->name_latin }} {{ $b->verse->surah->number }}:{{ $b->verse->ayah_number }}</a>
                    <span style="display:flex;gap:4px;align-items:center">
                        @if ($collections->isNotEmpty())
                            <form method="post" action="{{ route('profile.bookmarks.move', $b) }}">
                                @csrf @method('put')
                                <label class="sr-only" for="col-{{ $b->id }}">Koleksi</label>
                                <select id="col-{{ $b->id }}" name="collection_id" class="select select--sm" onchange="this.form.submit()">
                                    <option value="">Tanpa koleksi</option>
                                    @foreach ($collections as $c)<option value="{{ $c->id }}" @selected($b->collection_id === $c->id)>{{ $c->name }}</option>@endforeach
                                </select>
                                <noscript><button class="btn btn--sm btn--outline">Pindah</button></noscript>
                            </form>
                        @endif
                        <button type="button" class="icon-btn" data-bookmark-delete="{{ $b->id }}" aria-label="Hapus bookmark"><x-icon name="trash" /></button>
                    </span>
                </div>
                <p class="arabic" lang="ar" style="font-size:calc(var(--arabic-size) * .85);margin:6px 0">{{ $b->verse->text_uthmani }}</p>
                <p style="margin:0;font-size:var(--translation-size)">{{ $b->verse->translationId?->text }}</p>
            </article>
        @empty
            <div class="card"><x-empty icon="bookmark" title="{{ $active ? 'Koleksi ini masih kosong' : 'Belum ada bookmark' }}" text="Ketuk ikon penanda pada ayat untuk menyimpannya, lalu pilih koleksinya di sini." /></div>
        @endforelse
        {{ $items->links('partials.pagination') }}
    </div>
</x-layouts.app>
