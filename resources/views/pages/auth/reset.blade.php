{{-- Reset kata sandi — @2026-Alifiandra --}}
<x-layouts.app title="Atur ulang kata sandi" :noindex="true">
    <div style="max-width:440px;margin:0 auto">
        <x-page-head title="Kata sandi baru" />
        <form method="post" action="{{ route('password.update') }}" class="card card--pad-lg form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field"><label for="email">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $email) }}" required autocomplete="username">
                @error('email')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" class="input" required minlength="8" autocomplete="new-password">
                @error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
            <button class="btn btn--block" data-loading>Simpan kata sandi</button>
        </form>
    </div>
</x-layouts.app>
