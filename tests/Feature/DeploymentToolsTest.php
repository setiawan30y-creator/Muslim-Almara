<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\DuaItem;
use App\Models\GuideItem;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Perintah terbit konten & assetlinks TWA — @2026-Alifiandra
class DeploymentToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_command_requires_reviewer_and_records_reviews(): void
    {
        $this->seedAlmara(publishContent: false);
        $editor = $this->userWithRole(Role::EDITOR);
        $admin = $this->userWithRole(Role::ADMIN);

        $this->artisan('almara:content:publish', ['reviewer' => $editor->email, '--force' => true])->assertFailed();
        $this->artisan('almara:content:publish', ['reviewer' => $admin->email])->expectsConfirmation('Konten agama wajib diperiksa ahli. Anda menyatakan isi di atas TELAH diperiksa dan siap terbit?', 'no')->assertFailed();
        $this->assertSame(0, GuideItem::query()->published()->count());

        $this->artisan('almara:content:publish', ['reviewer' => $admin->email, '--type' => 'panduan', '--force' => true])->assertSuccessful();
        $this->assertSame(GuideItem::query()->count(), GuideItem::query()->published()->count());
        $this->assertSame(0, DuaItem::query()->published()->count());
        $this->assertDatabaseHas('content_reviews', ['content_type' => 'panduan', 'reviewer_id' => $admin->id, 'decision' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.panduan.approve-publish', 'actor_user_id' => $admin->id]);

        $this->artisan('almara:content:publish', ['reviewer' => $admin->email, '--force' => true])->assertSuccessful();
        $this->assertSame(0, DuaItem::query()->where('review_status', ContentStatus::Review->value)->count());
        $this->get('/doa-dzikir')->assertOk()->assertDontSee('Belum ada dzikir yang diterbitkan');
    }

    public function test_assetlinks_for_play_store(): void
    {
        $this->get('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
        config(['almara.twa.package' => 'id.contoh.almaramuslim', 'almara.twa.fingerprints' => 'AA:BB:CC, DD:EE:FF']);
        $this->get('/.well-known/assetlinks.json')->assertOk()
            ->assertJsonPath('0.target.package_name', 'id.contoh.almaramuslim')
            ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB:CC', 'DD:EE:FF']);
    }
}
