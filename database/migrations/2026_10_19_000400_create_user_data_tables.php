<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data & preferensi pribadi pengguna (privat secara default) — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->default('id');
            $table->string('timezone', 40)->default('Asia/Jakarta');
            $table->json('dashboard_cards_json')->nullable();
            $table->boolean('track_reading')->default(true);
            $table->timestamps();
        });

        Schema::create('user_appearance_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme_mode', 10)->default('system');
            $table->string('accent_color', 10)->default('hijau');
            $table->string('interface_text_size', 15)->default('normal');
            $table->string('reading_text_size', 15)->default('normal');
            $table->string('translation_text_size', 15)->default('normal');
            $table->timestamps();
        });

        Schema::create('quran_reading_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reading_mode', 10)->default('mushaf');
            $table->boolean('show_translation')->default(true);
            $table->boolean('show_transliteration')->default(false);
            $table->string('audio_mode', 10)->default('surah');
            $table->boolean('show_tafsir')->default(true);
            $table->timestamps();
        });

        Schema::create('prayer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('method_id')->nullable()->constrained('prayer_methods')->nullOnDelete();
            $table->string('asr_method', 10)->default('standard');
            $table->json('offsets_json')->nullable();
            $table->timestamps();
        });

        Schema::create('user_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'verse_id']);
        });

        Schema::create('user_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->timestamp('last_read_at');
            $table->unique(['user_id', 'verse_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('user_reading_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('daily_target');
            $table->date('start_date');
            $table->date('khatam_target_date')->nullable();
            $table->string('status', 12)->default('active');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->nullable()->constrained('quran_verses')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['user_id', 'verse_id']);
        });

        Schema::create('user_last_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->unsignedSmallInteger('mushaf_page')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['user_last_reads', 'user_notes', 'user_reading_goals', 'user_reading_progress', 'user_bookmarks',
            'prayer_preferences', 'quran_reading_preferences', 'user_appearance_preferences', 'user_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
