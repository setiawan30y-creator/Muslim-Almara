<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Token Sanctum untuk klien API (TWA/mobile). Web memakai sesi. @2026-Alifiandra
class AuthController extends ApiController
{
    public function register(RegisterRequest $request)
    {
        $user = User::query()->create($request->only('name', 'email', 'password'));
        event(new Registered($user));

        return $this->ok(['user' => (new UserResource($user->load('roles')))->resolve($request), 'token' => $user->createToken($this->device($request))->plainTextToken], [], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::query()->where('email', $request->input('email'))->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok.']);
        }

        return $this->ok(['user' => (new UserResource($user->load('roles')))->resolve($request), 'token' => $user->createToken($this->device($request))->plainTextToken]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return $this->ok(['logged_out' => true]);
    }

    private function device(Request $request): string
    {
        return mb_substr((string) $request->input('device_name', 'api'), 0, 60);
    }
}
