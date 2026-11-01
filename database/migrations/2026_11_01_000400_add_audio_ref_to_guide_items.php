<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Audio bacaan panduan yang berasal dari ayat Al-Qur'an (mis. doa tawaf QS 2:201). @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_items', function (Blueprint $table) {
            $table->string('audio_ref', 20)->nullable()->after('example_ref');
        });
        $file = resource_path('data/panduan.json');
        if (is_file($file)) {
            foreach (json_decode((string) file_get_contents($file), true)['items'] ?? [] as $it) {
                if (! empty($it['audio_ref'])) {
                    DB::table('guide_items')->where('slug', $it['slug'])->whereNull('audio_ref')->update(['audio_ref' => $it['audio_ref']]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('guide_items', fn (Blueprint $t) => $t->dropColumn('audio_ref'));
    }
};
