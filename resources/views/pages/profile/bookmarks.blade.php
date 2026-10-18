{{-- Bookmark — @2026-Alifiandra --}}
<x-layouts.app title="Bookmark" :noindex="true">
    <x-page-head title="Bookmark ayat" :subtitle="$items->total().' ayat'" :back="route('profile')" />
    <div class="stack">
        @forelse ($items as $b)
            <article class="card" data-bookmark-row="{{ $b->id }}">
                <div class="section-head" style="margin-bottom:6px">
                    <a href="{{ route('quran.show', $b->verse->surah->number) }}#ayat-{{ $b->verse->ayah_number }}" class="badge">QS. {{ $b->verse->surah->name_latin }} {{ $b->verse->surah->number }}:{{ $b->verse->ayah_number }}</a>
                    <button type="button" class="icon-btn" data-bookmark-delete="{{ $b->id }}" aria-label="Hapus bookmark"><x-icon name="trash" /></button>
                </div>
                <p class="arabic" lang="ar" style="font-size:calc(var(--arabic-size) * .85);margin:6px 0">{{ $b->verse->text_uthmani }}</p>
                <p style="margin:0;font-size:var(--translation-size)">{{ $b->verse->translationId?->text }}</p>
            </article>
        @empty
            <div class="card"><x-empty icon="bookmark" title="Belum ada bookmark" text="Ketuk ikon penanda pada ayat untuk menyimpannya di sini." /></div>
        @endforelse
        {{ $items->links('partials.pagination') }}
    </div>
</x-layouts.app>
