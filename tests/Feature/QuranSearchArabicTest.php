<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Support\ArabicText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Pencarian huruf Arab + fitur pemutar/bagikan gambar — @2026-Alifiandra
class QuranSearchArabicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_normalizer(): void
    {
        $this->assertSame('الله لا اله الا هو الحي القيوم', ArabicText::normalize('اَللّٰهُ لَآ اِلٰهَ اِلَّا هُوَۚ اَلْحَيُّ الْقَيُّوْمُ ەۚ'));
        $this->assertSame('رحمه', ArabicText::normalize('رَحْمَةً'));
        $this->assertTrue(ArabicText::hasArabic('cari الرحمن'));
        $this->assertFalse(ArabicText::hasArabic('sabar'));
    }

    public function test_search_by_arabic_letters_ignores_harakat(): void
    {
        $this->assertNotNull(QuranVerse::query()->value('text_plain'));
        $this->get('/al-quran/cari?q='.urlencode('الحي القيوم'))->assertOk()->assertSee('QS. Al-Baqarah')->assertSee('255');
        // Dengan harakat juga ditemukan
        $res = $this->get('/al-quran/cari?q='.urlencode('الرَّحْمٰنِ الرَّحِيْمِ'))->assertOk();
        $this->assertStringContainsString('Al-Fatihah', $res->getContent());
        $this->get('/al-quran/cari?q='.urlencode('قل هو الله احد'))->assertOk()->assertSee('Al-Ikhlas');
    }

    public function test_player_extras_and_share_image_buttons(): void
    {
        config(['almara.audio.quran_foundation.enabled' => true, 'almara.audio.quran_foundation.mock' => true]);
        $this->get('/al-quran/1?tampilan=ayat')->assertOk()
            ->assertSee('data-audio-rate', false)->assertSee('data-audio-sleep', false)->assertSee('data-audio-autonext', false)
            ->assertSee('data-range-from', false)->assertSee('max="7"', false)
            ->assertSee('data-share-image', false)->assertSee('href="'.url('/al-quran/1/1').'"', false);
        // Surah terakhir: tidak ada surah berikutnya → opsi lanjut otomatis nonaktif
        $this->get('/al-quran/114?tampilan=ayat')->assertOk()->assertSee('data-audio-autonext disabled', false);
    }
}
