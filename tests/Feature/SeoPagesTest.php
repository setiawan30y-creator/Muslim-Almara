<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Halaman SEO: per ayat, per kota, imsakiyah, puasa, sitemap, robots, meta OG — @2026-Alifiandra
class SeoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_verse_page(): void
    {
        $this->get('/al-quran/2/255')->assertOk()
            ->assertSee('<title>Ayat Kursi — QS. Al-Baqarah Ayat 255: Arab, Latin &amp; Arti', false)
            ->assertSee('rel="canonical" href="'.url('/al-quran/2/255').'"', false)
            ->assertSee('BreadcrumbList', false)->assertSee('Tafsir ringkas')
            ->assertSee('href="'.url('/al-quran/2/254').'"', false)->assertSee('href="'.url('/al-quran/2/256').'"', false)
            ->assertSee('data-share-image', false);
        $this->get('/al-quran/1/7')->assertOk()->assertSee(url('/al-quran/2/1'));
        $this->get('/al-quran/1/8')->assertNotFound();
        $this->get('/al-quran/1?tampilan=ayat')->assertOk()->assertSee('href="'.url('/al-quran/1/1').'"', false);
    }

    public function test_city_prayer_imsakiyah_and_fasting_pages(): void
    {
        $this->get('/jadwal-sholat/bandung')->assertOk()->assertSee('Jadwal Sholat Bandung Hari Ini')
            ->assertSee('rel="canonical" href="'.url('/jadwal-sholat/bandung').'"', false)->assertSee(url('/jadwal-sholat/surabaya'));
        $this->get('/jadwal-sholat/kota-fiktif')->assertNotFound();
        $this->get('/jadwal-sholat')->assertOk()->assertSee('Jadwal sholat kota lain');

        $res = $this->get('/imsakiyah/jakarta')->assertOk()->assertSee('Imsakiyah Ramadan')->assertSee('Magrib (buka)');
        $this->assertGreaterThanOrEqual(29, substr_count($res->getContent(), '<tr class='));
        $this->get('/imsakiyah')->assertOk();

        $this->get('/puasa-sunnah')->assertOk()->assertSee('Kalender Puasa')->assertSee('Puasa Senin/Kamis')->assertSee('HR. Muslim no. 1162');
        $this->assertSame(['haram'], \App\Services\Calendar\FastingCalendar::classify(\Carbon\CarbonImmutable::parse('2026-11-02'), 12, 11));
        $this->assertSame(['arafah', 'senin_kamis'], \App\Services\Calendar\FastingCalendar::classify(\Carbon\CarbonImmutable::parse('2026-11-02'), 12, 9));
    }

    public function test_sitemap_robots_and_open_graph(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->getContent();
        $this->assertStringContainsString(url('/al-quran/114/6'), $xml);
        $this->assertStringContainsString(url('/jadwal-sholat/medan'), $xml);
        $this->assertStringContainsString(url('/panduan/tahlil/tahlil-ayat-kursi'), $xml);
        $this->assertGreaterThan(6236, substr_count($xml, '<loc>'));

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'))->assertSee('Disallow: /admin');
        $this->get('/')->assertOk()->assertSee('og:image', false)->assertSee('og-image.png', false)->assertSee('"@type":"WebSite"', false)->assertSee('twitter:card', false);
    }
}
