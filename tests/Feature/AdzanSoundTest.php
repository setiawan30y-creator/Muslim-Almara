<?php

namespace Tests\Feature;

use App\Models\AdzanSound;
use App\Models\Role;
use App\Services\Quran\Audio\SampleTone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Suara adzan: nada cadangan, unggah → setujui → dipilih pengguna — @2026-Alifiandra
class AdzanSoundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
        Storage::fake('local');
    }

    private function upload($user, array $extra = [])
    {
        return $this->actingAs($user)->post('/admin/adzan', $extra + [
            'name' => 'Adzan Uji', 'kind' => 'regular', 'audio' => UploadedFile::fake()->createWithContent('adzan.wav', SampleTone::wav(10)),
            'license_note' => 'Rekaman milik sendiri', 'confirm_rights' => '1',
        ]);
    }

    public function test_chime_fallback_and_settings_section(): void
    {
        $wav = $this->get('/adzan/nada.wav')->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->assertStringStartsWith('RIFF', file_get_contents($wav->baseResponse->getFile()->getPathname()));
        $this->getJson('/api/v1/adzan/sounds')->assertOk()->assertJsonPath('data.0.key', 'nada')->assertJsonPath('meta.defaults.regular', 'nada');

        $this->get('/pengaturan/jadwal-sholat')->assertOk()->assertSee('Suara adzan')->assertSee('data-adzan-settings', false)
            ->assertSee('Nada pengingat (bukan adzan)')->assertSee('Rekaman adzan belum tersedia');
        $this->get('/')->assertOk()->assertSee('data-prayer="', false);
    }

    public function test_upload_approve_and_stream(): void
    {
        $editor = $this->userWithRole(Role::EDITOR);
        $reviewer = $this->userWithRole(Role::REVIEWER);

        $this->upload($this->userWithRole())->assertForbidden();
        $this->upload($editor, ['confirm_rights' => null])->assertSessionHasErrors('confirm_rights');
        $this->upload($editor, ['audio' => UploadedFile::fake()->createWithContent('x.mp3', str_repeat('bukan audio ', 20))])->assertSessionHasErrors('audio');
        $this->upload($editor)->assertRedirect()->assertSessionHasNoErrors();
        $this->upload($editor)->assertSessionHasErrors('audio'); // duplikat

        $s = AdzanSound::query()->firstOrFail();
        $this->assertSame('draft', $s->status);
        $this->assertSame(20, $s->duration_seconds);
        auth()->logout();
        $this->get('/adzan/suara/'.$s->id)->assertNotFound();
        $this->getJson('/api/v1/adzan/sounds')->assertJsonCount(1, 'data');

        // Editor tidak bisa menyetujui; reviewer bisa
        $this->actingAs($editor)->post("/admin/adzan/{$s->id}/approve")->assertForbidden();
        $this->actingAs($reviewer)->post("/admin/adzan/{$s->id}/approve")->assertRedirect();
        $this->assertSame('published', $s->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'adzan.approved']);

        auth()->logout();
        $this->get('/adzan/suara/'.$s->id)->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->getJson('/api/v1/adzan/sounds')->assertJsonPath('data.0.key', 'almara:'.$s->id)->assertJsonPath('meta.defaults.regular', 'almara:'.$s->id)
            ->assertJsonPath('meta.defaults.subuh', 'almara:'.$s->id);
        $this->get('/pengaturan/jadwal-sholat')->assertSee('Adzan Uji')->assertDontSee('Rekaman adzan belum tersedia');

        // Reviewer biasa tidak boleh menyetujui unggahan sendiri; admin boleh (tercatat)
        $this->upload($reviewer = $this->userWithRole(Role::ADMIN), ['name' => 'Adzan Subuh Uji', 'kind' => 'subuh', 'audio' => UploadedFile::fake()->createWithContent('s.wav', SampleTone::wav(11))]);
        $sub = AdzanSound::query()->where('kind', 'subuh')->firstOrFail();
        $this->actingAs($reviewer)->post("/admin/adzan/{$sub->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'adzan.approved', 'subject_id' => $sub->id]);
        $this->getJson('/api/v1/adzan/sounds')->assertJsonPath('meta.defaults.subuh', 'almara:'.$sub->id);

        $this->actingAs($reviewer)->get('/admin/adzan')->assertOk()->assertSee('Adzan Subuh Uji');
        $this->actingAs($reviewer)->post("/admin/adzan/{$s->id}/deactivate")->assertRedirect();
        $this->actingAs($reviewer)->post("/admin/adzan/{$s->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('adzan_sounds', ['id' => $s->id]);
    }

    public function test_env_url_source_and_after_adzan_dua(): void
    {
        config(['almara.adzan.url' => 'https://cdn.contoh.test/adzan.mp3', 'almara.adzan.url_name' => 'Adzan Berlisensi', 'almara.adzan.subuh_url' => 'javascript:alert(1)']);
        $this->getJson('/api/v1/adzan/sounds')->assertJsonPath('data.0.key', 'env:regular')->assertJsonPath('data.0.name', 'Adzan Berlisensi')
            ->assertJsonCount(2, 'data');
        $this->assertDatabaseHas('dua_items', ['slug' => 'setelah-adzan', 'source_reference' => 'HR. Al-Bukhari no. 614']);
    }
}
