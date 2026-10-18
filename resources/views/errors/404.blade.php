{{-- 404 — @2026-Alifiandra --}}
<x-layouts.app title="Halaman tidak ditemukan">
    <div class="card card--pad-lg" style="max-width:560px;margin:var(--space-6) auto;text-align:center">
        <x-empty icon="compass" title="Halaman tidak ditemukan" text="Alamat yang Anda buka tidak tersedia atau sudah dipindahkan." />
        <a href="{{ route('home') }}" class="btn"><x-icon name="home" />Kembali ke beranda</a>
    </div>
</x-layouts.app>
