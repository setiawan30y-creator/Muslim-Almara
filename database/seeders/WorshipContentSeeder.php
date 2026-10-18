<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\ContentSource;
use App\Models\DhikrCategory;
use App\Models\DhikrItem;
use App\Models\DuaCategory;
use App\Models\DuaItem;
use Illuminate\Database\Seeder;

/**
 * Mengisi doa & dzikir dari resources/data/doa-dzikir.json.
 * Semua item masuk berstatus "review": wajib disetujui reviewer agama di panel
 * admin sebelum tampil ke pengguna (blueprint §5.3, Standar Wajib Bacaan).
 * Item yang sudah ada tidak ditimpa (idempotent).
 *
 * @2026-Alifiandra
 */
class WorshipContentSeeder extends Seeder
{
    public function __construct(private ?string $status = null)
    {
    }

    public function run(): void
    {
        $data = json_decode((string) file_get_contents(resource_path('data/doa-dzikir.json')), true, flags: JSON_THROW_ON_ERROR);
        $src = $data['source'];
        $sourceId = ContentSource::query()->updateOrCreate(['key' => $src['key']], [
            'name' => $src['name'], 'content_type' => $src['content_type'], 'license' => $src['license'],
            'attribution' => $src['attribution'], 'version' => $src['version'],
            'checksum' => hash_file('sha256', resource_path('data/doa-dzikir.json')), 'retrieved_at' => now(),
        ])->id;

        $status = $this->status ?? ContentStatus::Review->value;

        foreach ([['dua', DuaCategory::class, DuaItem::class], ['dhikr', DhikrCategory::class, DhikrItem::class]] as [$type, $catClass, $itemClass]) {
            $cats = [];
            foreach ($data["{$type}_categories"] as $i => $c) {
                $cats[$c['slug']] = $catClass::query()->updateOrCreate(['slug' => $c['slug']], [
                    'title' => $c['title'], 'description' => $c['description'], 'sort_order' => $i,
                ])->id;
            }
            foreach ($data["{$type}_items"] as $i => $it) {
                $itemClass::query()->firstOrCreate(['slug' => $it['slug']], [
                    'category_id' => $cats[$it['category']],
                    'title' => $it['title'],
                    'arabic_text' => $it['arabic_text'],
                    'transliteration' => $it['transliteration'],
                    'translation' => $it['translation'],
                    'translation_status' => 'verified',
                    'count_target' => $it['count_target'],
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
}
