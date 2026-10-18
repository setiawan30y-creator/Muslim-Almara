<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Pengaturan Tampilan & Tema + preferensi Qur'an/sholat — @2026-Alifiandra
class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    private array $ap = ['theme_mode' => 'dark', 'accent_color' => 'ungu', 'interface_text_size' => 'besar', 'reading_text_size' => 'sangat-besar', 'translation_text_size' => 'besar'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_guest_appearance_saved_in_cookie_and_rendered(): void
    {
        $r = $this->postJson('/pengaturan/tampilan', $this->ap)->assertOk()->assertJsonPath('meta.stored', 'device');
        $r->assertPlainCookie('almara_tampilan');
        $this->withUnencryptedCookie('almara_tampilan', json_encode($this->ap))->get('/')
            ->assertSee('data-accent="ungu"', false)->assertSee('data-theme="dark"', false)->assertSee('data-reading-size="sangat-besar"', false);
    }

    public function test_user_appearance_persists_in_account(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->putJson('/api/v1/user/appearance-preferences', $this->ap)->assertOk();
        $this->assertDatabaseHas('user_appearance_preferences', ['user_id' => $u->id, 'accent_color' => 'ungu', 'theme_mode' => 'dark']);
        $this->actingAs($u)->get('/')->assertSee('data-accent="ungu"', false);
        $this->actingAs($u)->getJson('/api/v1/user/appearance-preferences')->assertJsonPath('data.accent_color', 'ungu');
    }

    public function test_invalid_appearance_values_rejected(): void
    {
        $this->postJson('/pengaturan/tampilan', ['theme_mode' => 'neon', 'accent_color' => 'red;}body{', 'interface_text_size' => 'sangat-besar', 'reading_text_size' => 'x', 'translation_text_size' => 'normal'])
            ->assertStatus(422)->assertJsonValidationErrors(['theme_mode', 'accent_color', 'interface_text_size', 'reading_text_size']);
        // Cookie palsu diabaikan, kembali ke nilai bawaan
        $this->withUnencryptedCookie('almara_tampilan', '{"accent_color":"x\"><script>"}')->get('/')->assertSee('data-accent="hijau"', false);
    }

    public function test_quran_preferences_default_mushaf_with_verse_fallback(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->getJson('/api/v1/quran/reading-preferences')
            ->assertJsonPath('data.reading_mode', 'mushaf')->assertJsonPath('data.show_translation', true)->assertJsonPath('data.audio_mode', 'surah')
            ->assertJsonPath('meta.mushaf_available', false);
        $this->actingAs($u)->get('/al-quran/1')->assertSee('Mode <b>Mushaf penuh</b>', false);
        $this->actingAs($u)->putJson('/api/v1/quran/reading-preferences', ['reading_mode' => 'verse', 'show_translation' => false])->assertOk();
        $this->actingAs($u)->get('/al-quran/1')->assertDontSee('Mode <b>Mushaf penuh</b>', false);
    }

    public function test_prayer_preferences_change_method(): void
    {
        $this->post('/pengaturan/jadwal-sholat', ['method' => 'mwl', 'asr' => 'hanafi', 'offsets' => ['subuh' => 2]])->assertRedirect()->assertPlainCookie('almara_sholat');
        $cookie = json_encode(['method' => 'mwl', 'asr' => 'hanafi', 'offsets' => ['subuh' => 2]]);
        $this->withUnencryptedCookie('almara_sholat', $cookie)->get('/jadwal-sholat')->assertSee('Muslim World League');
        $this->post('/pengaturan/jadwal-sholat', ['method' => 'tidak-ada', 'asr' => 'standard'])->assertSessionHasErrors('method');
    }

    public function test_home_cards_can_be_hidden_and_reordered(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/pengaturan/beranda', ['order' => ['verse_of_day', 'next_prayer', 'quick_actions', 'continue_reading', 'reading_goal', 'events', 'journal'], 'visible' => ['verse_of_day', 'next_prayer']])->assertRedirect();
        $this->actingAs($u)->get('/')->assertOk()->assertDontSee('Hari besar Islam')->assertSee('Ayat hari ini');
    }
}
