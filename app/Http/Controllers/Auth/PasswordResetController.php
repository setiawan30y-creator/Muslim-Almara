<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

// Reset kata sandi dengan token berumur terbatas (blueprint §10.1). @2026-Alifiandra
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('pages.auth.forgot');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:191']]);
        Password::sendResetLink($request->only('email'));

        // Pesan sama apa pun hasilnya agar tidak membocorkan email terdaftar
        return back()->with('status', 'Jika email terdaftar, tautan reset kata sandi telah dikirim.');
    }

    public function reset(Request $request, string $token)
    {
        return view('pages.auth.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [], ['password' => 'kata sandi']);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete(); // cabut token API lama
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Kata sandi berhasil diubah. Silakan masuk.')
            : back()->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
    }
}
