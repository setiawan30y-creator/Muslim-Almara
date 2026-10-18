<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Registrasi, login, logout, reset (blueprint §16.2) — @2026-Alifiandra
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_register_login_logout(): void
    {
        $this->post('/daftar', ['name' => 'Pengguna Uji', 'email' => 'uji@contoh.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'terms' => '1'])
            ->assertRedirect('/profil');
        $this->assertAuthenticated();
        $user = User::query()->where('email', 'uji@contoh.com')->first();
        $this->assertNotNull($user->appearance);
        $this->post('/keluar')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/masuk', ['email' => 'uji@contoh.com', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/masuk', ['email' => 'uji@contoh.com', 'password' => 'rahasia123'])->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_requires_terms_and_strong_password(): void
    {
        $this->post('/daftar', ['name' => 'A', 'email' => 'a@contoh.com', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors(['password', 'terms']);
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@contoh.com']);
        $this->post('/lupa-sandi', ['email' => 'reset@contoh.com'])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->post('/reset-sandi', ['token' => $n->token, 'email' => $user->email, 'password' => 'baruBaru123', 'password_confirmation' => 'baruBaru123'])
                ->assertRedirect('/masuk');

            return true;
        });
        $this->post('/masuk', ['email' => 'reset@contoh.com', 'password' => 'baruBaru123'])->assertRedirect('/profil');
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'x@contoh.com', 'password' => 'salah']);
        }
        $this->post('/masuk', ['email' => 'x@contoh.com', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_api_token_login_and_logout(): void
    {
        User::factory()->create(['email' => 'api@contoh.com', 'password' => 'rahasia123']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'api@contoh.com', 'password' => 'rahasia123'])
            ->assertOk()->assertJsonStructure(['data' => ['user' => ['id', 'email'], 'token'], 'meta'])->json('data.token');
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'api@contoh.com');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
