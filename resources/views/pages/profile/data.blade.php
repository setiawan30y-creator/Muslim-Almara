{{-- Data & privasi — @2026-Alifiandra --}}
<x-layouts.app title="Data & privasi" :noindex="true">
    <x-page-head title="Data & privasi" subtitle="Data pribadi Anda hanya dapat diakses oleh Anda" :back="route('profile')" />
    <div class="stack" style="max-width:760px">
        <section class="card">
            <h2>Ekspor data</h2>
            <p class="muted">Unduh salinan profil, preferensi, bookmark, catatan, target, dan riwayat baca dalam format JSON.</p>
            <a class="btn btn--tonal" href="{{ route('profile.export') }}"><x-icon name="download" />Unduh data saya</a>
        </section>
        <section class="card">
            <h2>Hapus riwayat bacaan</h2>
            <p class="muted">Menghapus semua ayat yang ditandai dibaca dan posisi terakhir dibaca. Bookmark dan catatan tidak terhapus.</p>
            <form method="post" action="{{ route('profile.progress.reset') }}" data-confirm-submit="Hapus seluruh riwayat bacaan?">
                @csrf @method('delete')
                <button class="btn btn--outline"><x-icon name="trash" />Hapus riwayat</button>
            </form>
        </section>
        <section class="card" style="border-color:var(--color-danger)">
            <h2 style="color:var(--color-danger)">Hapus akun</h2>
            <p class="muted">Akun dan seluruh data pribadi (preferensi, bookmark, catatan, progres) dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
            <form method="post" action="{{ route('profile.destroy') }}" class="form" data-confirm-submit="Hapus akun beserta seluruh data pribadi secara permanen?">
                @csrf @method('delete')
                <div class="field"><label for="del-password">Konfirmasi dengan kata sandi</label>
                    <input id="del-password" name="password" type="password" class="input" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                    @error('password')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="form-actions"><button class="btn btn--danger"><x-icon name="trash" />Hapus akun saya</button></div>
            </form>
        </section>
        <p class="small muted">Lihat juga <a href="{{ route('privacy') }}">Kebijakan Privasi</a>.</p>
    </div>
</x-layouts.app>
