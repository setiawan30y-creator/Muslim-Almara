<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Materi panduan dapat menampilkan rangkaian ayat (Arab + arti Kemenag) beserta transliterasinya — untuk tahlil. @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_items', function (Blueprint $table) {
            $table->boolean('show_verses')->default(false)->after('audio_ref');
            $table->text('quran_latin')->nullable()->after('show_verses');
        });
    }

    public function down(): void
    {
        Schema::table('guide_items', function (Blueprint $t) {
            $t->dropColumn(['show_verses', 'quran_latin']);
        });
    }
};
