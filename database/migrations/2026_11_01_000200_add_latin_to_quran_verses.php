<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Transliterasi Latin per ayat (diisi dari dataset berlisensi: php artisan almara:quran:import-latin). @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->text('text_latin')->nullable()->after('text_uthmani');
        });
    }

    public function down(): void
    {
        Schema::table('quran_verses', fn (Blueprint $t) => $t->dropColumn('text_latin'));
    }
};
