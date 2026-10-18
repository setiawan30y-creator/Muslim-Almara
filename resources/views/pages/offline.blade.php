{{-- Halaman offline — @2026-Alifiandra --}}
<x-layouts.app title="Offline">
    <div class="card card--pad-lg" style="max-width:560px;margin:var(--space-6) auto;text-align:center">
        <x-empty icon="info" title="Anda sedang offline" text="Halaman ini belum tersimpan di perangkat. Halaman yang pernah dibuka tetap bisa diakses tanpa internet." />
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
            <a href="{{ route('home') }}" class="btn"><x-icon name="home" />Beranda</a>
            <a href="{{ route('quran.index') }}" class="btn btn--tonal"><x-icon name="book" />Al-Qur'an</a>
            <button type="button" class="btn btn--outline" onclick="location.reload()"><x-icon name="reset" />Coba lagi</button>
        </div>
    </div>
</x-layouts.app>
