{{-- Detail bacaan — @2026-Alifiandra --}}
<x-layouts.app :title="$item->title" :description="$item->translation">
    <x-page-head :title="$item->title" :subtitle="$item->category->title" :back="route('doa', $type === 'doa' ? ['tab' => 'doa'] : [])" />
    <div class="stack" style="max-width:760px">
        <x-reading-card :item="$item" :type="$type" :link="false" />
        <section class="card card--flat small">
            <h2>Informasi konten</h2>
            <ul class="muted" style="padding-left:1.1rem;margin:0">
                <li>Jenis: {{ $item->sourceKindLabel() }} · Rujukan: {{ $item->source_reference }}</li>
                <li>Versi konten: {{ $item->content_version }} · Terbit: {{ $item->published_at?->translatedFormat('j F Y') ?? '-' }}</li>
                <li>Status review: {{ $item->reviewed_at ? 'ditinjau '.$item->reviewed_at->translatedFormat('j F Y') : 'tercatat terbit' }}</li>
                @if ($item->source)<li>Kompilasi: {{ $item->source->name }}</li>@endif
            </ul>
        </section>
    </div>
</x-layouts.app>
