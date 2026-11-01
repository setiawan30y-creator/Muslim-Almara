<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Services\Quran\QuranDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Pilihan qari Islamic Network, mode Mushaf, impor transliterasi — @2026-Alifiandra
class QuranExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
        Cache::flush();
    }

    public function test_islamic_network_reciters_and_per_ayah_tracks(): void
    {
        config(['almara.audio.islamic_network.enabled' => true]);
        Http::fake([
            'api.alquran.cloud/v1/edition/format/audio' => Http::response(['data' => [
                ['identifier' => 'ar.alafasy', 'language' => 'ar', 'name' => 'مشاري العفاسي', 'englishName' => 'Alafasy', 'format' => 'audio'],
                ['identifier' => 'ar.husarymujawwad', 'language' => 'ar', 'name' => 'الحصري (المجود)', 'englishName' => 'Husary (Mujawwad)', 'format' => 'audio'],
                ['identifier' => 'en.walk', 'language' => 'en', 'name' => 'Ibrahim Walk', 'englishName' => 'Ibrahim Walk', 'format' => 'audio'],
            ]]),
            'api.alquran.cloud/v1/surah/112/ar.alafasy' => Http::response(['data' => ['ayahs' => [
                ['numberInSurah' => 1, 'audio' => 'https://cdn.islamic.network/quran/audio/128/ar.alafasy/6222.mp3', 'audioSecondary' => ['https://cdn.islamic.network/quran/audio/64/ar.alafasy/6222.mp3']],
                ['numberInSurah' => 2, 'audio' => 'https://cdn.islamic.network/quran/audio/128/ar.alafasy/6223.mp3'],
                ['numberInSurah' => 3, 'audio' => 'https://cdn.islamic.network/quran/audio/128/ar.alafasy/6224.mp3'],
                ['numberInSurah' => 4, 'audio' => 'https://cdn.islamic.network/quran/audio/128/ar.alafasy/6225.mp3'],
            ]]]),
            'api.alquran.cloud/v1/surah/113/ar.alafasy' => Http::response(['data' => ['ayahs' => [
                ['numberInSurah' => 1, 'audio' => 'https://evil.example/x.mp3'],
            ]]]),
        ]);

        $this->getJson('/api/v1/quran/audio/reciters')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.key', 'in:ar.alafasy')->assertJsonPath('data.1.style', 'Mujawwad')
            ->assertJsonPath('meta.default', 'in:ar.alafasy'); // bawaan: Alafasy

        $this->getJson('/api/v1/quran/audio/surah/112')->assertOk()
            ->assertJsonPath('data.reciter.provider', 'islamic_network')
            ->assertJsonCount(4, 'data.tracks')
            ->assertJsonPath('data.tracks.0.alt', 'https://cdn.islamic.network/quran/audio/64/ar.alafasy/6222.mp3')
            ->assertJsonPath('data.tracks.3.ayah', 4);
        $this->assertSame(6222, QuranDataset::globalAyah(112, 1));

        // URL di luar CDN resmi ditolak
        $this->getJson('/api/v1/quran/audio/surah/113?reciter=in:ar.alafasy')->assertNotFound();
        $this->getJson('/api/v1/quran/audio/surah/1?reciter=in:../../x')->assertStatus(422);

        $this->get('/al-quran/112')->assertOk()->assertSee('data-audio-play', false)->assertSee('Qari pilihan')->assertSee('Husary (Mujawwad)');

        // Admin dapat membatasi daftar qari
        config(['almara.audio.islamic_network.reciters' => 'ar.husarymujawwad']);
        $this->getJson('/api/v1/quran/audio/reciters')->assertJsonCount(1, 'data')->assertJsonPath('data.0.key', 'in:ar.husarymujawwad');
    }

    public function test_fallback_reciters_when_api_unreachable(): void
    {
        config(['almara.audio.islamic_network.enabled' => true]);
        Http::fake(['api.alquran.cloud/*' => Http::response('', 500)]);
        $res = $this->getJson('/api/v1/quran/audio/reciters')->assertOk();
        $this->assertGreaterThanOrEqual(10, count($res->json('data')));
    }

    public function test_mushaf_mode_and_latin_import(): void
    {
        $this->get('/al-quran/113')->assertOk()->assertSee('data-mushaf', false)->assertSee('data-mushaf-detail', false)->assertSee('data-reading-mode="verse"', false);
        $this->get('/pengaturan/al-quran')->assertOk()->assertSee('Belum tersedia — admin perlu mengimpor');

        $dir = storage_path('framework/testing');
        @mkdir($dir, 0777, true);
        $file = $dir.'/latin.json';
        file_put_contents($file, json_encode(['112' => ['1' => 'Qul huwallāhu aḥad', '2' => 'Allāhuṣ-ṣamad']]));
        $this->artisan('almara:quran:import-latin', ['file' => $file])->assertFailed(); // wajib --license
        file_put_contents($dir.'/salah.json', json_encode([['surah' => 112, 'ayah' => 9, 'text' => 'x']]));
        $this->artisan('almara:quran:import-latin', ['file' => $dir.'/salah.json', '--license' => 'uji'])->assertFailed();
        $this->artisan('almara:quran:import-latin', ['file' => $file, '--license' => 'Izin uji', '--name' => 'Transliterasi uji'])->assertSuccessful();

        $this->assertSame('Qul huwallāhu aḥad', QuranVerse::query()->where('ayah_number', 1)->whereHas('surah', fn ($q) => $q->where('number', 112))->value('text_latin'));
        $this->assertDatabaseHas('content_sources', ['key' => 'quran-transliteration', 'license' => 'Izin uji']);
        $this->get('/pengaturan/al-quran')->assertOk()->assertDontSee('Belum tersedia — admin perlu mengimpor');

        $this->post('/pengaturan/al-quran', ['reading_mode' => 'verse', 'show_transliteration' => '1', 'show_translation' => '1', 'audio_mode' => 'surah'])->assertRedirect();
        $cookie = json_encode(['reading_mode' => 'verse', 'show_transliteration' => true, 'show_translation' => true]);
        $this->withUnencryptedCookie('almara_quran', $cookie)->get('/al-quran/112')->assertOk()->assertSee('Qul huwallāhu aḥad')->assertSee('ayah__latin', false);
    }
}
