<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul tahap lanjutan (blueprint §13):
 * - Panduan bersumber dengan alur review: Tajwid, Umroh, Haji, Ziarah Kubur.
 * - Hafalan & Murojaah (pengulangan berjarak) — data pribadi.
 * - Jurnal & Koleksi: koleksi bookmark tematik + tag catatan.
 * @2026-Alifiandra
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_categories', function (Blueprint $table) {
            $table->id();
            $table->string('module', 20)->index(); // tajwid|umroh|haji|ziarah
            $table->string('slug', 80)->unique();
            $table->string('title', 120);
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('guide_categories')->cascadeOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('title', 160);
            $table->text('body');                          // penjelasan (paragraf; baris "- " = daftar)
            $table->text('arabic_text')->nullable();       // bacaan (bila ada)
            $table->text('transliteration')->nullable();
            $table->text('translation')->nullable();
            $table->string('translation_status', 20)->default('verified');
            $table->unsignedSmallInteger('count_target')->nullable();
            $table->text('context_note')->nullable();
            $table->string('example_ref', 20)->nullable(); // contoh ayat "surah:ayat"
            $table->json('quiz_json')->nullable();         // latihan pilihan ganda
            $table->string('source_reference');
            $table->string('source_kind', 30)->default('general');
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->string('review_status', 20)->default('draft')->index();
            $table->unsignedInteger('content_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_memorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('status', 12)->default('learning'); // learning|memorized
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->decimal('ease', 4, 2)->default(2.5);
            $table->date('next_review_on')->nullable();
            $table->unsignedInteger('reviews')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->string('last_grade', 10)->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'verse_id']);
            $table->index(['user_id', 'next_review_on']);
        });

        Schema::create('user_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('user_bookmarks', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('verse_id')->constrained('user_collections')->nullOnDelete();
        });

        Schema::table('user_notes', function (Blueprint $table) {
            $table->string('tags', 255)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('user_notes', fn (Blueprint $t) => $t->dropColumn('tags'));
        Schema::table('user_bookmarks', function (Blueprint $t) {
            $t->dropConstrainedForeignId('collection_id');
        });
        Schema::dropIfExists('user_collections');
        Schema::dropIfExists('user_memorizations');
        Schema::dropIfExists('guide_items');
        Schema::dropIfExists('guide_categories');
    }
};
