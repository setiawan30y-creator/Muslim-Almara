{{-- Dzikir & Doa (blueprint §4.4) — @2026-Alifiandra --}}
@php $list = $tab === 'doa' ? $doa : $dzikir; $type = $tab === 'doa' ? 'doa' : 'dzikir'; @endphp
<x-layouts.app title="Dzikir & Doa" description="Dzikir setelah shalat, pagi-petang, dan doa harian dengan teks Arab, Latin, arti, dan sumber.">
    <x-page-head title="Dzikir & Doa" subtitle="Arab · Latin · arti Indonesia · sumber rujukan" />

    <div class="stack">
        <div class="tabs" role="tablist" aria-label="Jenis bacaan" style="max-width:360px">
            <a role="tab" class="tab-link" href="{{ route('doa') }}" aria-selected="{{ $tab === 'dzikir' ? 'true' : 'false' }}">Dzikir</a>
            <a role="tab" class="tab-link" href="{{ route('doa', ['tab' => 'doa']) }}" aria-selected="{{ $tab === 'doa' ? 'true' : 'false' }}">Doa</a>
        </div>

        @if ($list->isEmpty())
            <div class="card">
                <x-empty icon="hands" title="Belum ada {{ $type }} yang diterbitkan"
                         text="Setiap bacaan wajib ditinjau reviewer agama sebelum tampil. Konten menunggu review di panel admin." />
                @can('content.review')
                    <p style="text-align:center;margin:0"><a class="btn btn--sm" href="{{ route('admin.content.index', [$type, 'status' => 'review']) }}">Tinjau sekarang</a></p>
                @endcan
            </div>
        @else
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <div class="chips" role="group" aria-label="Kategori">
                    @foreach ($list as $cat)<a class="chip" href="#kat-{{ $cat->slug }}">{{ $cat->title }}</a>@endforeach
                </div>
                <span class="grow" style="flex:1"></span>
                <label class="switch"><input type="checkbox" data-toggle-latin checked><span class="switch__track"></span><span class="small">Latin</span></label>
            </div>

            @foreach ($list as $cat)
                <section aria-labelledby="kat-{{ $cat->slug }}" class="stack" style="--gap:12px">
                    <div class="section-head" id="kat-{{ $cat->slug }}" style="scroll-margin-top:80px"><h2>{{ $cat->title }}</h2><span class="small muted">{{ $cat->items->count() }} bacaan</span></div>
                    <div class="grid-2">
                        @foreach ($cat->items as $item)
                            <x-reading-card :item="$item" :type="$type" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        <p class="small muted">Teks Arab mengikuti rujukan yang dicantumkan; ayat Al-Qur'an mengikuti rasm Kemenag RI. Laporkan kekeliruan kepada pengelola agar ditinjau ulang.</p>
    </div>
</x-layouts.app>
