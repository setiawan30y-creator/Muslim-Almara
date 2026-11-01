<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rujukan ayat untuk audio doa/dzikir yang berasal dari Al-Qur'an ("2:201", "112:1-4").
 * Audio diputar dengan qari pilihan pengguna (ayat lengkap). Data lama diisi dari doa-dzikir.json.
 * @2026-Alifiandra
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['dua_items', 'dhikr_items'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('audio_ref', 20)->nullable()->after('source_reference');
            });
        }
        $file = resource_path('data/doa-dzikir.json');
        if (is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            foreach (['dua_items' => 'dua_items', 'dhikr_items' => 'dhikr_items'] as $key => $table) {
                foreach ($data[$key] ?? [] as $it) {
                    if (! empty($it['audio_ref'])) {
                        DB::table($table)->where('slug', $it['slug'])->whereNull('audio_ref')->update(['audio_ref' => $it['audio_ref']]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['dua_items', 'dhikr_items'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn('audio_ref'));
        }
    }
};
