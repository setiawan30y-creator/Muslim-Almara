<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Pengguna & peran — hanya metadata akun, tanpa data ibadah pribadi. @2026-Alifiandra
class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $q = trim((string) $request->query('q'));
        $users = User::query()->with('roles:id,name,label')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.users', ['users' => $users, 'roles' => Role::query()->orderBy('id')->get(), 'q' => $q]);
    }

    public function roles(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $ids = $request->validate(['roles' => ['nullable', 'array'], 'roles.*' => ['integer', 'exists:roles,id']])['roles'] ?? [];

        $adminRole = Role::query()->where('name', Role::ADMIN)->value('id');
        if ($user->is($request->user()) && ! in_array($adminRole, array_map('intval', $ids), true)) {
            throw ValidationException::withMessages(['roles' => 'Anda tidak dapat mencabut peran admin dari akun sendiri.']);
        }

        $before = $user->roles()->pluck('name')->all();
        $user->roles()->sync(collect($ids)->mapWithKeys(fn ($id) => [(int) $id => ['assigned_at' => now()]])->all());
        $user->flushPermissionCache();
        Audit::log('user.roles.updated', $user, ['before' => $before, 'after' => $user->roles()->pluck('name')->all()]);

        return back()->with('status', "Peran {$user->name} diperbarui.");
    }
}
