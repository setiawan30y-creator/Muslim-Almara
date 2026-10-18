{{-- Pencarian Al-Qur'an — @2026-Alifiandra --}}
<x-layouts.app title="Cari di Al-Qur'an" :noindex="true">
    <x-page-head title="Cari di Al-Qur'an" subtitle="Pencarian dasar pada terjemahan Indonesia atau rujukan (mis. 2:255)" :back="route('quran.index')" />
    <div class="stack">
        <form action="{{ route('quran.search') }}" method="get" role="search" class="input-wrap">
            <x-icon name="search" />
            <label for="q" class="sr-only">Kata kunci</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Contoh: sabar, rezeki, 2:255" minlength="2" maxlength="100" autofocus>
        </form>

        @if ($surahMatches->isNotEmpty())
            <div class="chips" style="flex-wrap:wrap">
                @foreach ($surahMatches as $s)<a class="chip" href="{{ route('quran.show', $s->number) }}">{{ $s->number }}. {{ $s->name_latin }}</a>@endforeach
            </div>
        @endif

        @if ($results)
            <p class="muted small">{{ number_format($results->total(), 0, ',', '.') }} ayat ditemukan untuk “{{ $q }}”.</p>
            @forelse ($results as $t)
                <a href="{{ route('quran.show', $t->verse->surah->number) }}#ayat-{{ $t->verse->ayah_number }}" class="card" style="display:block">
                    <span class="badge">QS. {{ $t->verse->surah->name_latin }} {{ $t->verse->surah->number }}:{{ $t->verse->ayah_number }}</span>
                    <p class="arabic" lang="ar" style="font-size:calc(var(--arabic-size) * .8);margin:8px 0">{{ $t->verse->text_uthmani }}</p>
                    <p style="margin:0;color:var(--color-text)">{!! preg_replace('/('.preg_quote(e($q), '/').')/iu', '<mark>$1</mark>', e($t->text)) !!}</p>
                </a>
            @empty
                <x-empty title="Tidak ada hasil" text="Coba kata lain atau gunakan rujukan surah:ayat." />
            @endforelse
            {{ $results->links('partials.pagination') }}
        @else
            <x-empty icon="search" title="Ketik kata kunci untuk mulai mencari" />
        @endif
    </div>
</x-layouts.app>
