<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sumber konten & Al-Qur'an (blueprint §4.1, §5, §6.1–6.2) — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->string('content_type', 40);
            $table->string('url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->string('license')->nullable();
            $table->text('attribution')->nullable();
            $table->string('version', 60)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('name_ar', 60);
            $table->string('name_latin', 60);
            $table->string('name_translation', 100);
            $table->string('revelation_type', 12);
            $table->unsignedSmallInteger('ayah_count');
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quran_verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('quran_surahs')->cascadeOnDelete();
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedTinyInteger('juz_number')->index();
            $table->unsignedSmallInteger('page_number')->nullable()->index();
            $table->text('text_uthmani');
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->string('version', 60);
            $table->timestamps();
            $table->unique(['surah_id', 'ayah_number']);
        });

        Schema::create('quran_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('language', 10);
            $table->string('translator', 120);
            $table->text('text');
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->string('version', 60);
            $table->timestamps();
            $table->unique(['verse_id', 'language', 'translator', 'version'], 'quran_translations_unique');
            $table->index('language');
        });

        Schema::create('quran_tafsirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('work_name', 120);
            $table->string('author', 120);
            $table->string('language', 10);
            $table->longText('text');
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->timestamps();
            $table->unique(['verse_id', 'work_name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_tafsirs');
        Schema::dropIfExists('quran_translations');
        Schema::dropIfExists('quran_verses');
        Schema::dropIfExists('quran_surahs');
        Schema::dropIfExists('content_sources');
    }
};
