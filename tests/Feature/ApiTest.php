<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// API v1 publik (blueprint §7) — @2026-Alifiandra
class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    public function test_health(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('data.status', 'ok')->assertJsonStructure(['data', 'meta'])->assertHeader('X-Request-Id');
    }

    public function test_quran_endpoints(): void
    {
        $this->getJson('/api/v1/quran/surahs')->assertOk()->assertJsonPath('data.0.number', 1);
        $this->getJson('/api/v1/quran/surahs/112')->assertOk()->assertJsonPath('data.ayah_count', 4)->assertJsonPath('meta.source.key', 'quran-text-kemenag');
        $this->getJson('/api/v1/quran/surahs/999')->assertNotFound()->assertJsonStructure(['message']);
        $this->getJson('/api/v1/quran/verses?surah=2&from=255&to=255&tafsir=1')->assertOk()
            ->assertJsonPath('data.0.ayah', 255)->assertJsonPath('data.0.juz', 3)->assertJsonStructure(['data' => [['text_uthmani', 'translation' => ['text'], 'tafsir']], 'meta' => ['total', 'per_page']]);
        $this->getJson('/api/v1/quran/verses')->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['surah']]);
        $this->getJson('/api/v1/quran/verses?surah=2&per_page=500')->assertStatus(422);
        $this->getJson('/api/v1/quran/search?q=Pemilik hari pembalasan')->assertOk()->assertJsonPath('data.0.surah', 1);
        $this->getJson('/api/v1/quran/translations')->assertOk();
    }

    public function test_prayer_and_qibla_endpoints(): void
    {
        $this->getJson('/api/v1/prayer/methods')->assertOk()->assertJsonFragment(['key' => 'kemenag']);
        $this->getJson('/api/v1/prayer/times?city=jakarta&date=2026-10-18')->assertOk()
            ->assertJsonPath('data.times.zuhur', '11:40')->assertJsonPath('data.timezone', 'WIB')->assertJsonPath('meta.method.key', 'kemenag');
        $this->getJson('/api/v1/prayer/times?lat=-6.2088&lng=106.8456&tz=7&date=2026-10-18&method=mwl&asr=hanafi')->assertOk()
            ->assertJsonPath('meta.method.key', 'mwl')->assertJsonPath('meta.location.basis', 'device');
        $this->getJson('/api/v1/prayer/times?lat=200&lng=10')->assertStatus(422);
        $this->getJson('/api/v1/prayer/times?method=palsu')->assertStatus(422);
        $this->getJson('/api/v1/qibla?lat=-6.2088&lng=106.8456')->assertOk()->assertJsonPath('data.bearing', 295.15);
    }

    public function test_worship_content_and_events(): void
    {
        $this->getJson('/api/v1/dhikr/categories')->assertOk()->assertJsonFragment(['slug' => 'setelah-shalat']);
        $this->getJson('/api/v1/dhikr/items?category=setelah-shalat')->assertOk()
            ->assertJsonStructure(['data' => [['arabic_text', 'transliteration', 'translation' => ['text'], 'source' => ['reference'], 'review_status', 'content_version']]]);
        $this->getJson('/api/v1/dua/items?per_page=5')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.per_page', 5);
        $this->getJson('/api/v1/islamic-events')->assertOk()->assertJsonPath('meta.date_basis', 'calculated');
    }
}
