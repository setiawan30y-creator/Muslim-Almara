{{-- Akun — @2026-Alifiandra --}}
<x-layouts.app title="Akun" :noindex="true">
    <x-page-head title="Akun" :back="route('profile')" />
    <div class="grid-2">
        <form method="post" action="{{ route('profile.update') }}" class="card form">
            @csrf @method('put')
            <h2>Profil</h2>
            <div class="field"><label for="name">Nama</label>
                <input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name" @error('name') aria-invalid="true" @enderror>
                @error('name')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="email">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="email" @error('email') aria-invalid="true" @enderror>
                @error('email')<span class="error">{{ $message }}</span>@enderror
                <span class="hint">Mengubah email memerlukan verifikasi ulang.</span></div>
            <div class="form-actions"><button class="btn" data-loading>Simpan</button></div>
        </form>
        <form method="post" action="{{ route('profile.password') }}" class="card form">
            @csrf @method('put')
            <h2>Kata sandi</h2>
            <div class="field"><label for="current_password">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password" class="input" required autocomplete="current-password" @error('current_password') aria-invalid="true" @enderror>
                @error('current_password')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" class="input" required autocomplete="new-password" minlength="8" @error('password') aria-invalid="true" @enderror>
                <span class="hint">Minimal 8 karakter, berisi huruf dan angka.</span>
                @error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
            <div class="form-actions"><button class="btn" data-loading>Ubah kata sandi</button></div>
        </form>
    </div>
</x-layouts.app>
