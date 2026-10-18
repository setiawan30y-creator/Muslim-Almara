{{-- Daftar — @2026-Alifiandra --}}
<x-layouts.app title="Daftar" :noindex="true">
    <div style="max-width:480px;margin:0 auto">
        <x-page-head title="Buat akun" subtitle="Gratis — fitur ibadah inti tetap dapat dipakai tanpa akun" />
        <form method="post" action="{{ route('register') }}" class="card card--pad-lg form">
            @csrf
            <div class="field"><label for="name">Nama</label>
                <input id="name" name="name" class="input" value="{{ old('name') }}" required maxlength="100" autocomplete="name" placeholder="Nama lengkap" @error('name') aria-invalid="true" @enderror>
                @error('name')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="email">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@contoh.com" @error('email') aria-invalid="true" @enderror>
                @error('email')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" class="input" required minlength="8" autocomplete="new-password" @error('password') aria-invalid="true" @enderror>
                <span class="hint">Minimal 8 karakter, berisi huruf dan angka.</span>
                @error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
            <label class="check"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                <span>Saya menyetujui <a href="{{ route('terms') }}" target="_blank">Syarat &amp; Ketentuan</a> dan <a href="{{ route('privacy') }}" target="_blank">Kebijakan Privasi</a>.</span></label>
            @error('terms')<span class="error">{{ $message }}</span>@enderror
            <button class="btn btn--block" data-loading>Daftar</button>
            <p class="small" style="margin:0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </form>
    </div>
</x-layouts.app>
