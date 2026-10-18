{{-- Catatan pribadi — @2026-Alifiandra --}}
<x-layouts.app title="Catatan pribadi" :noindex="true">
    <x-page-head title="Catatan pribadi" subtitle="Hanya terlihat oleh Anda" :back="route('profile')" />
    <div class="stack">
        <form method="get" class="input-wrap" role="search">
            <x-icon name="search" /><label for="q" class="sr-only">Cari catatan</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Cari di catatan…" maxlength="100">
        </form>
        @forelse ($notes as $n)
            <article class="card" data-note-row="{{ $n->id }}">
                <div class="section-head" style="margin-bottom:6px">
                    @if ($n->verse)
                        <a href="{{ route('quran.show', $n->verse->surah->number) }}#ayat-{{ $n->verse->ayah_number }}" class="badge">QS. {{ $n->verse->surah->name_latin }} {{ $n->verse->surah->number }}:{{ $n->verse->ayah_number }}</a>
                    @else
                        <span class="badge badge--neutral">Catatan umum</span>
                    @endif
                    <span style="display:flex;gap:4px">
                        <span class="small muted">{{ $n->updated_at->translatedFormat('j M Y') }}</span>
                        <button type="button" class="icon-btn" data-note-delete="{{ $n->id }}" aria-label="Hapus catatan"><x-icon name="trash" /></button>
                    </span>
                </div>
                <p style="margin:0;white-space:pre-line">{{ $n->body }}</p>
            </article>
        @empty
            <div class="card"><x-empty icon="note" title="{{ $q ? 'Tidak ada catatan yang cocok' : 'Belum ada catatan' }}" text="Tambahkan catatan tadabur dari ikon catatan pada setiap ayat." /></div>
        @endforelse
        {{ $notes->links('partials.pagination') }}
        <p class="small muted">Catatan pribadi bukan tafsir resmi atau fatwa.</p>
    </div>
</x-layouts.app>
