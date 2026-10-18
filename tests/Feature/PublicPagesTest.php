<?php

namespace Tests\Feature;

use App\Models\DuaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// @2026-Alifiandra
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return array_map(fn ($u) => [$u], [
            '/', '/ibadah', '/belajar', '/jadwal-sholat', '/jadwal-sholat?kota=makassar&bulan=2027-03', '/lokasi',
            '/kiblat', '/doa-dzikir', '/doa-dzikir?tab=doa', '/tasbih', '/tasbih?dzikir=tasbih-33', '/kalender', '/kalender?bulan=2027-02',
            '/zakat', '/asmaul-husna', '/profil', '/pengaturan', '/pengaturan/tampilan', '/pengaturan/al-quran',
            '/pengaturan/jadwal-sholat', '/pengaturan/beranda', '/masuk', '/daftar', '/lupa-sandi',
            '/tentang', '/kebijakan-privasi', '/syarat-ketentuan', '/offline', '/sitemap.xml',
        ]);
    }

    #[DataProvider('pages')]
    public function test_page_loads(string $url): void
    {
        $this->seedAlmara();
        $this->get($url)->assertOk();
    }

    public function test_old_urls_redirect(): void
    {
        $this->get('/doa')->assertRedirect('/doa-dzikir');
        $this->get('/menu')->assertRedirect('/ibadah');
    }

    public function test_doa_page_shows_arabic_latin_meaning_and_source(): void
    {
        $this->seedAlmara();
        $d = DuaItem::query()->where('slug', 'sebelum-tidur')->first();
        $this->get('/doa-dzikir?tab=doa')->assertOk()
            ->assertSee($d->arabic_text, false)->assertSee($d->transliteration)->assertSee($d->translation)->assertSee('HR. Al-Bukhari no. 6324');
        $this->get('/doa-dzikir/doa/sebelum-tidur')->assertOk();
    }

    public function test_unpublished_content_is_hidden(): void
    {
        $this->seedAlmara(publishContent: false);
        $this->get('/doa-dzikir?tab=doa')->assertOk()->assertSee('Belum ada doa yang diterbitkan')->assertDontSee('Bismikallāhumma');
        $this->get('/doa-dzikir/doa/sebelum-tidur')->assertNotFound();
        $this->getJson('/api/v1/dua/items')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_city_choice_cookie_and_location_redirect_safety(): void
    {
        $this->seedAlmara();
        $this->get('/jadwal-sholat?kota=jayapura')->assertOk()->assertSee('Jayapura')->assertSee('WIT')->assertCookie('almara_lokasi');
        $this->get('/kiblat?lat=-6.2&lng=106.8&tz=7')->assertOk()->assertSee('Lokasi perangkat');
        $this->get('/lokasi?kembali=//evil.example')->assertOk()->assertDontSee('evil.example');
    }

    public function test_security_headers_present(): void
    {
        $this->seedAlmara();
        $r = $this->get('/');
        $r->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Request-Id');
        $this->assertNull($r->headers->get('X-Almara-Private'));
    }
}
