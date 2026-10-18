<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Isolasi data pribadi & IDOR (blueprint §10.3, §16.2) — @2026-Alifiandra
class PersonalDataTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;
    private User $bob;
    private QuranVerse $verse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
        $this->verse = QuranVerse::query()->first();
    }

    public function test_bookmarks_are_private_and_idor_safe(): void
    {
        $id = $this->actingAs($this->alice)->postJson('/api/v1/me/bookmarks', ['verse_id' => $this->verse->id])->assertCreated()->json('data.id');
        $this->actingAs($this->alice)->postJson('/api/v1/me/bookmarks', ['verse_id' => $this->verse->id])->assertOk(); // tidak duplikat

        $this->actingAs($this->bob)->getJson('/api/v1/me/bookmarks')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->bob)->deleteJson("/api/v1/me/bookmarks/{$id}")->assertForbidden();
        $this->assertDatabaseHas('user_bookmarks', ['id' => $id]);

        $this->actingAs($this->alice)->deleteJson("/api/v1/me/bookmarks/{$id}")->assertOk();
        $this->assertDatabaseMissing('user_bookmarks', ['id' => $id]);
    }

    public function test_notes_are_private_and_idor_safe(): void
    {
        $id = $this->actingAs($this->alice)->postJson('/api/v1/me/notes', ['verse_id' => $this->verse->id, 'body' => 'Catatan rahasia'])->assertCreated()->json('data.id');
        $this->actingAs($this->bob)->getJson('/api/v1/me/notes')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->bob)->patchJson("/api/v1/me/notes/{$id}", ['body' => 'diubah'])->assertForbidden();
        $this->actingAs($this->bob)->deleteJson("/api/v1/me/notes/{$id}")->assertForbidden();
        $this->actingAs($this->bob)->get('/profil/catatan')->assertOk()->assertDontSee('Catatan rahasia');
        $this->actingAs($this->alice)->get('/profil/catatan')->assertOk()->assertSee('Catatan rahasia');
    }

    public function test_guest_cannot_access_private_endpoints(): void
    {
        $this->getJson('/api/v1/me/bookmarks')->assertUnauthorized();
        $this->getJson('/api/v1/me/data-export')->assertUnauthorized();
        $this->get('/profil/catatan')->assertRedirect('/masuk');
    }

    public function test_reading_progress_and_goals(): void
    {
        $v = QuranVerse::query()->whereHas('surah', fn ($q) => $q->where('number', 1))->where('ayah_number', 7)->first();
        $this->actingAs($this->alice)->putJson('/api/v1/me/reading-goals', ['daily_target' => 10])->assertOk();
        $this->actingAs($this->alice)->putJson('/api/v1/me/reading-progress', ['verse_id' => $v->id, 'mark_read' => true, 'from_ayah' => 1])
            ->assertOk()->assertJsonPath('data.recorded', 7)->assertJsonPath('data.stats.today', 7)->assertJsonPath('data.stats.target', 10);
        $this->actingAs($this->alice)->getJson('/api/v1/me/reading-progress')->assertJsonPath('data.last_read.ayah', 7);
        $this->actingAs($this->bob)->getJson('/api/v1/me/reading-progress')->assertJsonPath('data.last_read', null)->assertJsonPath('data.stats.total', 0);
    }

    public function test_export_and_delete_account(): void
    {
        $this->alice->update(['password' => 'rahasia123']);
        $this->actingAs($this->alice)->postJson('/api/v1/me/notes', ['body' => 'Ekspor saya']);
        $this->actingAs($this->alice)->get('/profil/data/ekspor')->assertOk()->assertHeader('Content-Disposition')->assertSee('Ekspor saya');

        $this->actingAs($this->alice)->delete('/profil/akun', ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->actingAs($this->alice)->delete('/profil/akun', ['password' => 'rahasia123'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $this->alice->id]);
        $this->assertDatabaseMissing('user_notes', ['user_id' => $this->alice->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.deleted']);
    }

    public function test_private_pages_are_never_cacheable(): void
    {
        $r = $this->actingAs($this->alice)->get('/profil');
        $r->assertOk()->assertHeader('X-Almara-Private', '1');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->actingAs($this->alice)->get('/')->headers->get('Cache-Control'));
    }
}
