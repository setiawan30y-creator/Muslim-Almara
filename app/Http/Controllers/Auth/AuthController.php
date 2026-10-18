<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Preferences\PreferenceService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// @2026-Alifiandra
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('pages.auth.login');
    }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('profile'))->with('status', 'Selamat datang kembali.');
    }

    public function showRegister()
    {
        return view('pages.auth.register');
    }

    public function register(RegisterRequest $request, PreferenceService $prefs)
    {
        $user = User::query()->create($request->only('name', 'email', 'password'));
        // Bawa preferensi tampilan perangkat ini ke akun baru
        $user->appearance()->create($prefs->appearance());
        $user->profile()->create(['dashboard_cards_json' => $prefs->homeCards()]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile')->with('status', 'Akun berhasil dibuat. Tautan verifikasi telah dikirim ke email Anda.');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar.')->with('clear_private_cache', true);
    }
}
