<?php

namespace Tests\Feature;

use Tests\TestCase;

// @2026-Alifiandra
class ApiTest extends TestCase
{
    public function test_prayer_times_endpoint(): void
    {
        $this->getJson('/api/v1/jadwal-sholat?kota=jakarta&tanggal=2026-10-18')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.zona', 'WIB')
            ->assertJsonPath('data.jadwal.zuhur', '11:40')
            ->assertJsonStructure(['data' => ['jadwal' => ['imsak', 'subuh', 'terbit', 'duha', 'zuhur', 'asar', 'magrib', 'isya'], 'hijriah']]);
    }

    public function test_prayer_times_validation(): void
    {
        $this->getJson('/api/v1/jadwal-sholat?lat=200&lng=10')->assertStatus(422);
    }

    public function test_surah_list_and_detail(): void
    {
        $this->getJson('/api/v1/surat')->assertOk()->assertJsonCount(114, 'data');
        $this->getJson('/api/v1/surat/1')->assertOk()->assertJsonPath('data.jumlah_ayat', 7)->assertJsonMissingPath('data.ayat.0.tafsir');
        $this->getJson('/api/v1/surat/1?tafsir=1')->assertOk()->assertJsonStructure(['data' => ['ayat' => [['nomor', 'arab', 'arti', 'tafsir']]]]);
        $this->getJson('/api/v1/surat/0')->assertNotFound();
    }

    public function test_content_endpoints(): void
    {
        $this->getJson('/api/v1/asmaul-husna')->assertOk()->assertJsonCount(99, 'data');
        $this->getJson('/api/v1/doa')->assertOk();
        $this->getJson('/api/v1/kota')->assertOk();
        $this->getJson('/api/v1/hijriah?tanggal=2026-03-20')->assertOk()->assertJsonPath('data.hijriah.month', 10)->assertJsonPath('data.hijriah.day', 1);
        $this->getJson('/api/v1/kiblat?lat=-6.2088&lng=106.8456')->assertOk()->assertJsonPath('data.arah_derajat', 295.15);
    }
}
