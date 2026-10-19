{{-- Panduan — @2026-Alifiandra --}}
<x-layouts.app title="Panduan" description="Panduan Tajwid, Umroh, Haji, dan Ziarah Kubur yang bersumber dan ditinjau.">
    <x-page-head title="Panduan" subtitle="Materi bersumber yang ditinjau sebelum terbit" />
    <div class="hub-grid">
        @foreach ($modules as $key => [$label, $desc, $icon])
            <a href="{{ route('guide.module', $key) }}" class="card hub-card">
                <span class="shortcut__ico"><x-icon :name="$icon" /></span>
                <span><b style="display:block">{{ $label }}</b><span class="desc">{{ $desc }}</span></span>
                <span class="small muted">{{ ($counts[$key] ?? 0) ? $counts[$key].' materi' : 'Menunggu review' }}</span>
            </a>
        @endforeach
    </div>
</x-layouts.app>
