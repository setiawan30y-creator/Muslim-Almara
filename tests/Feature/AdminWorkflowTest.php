<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\DuaItem;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Admin & alur review konten (blueprint §2, §5.3, §16.2) — @2026-Alifiandra
class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'category_id' => null, 'title' => 'Doa Uji', 'slug' => 'doa-uji', 'arabic_text' => 'اَللّٰهُمَّ', 'transliteration' => 'Allāhumma',
        'translation' => 'Ya Allah', 'source_reference' => 'Rujukan uji', 'source_kind' => 'general',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara(publishContent: false);
        $this->payload['category_id'] = \App\Models\DuaCategory::query()->value('id');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/masuk');
        $this->actingAs($this->userWithRole())->get('/admin')->assertForbidden();
        $this->actingAs($this->userWithRole())->post('/admin/konten/doa', $this->payload)->assertForbidden();
    }

    public function test_editor_creates_but_cannot_approve_own_content(): void
    {
        $editor = $this->userWithRole(Role::EDITOR);
        $this->actingAs($editor)->post('/admin/konten/doa', $this->payload)->assertRedirect();
        $item = DuaItem::query()->where('slug', 'doa-uji')->first();
        $this->assertSame(ContentStatus::Draft, $item->review_status);

        $this->actingAs($editor)->post("/admin/konten/doa/{$item->id}/submit")->assertRedirect();
        $this->assertSame(ContentStatus::Review, $item->fresh()->review_status);
        $this->actingAs($editor)->post("/admin/konten/doa/{$item->id}/approve-publish")->assertForbidden();

        // Admin yang juga pengaju tidak boleh menyetujui konten sendiri
        $admin = $this->userWithRole(Role::ADMIN);
        $this->actingAs($admin)->put("/admin/konten/doa/{$item->id}", ['title' => 'Doa Uji 2'] + $this->payload)->assertRedirect();
        $item->refresh();
        $this->assertSame(2, $item->content_version);
        $this->assertSame(ContentStatus::Draft, $item->review_status);
        $this->actingAs($admin)->post("/admin/konten/doa/{$item->id}/submit");
        $this->actingAs($admin)->post("/admin/konten/doa/{$item->id}/approve-publish")->assertSessionHasErrors('status');

        $reviewer = $this->userWithRole(Role::REVIEWER);
        $this->actingAs($reviewer)->post("/admin/konten/doa/{$item->id}/approve-publish", ['notes' => 'Sesuai sumber'])->assertRedirect();
        $item->refresh();
        $this->assertSame(ContentStatus::Published, $item->review_status);
        $this->assertSame($reviewer->id, $item->reviewed_by);
        $this->assertDatabaseHas('content_reviews', ['content_id' => $item->id, 'decision' => 'approved', 'reviewer_id' => $reviewer->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.doa.approve-publish']);
        $this->getJson('/api/v1/dua/items')->assertJsonFragment(['slug' => 'doa-uji']);

        // Menangguhkan menyembunyikan dari publik
        $this->actingAs($reviewer)->post("/admin/konten/doa/{$item->id}/suspend")->assertRedirect();
        $this->getJson('/api/v1/dua/items')->assertJsonMissing(['slug' => 'doa-uji']);
    }

    public function test_content_requires_latin_and_indonesian_meaning(): void
    {
        $editor = $this->userWithRole(Role::EDITOR);
        $this->actingAs($editor)->post('/admin/konten/doa', ['transliteration' => '', 'translation' => ''] + $this->payload)
            ->assertSessionHasErrors(['transliteration', 'translation']);
    }

    public function test_bulk_approve_requires_confirmation_and_reviewer(): void
    {
        $reviewer = $this->userWithRole(Role::REVIEWER);
        $ids = DuaItem::query()->pluck('id')->all();
        $this->actingAs($reviewer)->post('/admin/konten/doa/massal', ['ids' => $ids, 'action' => 'approve-publish'])->assertSessionHasErrors('confirm');
        $this->actingAs($reviewer)->post('/admin/konten/doa/massal', ['ids' => $ids, 'action' => 'approve-publish', 'confirm' => '1'])->assertRedirect();
        $this->assertSame(count($ids), DuaItem::query()->published()->count());
        $this->get('/doa-dzikir?tab=doa')->assertSee('Sebelum Tidur');
    }

    public function test_admin_pages_and_role_management(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        foreach (['/admin', '/admin/konten/doa', '/admin/konten/dzikir', '/admin/konten/doa/baru', '/admin/sumber', '/admin/pengguna', '/admin/pengaturan', '/admin/audit'] as $u) {
            $this->actingAs($admin)->get($u)->assertOk();
        }
        $this->actingAs($admin)->get('/admin/konten/doa/'.DuaItem::query()->value('id'))->assertOk();

        $target = $this->userWithRole();
        $reviewerId = Role::query()->where('name', Role::REVIEWER)->value('id');
        $this->actingAs($admin)->put("/admin/pengguna/{$target->id}/peran", ['roles' => [$reviewerId]])->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole(Role::REVIEWER));
        // Tidak bisa mencabut admin dari diri sendiri
        $this->actingAs($admin)->put("/admin/pengguna/{$admin->id}/peran", ['roles' => []])->assertSessionHasErrors('roles');

        $this->actingAs($admin)->put('/admin/pengaturan', ['default_method' => 'mwl', 'hijri_adjustment' => 1, 'default_city' => 'bandung'])->assertRedirect();
        $this->get('/jadwal-sholat')->assertSee('Muslim World League')->assertSee('Bandung');
    }

    public function test_editor_cannot_manage_users_or_settings(): void
    {
        $editor = $this->userWithRole(Role::EDITOR);
        $this->actingAs($editor)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($editor)->get('/admin/pengaturan')->assertForbidden();
        $this->actingAs($editor)->get('/admin/audit')->assertForbidden();
    }
}
