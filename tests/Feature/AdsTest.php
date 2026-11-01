<?php

namespace Tests\Feature;

use App\Services\Ads\AdManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Modul iklan: mati default, penempatan aman, consent, ads.txt — @2026-Alifiandra
class AdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    private function enable(array $extra = []): void
    {
        config(['almara.ads' => array_merge(config('almara.ads'), [
            'enabled' => true, 'client' => 'ca-pub-1234567890123456', 'slots' => ['footer' => '1111111111', 'list' => '2222222222'],
        ], $extra)]);
    }

    public function test_disabled_by_default(): void
    {
        $this->get('/')->assertOk()->assertDontSee('adsbygoogle', false)->assertDontSee('data-ads-consent', false);
        $this->get('/ads.txt')->assertNotFound();
        $this->get('/kebijakan-privasi')->assertSee('tidak menampilkan iklan');
    }

    public function test_placements_consent_and_ads_txt(): void
    {
        $this->enable(['ads_txt_extra' => 'example-cpm.com, 98765, RESELLER | baris tidak valid <script>']);

        $home = $this->get('/')->assertOk()->assertSee('data-ad-slot="1111111111"', false)->assertSee('data-ad-slot="2222222222"', false)
            ->assertSee('data-ads-consent', false)->assertSee('Pengaturan iklan');
        // Skrip iklan tidak dimuat sebelum ada persetujuan (dimuat lambat oleh JS)
        $this->assertStringNotContainsString('pagead2.googlesyndication.com', $home->getContent());

        // Tidak ada iklan di bacaan ibadah, akun, admin
        foreach (['/al-quran/1', '/al-quran/2/255', '/tasbih', '/masuk', '/kebijakan-privasi', '/hafalan'] as $u) {
            $this->get($u)->assertOk()->assertDontSee('class="adsbygoogle"', false);
        }
        $this->get('/jadwal-sholat')->assertOk()->assertSee('data-ad-slot="1111111111"', false);

        // Consent tersimpan → banner disembunyikan
        $this->withUnencryptedCookie(AdManager::CONSENT_COOKIE, 'npa')->get('/')->assertSee('"consent":"npa"', false)
            ->assertSee('data-ads-consent role="dialog" aria-labelledby="consent-title"  hidden', false);

        $this->get('/ads.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0')->assertSee('example-cpm.com, 98765, RESELLER')
            ->assertDontSee('<script>', false);
        $this->get('/kebijakan-privasi')->assertSee('Iklan umum saja');

        // Opsi: sembunyikan iklan untuk anggota
        config(['almara.ads.hide_for_members' => true]);
        $this->actingAs($this->userWithRole())->get('/')->assertDontSee('class="adsbygoogle"', false);
    }

    public function test_invalid_client_keeps_ads_off(): void
    {
        $this->enable(['client' => 'pub-<script>']);
        $this->get('/')->assertDontSee('adsbygoogle', false);
        $this->get('/ads.txt')->assertNotFound();
    }
}
