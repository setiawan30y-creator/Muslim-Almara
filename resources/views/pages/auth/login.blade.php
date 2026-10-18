{{-- Masuk — @2026-Alifiandra --}}
<x-layouts.app title="Masuk" :noindex="true">
    <div style="max-width:440px;margin:0 auto">
        <x-page-head title="Masuk" subtitle="Lanjutkan progres & catatan Anda" />
        <form method="post" action="{{ route('login') }}" class="card card--pad-lg form">
            @csrf
            <div class="field"><label for="email">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-err" @enderror>
                @error('email')<span class="error" id="email-err">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" class="input" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                @error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <label class="check"><input type="checkbox" name="remember" value="1"> <span>Ingat saya di perangkat ini</span></label>
            <button class="btn btn--block" data-loading>Masuk</button>
            <p class="small" style="margin:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
                <span>Belum punya akun? <a href="{{ route('register') }}">Daftar</a></span>
            </p>
        </form>
    </div>
</x-layouts.app>
