<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\GuideItem;
use App\Models\QuranVerse;
use App\Models\Role;
use App\Models\UserBookmark;
use App\Models\UserNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Panduan, hafalan, terjemah, masjid terdekat, jurnal & koleksi — @2026-Alifiandra
class LearningFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
    }

    private function verse(int $surah, int $ayah): QuranVerse
    {
        return QuranVerse::query()->where('ayah_number', $ayah)->whereHas('surah', fn ($q) => $q->where('number', $surah))->firstOrFail();
    }

    public function test_hub_pages_have_no_coming_soon_cards_and_link_all_features(): void
    {
        $this->get('/belajar')->assertOk()->assertDontSee('Segera')
            ->assertSee(route('guide.module', 'tajwid'))->assertSee(route('hafalan'))->assertSee(route('translate'))->assertSee(route('profile.notes'));
        $this->get('/ibadah')->assertOk()->assertDontSee('Segera')
            ->assertSee(route('mosques'))->assertSee(route('guide.module', 'umroh'))->assertSee(route('guide.module', 'haji'))->assertSee(route('guide.module', 'ziarah'));
    }

    public function test_published_guides_render_with_reading_example_and_quiz(): void
    {
        $this->get('/panduan')->assertOk()->assertSee('Belajar Tajwid')->assertSee('Panduan Ziarah Kubur');
        $this->get('/panduan/tajwid')->assertOk()->assertSee('Izhar halqi')->assertSee('Iqlab');
        $this->get('/panduan/umroh/umroh-talbiyah')->assertOk()->assertSee('لَبَّيْكَ', false)->assertSee('HR. Bukhari no. 1549');
        // Contoh ayat diambil dari teks Kemenag di database (QS. 112:1 diimpor pada pengujian)
        $this->get('/panduan/tajwid/tajwid-lafaz-allah-ra')->assertOk()->assertSee('Contoh ayat')->assertSee('QS. Al-Ikhlas: 1')->assertSee('data-quiz', false);
        $this->get('/panduan/ziarah/ziarah-salam')->assertOk()->assertSee('HR. Muslim no. 975');
        // Daftar berisi huruf Arab tidak boleh terpotong (pemisah baris multibyte)
        $this->get('/panduan/tajwid/tajwid-izhar-halqi')->assertOk()->assertSee('<li>Contoh: مِنْ خَوْفٍ (min khauf)', false)->assertDontSee("\u{FFFD}");
        $this->get('/panduan/lainnya')->assertNotFound();
        $this->get('/panduan/haji/umroh-talbiyah')->assertNotFound(); // slug harus sesuai modul
    }

    public function test_unpublished_guides_are_hidden_and_go_through_review(): void
    {
        GuideItem::query()->update(['review_status' => ContentStatus::Review->value]);
        $this->get('/panduan/ziarah')->assertOk()->assertSee('Materi sedang ditinjau');
        $this->get('/panduan/ziarah/ziarah-salam')->assertNotFound();

        $item = GuideItem::query()->where('slug', 'ziarah-salam')->firstOrFail();
        $admin = $this->userWithRole(Role::ADMIN);
        $this->actingAs($admin)->get('/admin/konten/panduan?q=Salam')->assertOk()->assertSee('Salam ketika memasuki pemakaman');
        $this->actingAs($admin)->get('/admin/konten/panduan/'.$item->id)->assertOk()->assertSee('Penjelasan');
        $this->actingAs($admin)->post("/admin/konten/panduan/{$item->id}/approve-publish")->assertRedirect();
        $this->assertSame(ContentStatus::Published, $item->fresh()->review_status);
        $this->get('/panduan/ziarah/ziarah-salam')->assertOk();

        // Editor membuat materi tanpa bacaan Arab → cukup penjelasan
        $editor = $this->userWithRole(Role::EDITOR);
        $this->actingAs($editor)->post('/admin/konten/panduan', [
            'category_id' => $item->category_id, 'title' => 'Materi uji', 'slug' => 'materi-uji', 'body' => "Paragraf satu.\n\n- butir a\n- butir b",
            'source_reference' => 'Rujukan uji', 'source_kind' => 'general', 'example_ref' => '1:1',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $new = GuideItem::query()->where('slug', 'materi-uji')->firstOrFail();
        $this->assertSame(ContentStatus::Draft, $new->review_status);
        $this->assertSame([['type' => 'p', 'lines' => ['Paragraf satu.']], ['type' => 'list', 'lines' => ['butir a', 'butir b']]], $new->bodyBlocks());
        // Bacaan Arab tanpa arti ditolak
        $this->actingAs($editor)->post('/admin/konten/panduan', [
            'category_id' => $item->category_id, 'title' => 'X', 'slug' => 'materi-x', 'body' => 'Isi', 'arabic_text' => 'بِسْمِ اللّٰهِ',
            'source_reference' => 'R', 'source_kind' => 'general',
        ])->assertSessionHasErrors(['transliteration', 'translation']);
    }

    public function test_memorization_spaced_repetition_and_privacy(): void
    {
        $this->get('/hafalan')->assertOk()->assertSee('Mulai hafalan')->assertSee('Masuk');
        $this->get('/hafalan/112')->assertOk()->assertSee('data-hafalan', false)->assertSee('data-guest', false);
        $this->postJson('/api/v1/me/memorization', ['verse_id' => 1, 'grade' => 'lancar'])->assertUnauthorized();

        $user = $this->userWithRole();
        $v = $this->verse(112, 1);
        $grade = fn ($g) => $this->actingAs($user)->postJson('/api/v1/me/memorization', ['verse_id' => $v->id, 'grade' => $g])->assertOk()->json('data');

        $this->assertSame([1, 'learning'], [($d = $grade('lancar'))['interval_days'], $d['status']]);
        $this->assertSame(3, $grade('lancar')['interval_days']);
        $d = $grade('lancar');
        $this->assertGreaterThanOrEqual(7, $d['interval_days']);
        $this->assertSame('memorized', $d['status']);
        $d = $grade('lupa');
        $this->assertSame([1, 'learning'], [$d['interval_days'], $d['status']]);
        $this->assertSame(today()->addDay()->toDateString(), $d['next_review_on']);
        $this->actingAs($user)->postJson('/api/v1/me/memorization', ['verse_id' => $v->id, 'grade' => 'asal'])->assertStatus(422);

        // Jatuh tempo → muncul di murojaah
        $user->memorizations()->update(['next_review_on' => today()]);
        $this->actingAs($user)->get('/hafalan')->assertOk()->assertSee('Al-Ikhlas')->assertSee('Murojaah 1 ayat');
        $this->actingAs($user)->get('/hafalan/112?murojaah=1')->assertOk()->assertSee('112:1')->assertDontSee('112:2');
        $this->actingAs($user)->getJson('/api/v1/me/memorization?surah=112')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.due', 1);

        // Data orang lain tidak terlihat; ekspor memuat hafalan
        $other = $this->userWithRole();
        $this->actingAs($other)->getJson('/api/v1/me/memorization')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson('/api/v1/me/data-export')->assertOk()->assertJsonPath('data.memorization.0.status', 'learning');

        $this->actingAs($user)->deleteJson('/api/v1/me/memorization?surah=112')->assertOk()->assertJsonPath('data.deleted', 1);
        $this->get('/hafalan/999')->assertNotFound();
    }

    public function test_translate_page_phrasebook_and_optional_provider(): void
    {
        $this->get('/terjemah')->assertOk()->assertSee('Kumpulan frasa')->assertSee('أَيْنَ الْحَمَّامُ؟', false)->assertDontSee('data-translate', false);
        $this->postJson('/api/v1/translate', ['q' => 'Terima kasih', 'source' => 'id', 'target' => 'ar'])->assertStatus(503);

        config(['almara.translate.url' => 'https://translate.contoh.test']);
        Http::fake(['translate.contoh.test/translate' => Http::response(['translatedText' => 'شكرا'])]);
        $this->get('/terjemah')->assertOk()->assertSee('data-translate', false);
        $this->postJson('/api/v1/translate', ['q' => 'Terima kasih', 'source' => 'id', 'target' => 'ar'])->assertOk()->assertJsonPath('data.text', 'شكرا');
        $this->postJson('/api/v1/translate', ['q' => 'x', 'source' => 'id', 'target' => 'id'])->assertStatus(422);
        $this->postJson('/api/v1/translate', ['q' => 'x', 'source' => 'id', 'target' => 'fr'])->assertStatus(422);
        Http::assertSent(fn ($r) => $r['q'] === 'Terima kasih' && $r['target'] === 'ar');
    }

    public function test_mosque_finder_page(): void
    {
        $this->get('/masjid-terdekat')->assertOk()->assertSee('data-mosques', false)->assertSee('OpenStreetMap')
            ->assertSee('overpass-api.de', false)->assertSee('leaflet', false);
    }

    public function test_journal_tags_and_collections(): void
    {
        $user = $this->userWithRole();
        $this->get('/profil/catatan')->assertRedirect('/masuk');

        $this->actingAs($user)->post('/profil/catatan', ['body' => 'Belajar sabar hari ini', 'tags' => ' Sabar, syukur ,sabar, <b>x</b>'])->assertRedirect();
        $note = UserNote::query()->firstOrFail();
        $this->assertSame('sabar,syukur,x', $note->tags);
        $this->actingAs($user)->get('/profil/catatan?tag=sabar')->assertOk()->assertSee('Belajar sabar hari ini')->assertSee('#syukur');
        $this->actingAs($user)->get('/profil/catatan?tag=lain')->assertOk()->assertDontSee('Belajar sabar hari ini');
        $this->actingAs($user)->put("/profil/catatan/{$note->id}/tag", ['tags' => 'doa'])->assertRedirect();
        $this->assertSame('doa', $note->fresh()->tags);
        $this->actingAs($user)->postJson('/api/v1/me/notes', ['body' => 'Lewat API', 'tags' => 'Tadabur'])->assertCreated()->assertJsonPath('data.tags.0', 'tadabur');

        // Koleksi bookmark
        $bm = $user->bookmarks()->create(['verse_id' => $this->verse(1, 1)->id]);
        $this->actingAs($user)->post('/profil/koleksi', ['name' => 'Ayat pembuka'])->assertRedirect();
        $col = $user->collections()->firstOrFail();
        $this->actingAs($user)->put("/profil/bookmark/{$bm->id}/koleksi", ['collection_id' => $col->id])->assertRedirect();
        $this->assertSame($col->id, $bm->fresh()->collection_id);
        $this->actingAs($user)->get('/profil/bookmark?koleksi='.$col->id)->assertOk()->assertSee('Ayat pembuka')->assertSee('QS. Al-Fatihah');
        $this->actingAs($user)->get('/profil/bookmark?koleksi=tanpa')->assertOk()->assertSee('Koleksi ini masih kosong');

        // Tidak bisa menyentuh data pengguna lain
        $other = $this->userWithRole();
        $this->actingAs($other)->put("/profil/bookmark/{$bm->id}/koleksi", ['collection_id' => null])->assertNotFound();
        $this->actingAs($other)->put("/profil/catatan/{$note->id}/tag", ['tags' => 'x'])->assertNotFound();
        $this->actingAs($other)->delete("/profil/koleksi/{$col->id}")->assertNotFound();
        $otherBm = $other->bookmarks()->create(['verse_id' => $this->verse(1, 2)->id]);
        $this->actingAs($other)->put("/profil/bookmark/{$otherBm->id}/koleksi", ['collection_id' => $col->id])->assertNotFound();

        // Hapus koleksi → bookmark tetap ada
        $this->actingAs($user)->delete("/profil/koleksi/{$col->id}")->assertRedirect();
        $this->assertNull($bm->fresh()->collection_id);
        $this->assertSame(2, UserBookmark::query()->count());
    }
}
