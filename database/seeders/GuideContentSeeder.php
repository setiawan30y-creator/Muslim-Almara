<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\ContentSource;
use App\Models\GuideCategory;
use App\Models\GuideItem;
use Illuminate\Database\Seeder;

/**
 * Panduan Tajwid, Umroh, Haji, dan Ziarah dari resources/data/panduan.json.
 * Semua materi masuk berstatus "review" — tampil setelah disetujui reviewer di
 * Admin → Panduan (blueprint §5.3). Materi yang sudah ada tidak ditimpa.
 * @2026-Alifiandra
 */
class GuideContentSeeder extends Seeder
{
    public function __construct(private ?string $status = null)
    {
    }

    public function run(): void
    {
        $file = resource_path('data/panduan.json');
        $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $src = $data['source'];
        $sourceId = ContentSource::query()->updateOrCreate(['key' => $src['key']], [
            'name' => $src['name'], 'content_type' => $src['content_type'], 'license' => $src['license'],
            'attribution' => $src['attribution'], 'version' => $src['version'],
            'checksum' => hash_file('sha256', $file), 'retrieved_at' => now(),
        ])->id;
        $status = $this->status ?? ContentStatus::Review->value;

        $cats = [];
        foreach ($data['categories'] as $i => $c) {
            $cats[$c['slug']] = GuideCategory::query()->updateOrCreate(['slug' => $c['slug']], [
                'module' => $c['module'], 'title' => $c['title'], 'description' => $c['description'], 'sort_order' => $i,
            ])->id;
        }
        foreach ($data['items'] as $i => $it) {
            GuideItem::query()->firstOrCreate(['slug' => $it['slug']], [
                'category_id' => $cats[$it['category']],
                'title' => $it['title'],
                'body' => $it['body'],
                'arabic_text' => $it['arabic_text'],
                'transliteration' => $it['transliteration'],
                'translation' => $it['translation'],
                'example_ref' => $it['example_ref'],
                'quiz_json' => $it['quiz'],
                'context_note' => $it['context_note'],
                'source_reference' => $it['source_reference'],
                'source_kind' => $it['source_kind'],
                'source_id' => $sourceId,
                'review_status' => $status,
                'published_at' => $status === ContentStatus::Published->value ? now() : null,
                'sort_order' => $i,
            ]);
        }
    }
}
