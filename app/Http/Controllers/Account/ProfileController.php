<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\PersonalDataService;
use App\Services\UserProgress\ReadingProgressService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// Tab "Profil" (blueprint §8.2) + data & privasi (§6.3). @2026-Alifiandra
class ProfileController extends Controller
{
    public function index(Request $request, ReadingProgressService $progress)
    {
        $user = $request->user();

        return view('pages.profile.index', [
            'user' => $user,
            'stats' => $user ? $progress->stats($user) : null,
            'counts' => $user ? ['bookmarks' => $user->bookmarks()->count(), 'notes' => $user->notes()->count()] : null,
        ]);
    }

    public function edit(Request $request)
    {
        return view('pages.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'lowercase', 'max:191', Rule::unique('users')->ignore($user->id)],
        ], [], ['name' => 'nama']);

        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
        if ($user->wasChanged('email')) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Profil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['current_password' => 'kata sandi saat ini', 'password' => 'kata sandi baru']);

        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->delete();
        Auth::logoutOtherDevices($data['password']);

        return back()->with('status', 'Kata sandi diubah. Sesi di perangkat lain telah diakhiri.');
    }

    public function data(Request $request)
    {
        return view('pages.profile.data', ['user' => $request->user()]);
    }

    public function export(Request $request, PersonalDataService $data)
    {
        Audit::log('account.exported', $request->user());
        $json = json_encode($data->export($request->user()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="almara-muslim-data-'.now()->format('Ymd').'.json"',
        ]);
    }

    public function resetProgress(Request $request, ReadingProgressService $progress)
    {
        $progress->reset($request->user());

        return back()->with('status', 'Riwayat & progres bacaan telah dihapus.');
    }

    public function destroy(Request $request, PersonalDataService $data)
    {
        $request->validate(['password' => ['required', 'current_password']], [], ['password' => 'kata sandi']);
        $user = $request->user();
        Auth::guard('web')->logout();
        $data->delete($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Akun dan seluruh data pribadi Anda telah dihapus.')->with('clear_private_cache', true);
    }
}
