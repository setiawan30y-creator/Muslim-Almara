<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\QuranAudioTrack;
use App\Models\QuranReciter;
use App\Models\Role;
use App\Services\Quran\Audio\AudioWorkflow;
use App\Services\Quran\Audio\SampleTone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Audio qari: Qari ALMARA (unggah → review → terbit) & Quran.Foundation — @2026-Alifiandra
class QuranAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
        Storage::fake('local');
    }

    private function reciter(): QuranReciter
    {
        return QuranReciter::query()->create(['slug' => 'qari-uji', 'name' => 'Qari Uji', 'style' => 'Murattal', 'license_note' => 'Rekaman internal']);
    }

    private function wav(int $ayat = 3): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surah.wav', SampleTone::wav($ayat));
    }

    private function upload($user, QuranReciter $r, int $surah = 1, array $extra = [])
    {
        return $this->actingAs($user)->post('/admin/audio', $extra + [
            'reciter_id' => $r->id, 'surah_number' => $surah, 'audio' => $this->wav(), 'confirm_rights' => '1',
            'segments' => "1 0 2\n2 0:02 0:04\n3 4 6",
        ]);
    }

    public function test_reader_without_audio_shows_note_and_no_player(): void
    {
        $this->get('/al-quran/1')->assertOk()->assertSee('Audio murattal untuk surah ini belum tersedia')->assertDontSee('data-audio-play', false);
        $this->getJson('/api/v1/quran/audio/surah/1')->assertNotFound();
    }

    public function test_full_upload_review_publish_and_stream_flow(): void
    {
        $r = $this->reciter();
        $editor = $this->userWithRole(Role::EDITOR);
        $reviewer = $this->userWithRole(Role::REVIEWER);

        // Pengguna biasa tidak boleh mengunggah
        $this->upload($this->userWithRole(), $r)->assertForbidden();

        $this->upload($editor, $r)->assertRedirect();
        $track = QuranAudioTrack::query()->firstOrFail();
        $this->assertSame(ContentStatus::Draft, $track->review_status);
        $this->assertSame('audio/wav', $track->mime);
        $this->assertSame(6, $track->duration_seconds);
        $this->assertSame(64, strlen($track->checksum));
        $this->assertCount(3, $track->segments);
        Storage::disk('local')->assertExists($track->path);

        // Karantina: belum bisa diakses publik, belum muncul di pemutar
        auth()->logout();
        $this->get('/al-quran/audio/rekaman/'.$track->id)->assertNotFound();
        $this->getJson('/api/v1/quran/audio/surah/1')->assertNotFound();

        // Pengunggah tidak bisa menyetujui rekamannya sendiri
        $this->actingAs($editor)->post("/admin/audio/{$track->id}/submit")->assertRedirect();
        $this->actingAs($editor)->post("/admin/audio/{$track->id}/approve-publish")->assertForbidden();
        $this->actingAs($reviewer)->post("/admin/audio/{$track->id}/approve-publish")->assertRedirect();
        $this->assertSame(ContentStatus::Published, $track->fresh()->review_status);
        $this->assertDatabaseHas('content_reviews', ['content_type' => 'audio', 'content_id' => $track->id, 'decision' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'audio.approve-publish']);

        // Publik: tanpa login, mendukung Range
        auth()->logout();
        $this->get('/al-quran/audio/rekaman/'.$track->id)->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->get('/al-quran/audio/rekaman/'.$track->id, ['Range' => 'bytes=0-99'])->assertStatus(206)->assertHeader('Content-Length', '100');

        $res = $this->getJson('/api/v1/quran/audio/surah/1')->assertOk()
            ->assertJsonPath('data.reciter.key', 'almara:qari-uji')
            ->assertJsonPath('data.segments.1.ayah', 2)
            ->assertJsonPath('data.segments.1.start_ms', 2000);
        $this->assertStringContainsString('/al-quran/audio/rekaman/'.$track->id, $res->json('data.url'));
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));

        $this->get('/al-quran/1')->assertOk()->assertSee('data-audio-play', false)->assertSee('Qari Uji')->assertSee('data-play-ayah="1"', false);
        // Surah lain belum ada rekamannya
        $this->getJson('/api/v1/quran/audio/surah/2')->assertNotFound();

        // Versi baru → versi lama diarsipkan setelah terbit
        $this->upload($editor, $r, 1, ['audio' => UploadedFile::fake()->createWithContent('v2.wav', SampleTone::wav(4)), 'segments' => ''])->assertRedirect();
        $v2 = QuranAudioTrack::query()->where('version', 2)->firstOrFail();
        $this->actingAs($editor)->post("/admin/audio/{$v2->id}/submit");
        $this->actingAs($reviewer)->post("/admin/audio/{$v2->id}/approve-publish");
        $this->assertSame(ContentStatus::Archived, $track->fresh()->review_status);
        $this->assertSame(ContentStatus::Published, $v2->fresh()->review_status);
    }

    public function test_upload_validation_rejects_bad_files_and_segments(): void
    {
        $r = $this->reciter();
        $editor = $this->userWithRole(Role::EDITOR);

        $this->upload($editor, $r, 1, ['audio' => UploadedFile::fake()->createWithContent('bukan.mp3', str_repeat('teks biasa ', 50))])->assertSessionHasErrors('audio');
        $this->upload($editor, $r, 1, ['confirm_rights' => null])->assertSessionHasErrors('confirm_rights');
        $this->upload($editor, $r, 1, ['segments' => '9 0 2'])->assertSessionHasErrors('segments'); // Al-Fatihah hanya 7 ayat
        $this->upload($editor, $r, 1, ['segments' => '1 0 99'])->assertSessionHasErrors('segments'); // melebihi durasi
        $this->assertSame(0, QuranAudioTrack::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('quran-audio/qari-uji'));

        // Berkas identik tidak diunggah dua kali
        $this->upload($editor, $r)->assertSessionHasNoErrors();
        $this->upload($editor, $r)->assertSessionHasErrors('audio');
    }

    public function test_signature_sniffing_and_segment_parser(): void
    {
        $dir = sys_get_temp_dir();
        $cases = ['ID3'.str_repeat("\0", 13) => 'audio/mpeg', "\xFF\xFB\x90\x00".str_repeat("\0", 12) => 'audio/mpeg',
            "\0\0\0\x20ftypM4A ".str_repeat("\0", 4) => 'audio/mp4', 'OggS'.str_repeat("\0", 12) => 'audio/ogg', '<?php echo 1; ?>xxxx' => null];
        foreach ($cases as $bytes => $mime) {
            $f = tempnam($dir, 'snf');
            file_put_contents($f, $bytes);
            $this->assertSame($mime, AudioWorkflow::sniff($f)[0] ?? null);
            unlink($f);
        }
        $this->assertSame([
            ['ayah_number' => 1, 'start_ms' => 0, 'end_ms' => 6500],
            ['ayah_number' => 2, 'start_ms' => 6500, 'end_ms' => 72250],
        ], AudioWorkflow::parseSegments("2 0:06.5 1:12.25\n1 0 6.5", 7, 100));
    }

    public function test_quran_foundation_provider_with_server_side_credentials(): void
    {
        config(['almara.audio.quran_foundation' => array_merge(config('almara.audio.quran_foundation'), [
            'enabled' => true, 'mock' => false, 'client_id' => 'id-uji', 'client_secret' => 'rahasia-uji',
        ])]);
        Cache::flush();
        Http::fake([
            'oauth2.quran.foundation/*' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600]),
            'apis.quran.foundation/content/api/v4/resources/chapter_reciters*' => Http::response(['reciters' => [
                ['id' => 7, 'name' => 'Reciter A', 'style' => ['name' => 'Murattal'], 'translated_name' => ['name' => 'Qari A']],
            ]]),
            'apis.quran.foundation/content/api/v4/chapter_recitations/7/1*' => Http::response(['audio_file' => [
                'id' => 1, 'chapter_id' => 1, 'audio_url' => 'https://download.example.org/7/001.mp3',
                'timestamps' => [
                    ['verse_key' => '1:1', 'timestamp_from' => 0, 'timestamp_to' => 5000],
                    ['verse_key' => '1:2', 'timestamp_from' => 5000, 'timestamp_to' => 9000],
                ],
            ]]),
        ]);

        $this->getJson('/api/v1/quran/audio/reciters')->assertOk()->assertJsonPath('data.0.key', 'qf:7')->assertJsonPath('data.0.name', 'Qari A')
            ->assertJsonPath('meta.default', 'qf:7');
        $res = $this->getJson('/api/v1/quran/audio/surah/1?reciter=qf:7')->assertOk()
            ->assertJsonPath('data.url', 'https://download.example.org/7/001.mp3')
            ->assertJsonPath('data.segments.1', ['ayah' => 2, 'start_ms' => 5000, 'end_ms' => 9000])
            ->assertJsonPath('data.attribution', 'Quran data provided by Quran.Foundation')
            ->assertJsonPath('data.offline_allowed', false);
        $this->assertStringNotContainsString('rahasia-uji', $res->getContent());
        $this->assertStringNotContainsString('tok-123', $res->getContent());

        Http::assertSent(fn ($req) => str_contains($req->url(), 'chapter_recitations') && $req->hasHeader('x-auth-token', 'tok-123') && $req->hasHeader('x-client-id', 'id-uji'));
        $this->get('/al-quran/1')->assertOk()->assertSee('data-audio-play', false)->assertSee('Qari A')->assertDontSee('rahasia-uji');

        // Kunci tidak valid ditolak
        $this->getJson('/api/v1/quran/audio/surah/1?reciter=../../etc')->assertStatus(422);
    }

    public function test_mock_mode_and_reciter_preference(): void
    {
        config(['almara.audio.quran_foundation.enabled' => true, 'almara.audio.quran_foundation.mock' => true]);

        $this->getJson('/api/v1/quran/audio/surah/1')->assertOk()->assertJsonPath('data.simulated', true)->assertJsonPath('data.reciter.key', 'qf:0');
        $wav = $this->get('/al-quran/audio/simulasi/1.wav')->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->assertStringStartsWith('RIFF', file_get_contents($wav->baseResponse->getFile()->getPathname()));

        // Preferensi qari tamu tersimpan di cookie & divalidasi
        $this->postJson('/pengaturan/al-quran', ['reciter' => 'qf:0'])->assertOk()->assertJsonPath('data.reciter', 'qf:0');
        $this->postJson('/pengaturan/al-quran', ['reciter' => 'x; DROP'])->assertStatus(422);

        $user = $this->userWithRole();
        $this->actingAs($user)->putJson('/api/v1/quran/reading-preferences', ['reciter' => 'almara:qari-uji'])->assertOk();
        $this->assertSame('almara:qari-uji', $user->quranPreference()->value('reciter'));

        // Mode simulasi mati → nada uji tidak tersedia
        config(['almara.audio.quran_foundation.mock' => false]);
        $this->get('/al-quran/audio/simulasi/1.wav')->assertNotFound();
    }
}
