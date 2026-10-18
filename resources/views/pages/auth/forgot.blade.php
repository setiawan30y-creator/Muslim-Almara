{{-- Lupa kata sandi — @2026-Alifiandra --}}
<x-layouts.app title="Lupa kata sandi" :noindex="true">
    <div style="max-width:440px;margin:0 auto">
        <x-page-head title="Lupa kata sandi" subtitle="Kami kirimkan tautan reset ke email Anda" :back="route('login')" />
        <form method="post" action="{{ route('password.email') }}" class="card card--pad-lg form">
            @csrf
            <div class="field"><label for="email">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<span class="error">{{ $message }}</span>@enderror</div>
            <button class="btn btn--block" data-loading>Kirim tautan reset</button>
            <p class="small muted" style="margin:0">Tautan berlaku {{ config('auth.passwords.users.expire', 60) }} menit.</p>
        </form>
    </div>
</x-layouts.app>
