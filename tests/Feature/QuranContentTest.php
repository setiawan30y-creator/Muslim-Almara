<?php

namespace Tests\Feature;

use App\Models\ContentSource;
use App\Models\QuranVerse;
use App\Services\Quran\QuranImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Konten Qur'an (blueprint §16.3) — @2026-Alifiandra
class QuranContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_dataset_validates(): void
    {
        $importer = app(QuranImporter::class);
        $this->assertTrue($importer->validate(), implode("\n", $importer->errors));
    }

    public function test_import_is_idempotent_and_records_source(): void
    {
        $importer = app(QuranImporter::class);
        $importer->import([1, 112]);
        $importer->import([1, 112]);
        $this->assertSame(11, QuranVerse::query()->count());
        $this->assertSame(1, ContentSource::query()->where('key', 'quran-text-kemenag')->count());
        $src = ContentSource::query()->where('key', 'quran-text-kemenag')->first();
        $this->assertNotEmpty($src->checksum);
        $this->assertNotEmpty($src->license);
        // Terjemahan terhubung ke ayat yang benar
        $v = QuranVerse::query()->whereHas('surah', fn ($q) => $q->where('number', 112))->where('ayah_number', 1)->with('translationId')->first();
        $this->assertStringContainsString('Allah', $v->translationId->text);
    }

    public function test_quran_pages_and_search(): void
    {
        $this->seedAlmara();
        $this->get('/al-quran')->assertOk()->assertSee('Al-Fatihah');
        $this->get('/al-quran?tab=juz')->assertOk()->assertSee('Juz 30');
        $this->get('/al-quran/1')->assertOk()->assertSee('بِسْمِ اللّٰهِ', false);
        $this->get('/al-quran/115')->assertNotFound();
        $this->get('/al-quran/cari?q=Pemilik+hari+pembalasan')->assertOk()->assertSee('Al-Fatihah');
        $this->get('/al-quran/cari?q=2:255')->assertOk()->assertSee('2:255');
    }
}
