<?php

namespace Tests\Feature;

use Tests\TestCase;

// @2026-Alifiandra
class PagesTest extends TestCase
{
    public static function pages(): array
    {
        return [
            ['/'], ['/jadwal-sholat'], ['/jadwal-sholat?kota=makassar&bulan=2027-03'], ['/lokasi'],
            ['/al-quran'], ['/al-quran/1'], ['/al-quran/2'], ['/al-quran/114'],
            ['/kiblat'], ['/doa'], ['/asmaul-husna'], ['/tasbih'], ['/kalender'], ['/kalender?bulan=2027-02'],
            ['/zakat'], ['/menu'], ['/tentang'], ['/kebijakan-privasi'], ['/syarat-ketentuan'], ['/sitemap.xml'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_public_page_loads(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_unknown_surah_returns_404(): void
    {
        $this->get('/al-quran/115')->assertNotFound();
    }

    public function test_city_choice_is_remembered_in_cookie(): void
    {
        $this->get('/jadwal-sholat?kota=jayapura')->assertOk()->assertSee('Jayapura')->assertSee('WIT')->assertCookie('ma_lokasi');
    }

    public function test_coordinates_are_accepted(): void
    {
        $this->get('/kiblat?lat=-6.2&lng=106.8&tz=7')->assertOk()->assertSee('Lokasi Anda');
    }

    public function test_location_back_url_is_not_open_redirect(): void
    {
        $this->get('/lokasi?kembali=//evil.example')->assertOk()->assertDontSee('evil.example');
    }
}
