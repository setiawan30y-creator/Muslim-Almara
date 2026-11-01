<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Tracker ibadah harian — @2026-Alifiandra
class WorshipTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_requires_login_and_saves_privately(): void
    {
        $this->get('/profil/ibadah')->assertRedirect('/masuk');
        $user = $this->userWithRole();
        $other = $this->userWithRole();

        $this->actingAs($user)->get('/profil/ibadah')->assertOk()->assertSee('Tracker ibadah harian')->assertSee('Berjamaah')->assertSee('Dzikir pagi');
        $today = now()->toDateString();
        $this->post('/profil/ibadah', ['tanggal' => $today,
            'prayers' => ['subuh' => 'jamaah', 'dzuhur' => 'tepat', 'ashar' => 'tepat', 'maghrib' => 'jamaah', 'isya' => 'hack'],
            'habits' => ['tilawah' => '1', 'dzikir_pagi' => '1', 'bukan_amalan' => '1']])->assertRedirect();
        $log = $user->worshipLogs()->first();
        $this->assertNull($log->data['prayers']['isya']);
        $this->assertTrue($log->data['habits']['tilawah']);
        $this->assertArrayNotHasKey('bukan_amalan', $log->data['habits']);
        $this->assertSame(14 + 12 + 12 + 14 + 10, $log->score);

        // Simpan ulang (JSON) memperbarui baris yang sama
        $this->postJson('/profil/ibadah', ['tanggal' => $today, 'prayers' => array_fill_keys(['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'], 'jamaah')])
            ->assertOk()->assertJsonPath('data.score', 70)->assertJsonPath('data.prayers.isya', 'jamaah');
        $this->assertSame(1, $user->worshipLogs()->count());

        // Tidak boleh tanggal mendatang atau > 30 hari lalu
        $this->post('/profil/ibadah', ['tanggal' => now()->addDay()->toDateString()])->assertNotFound();
        $this->post('/profil/ibadah', ['tanggal' => now()->subDays(31)->toDateString()])->assertNotFound();
        $this->get('/profil/ibadah?tanggal=2026-02-30')->assertNotFound();

        // Pengguna lain tidak melihat data ini
        $this->actingAs($other)->getJson('/api/v1/me/worship-logs')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson('/api/v1/me/worship-logs')->assertOk()->assertJsonPath('data.0.prayers.subuh', 'jamaah')->assertJsonPath('meta.streak', 1);
    }

    public function test_streak_api_and_export(): void
    {
        $user = $this->userWithRole();
        $full = ['prayers' => array_fill_keys(['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'], 'tepat')];
        foreach ([1, 2, 3] as $i) {
            $this->actingAs($user)->putJson('/api/v1/me/worship-logs/'.now()->subDays($i)->toDateString(), $full)->assertOk();
        }
        $this->putJson('/api/v1/me/worship-logs/'.now()->subDays(5)->toDateString(), $full)->assertOk();
        // Hari ini belum diisi → streak dihitung dari kemarin
        $this->getJson('/api/v1/me/worship-logs')->assertJsonPath('meta.streak', 3);
        $this->get('/profil/ibadah')->assertOk()->assertSee('heat__cell--3', false);

        $export = $this->get('/profil/data/ekspor')->assertOk()->getContent();
        $this->assertStringContainsString('"worship_logs"', $export);
        $this->assertStringContainsString(now()->subDays(5)->toDateString(), $export);
    }
}
