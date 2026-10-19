{{-- Daftar materi satu modul panduan — @2026-Alifiandra --}}
@php [$label, $desc, $icon] = $meta; $n = 0; @endphp
<x-layouts.app :title="$label" :description="$desc">
    <x-page-head :title="$label" :subtitle="$desc" :back="route('guide.index')" />
    <div class="stack" style="max-width:820px">
        @if ($categories->isEmpty())
            <div class="card">
                <x-empty :icon="$icon" title="Materi sedang ditinjau"
                         text="Setiap materi wajib ditinjau guru/ahli yang kompeten sebelum tampil. Materi menunggu review di panel admin." />
                @can('content.review')
                    <p style="text-align:center;margin:0"><a class="btn btn--sm" href="{{ route('admin.content.index', ['panduan', 'status' => 'review']) }}">Tinjau sekarang</a></p>
                @endcan
            </div>
        @else
            @foreach ($categories as $cat)
                <section class="card" aria-labelledby="kat-{{ $cat->slug }}">
                    <div class="section-head"><h2 id="kat-{{ $cat->slug }}">{{ $cat->title }}</h2><span class="small muted">{{ $cat->items->count() }} materi</span></div>
                    @if ($cat->description)<p class="small muted" style="margin-top:-6px">{{ $cat->description }}</p>@endif
                    <ol class="step-list">
                        @foreach ($cat->items as $it)
                            @php $n++; @endphp
                            <li>
                                <a href="{{ route('guide.show', [$modul, $it->slug]) }}">
                                    <span class="step-list__num">{{ $n }}</span>
                                    <span class="step-list__body"><b>{{ $it->title }}</b>
                                        <span class="small muted">{{ \Illuminate\Support\Str::limit(strtok($it->body, "\n"), 110) }}</span></span>
                                    @if ($it->quiz_json)<span class="badge badge--neutral">Latihan</span>@endif
                                    <x-icon name="chevron-right" />
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
            <p class="small muted">Materi ini ringkasan untuk membantu belajar dan mengulang. Untuk praktik ibadah dan bacaan, tetap berguru kepada pembimbing yang kompeten.</p>
        @endif
    </div>
</x-layouts.app>
