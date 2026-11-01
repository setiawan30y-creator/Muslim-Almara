<?php

use App\Support\ArabicText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kolom teks Arab tanpa harakat untuk pencarian huruf Arab — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verses', function (Blueprint $t) {
            $t->text('text_plain')->nullable()->after('text_uthmani');
        });

        DB::table('quran_verses')->select(['id', 'text_uthmani'])->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $r) {
                DB::table('quran_verses')->where('id', $r->id)->update(['text_plain' => ArabicText::normalize((string) $r->text_uthmani)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('quran_verses', function (Blueprint $t) {
            $t->dropColumn('text_plain');
        });
    }
};
