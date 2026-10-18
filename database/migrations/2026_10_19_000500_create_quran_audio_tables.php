<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audio qari (blueprint §4.12 "Qari ALMARA" + penyedia berlisensi).
 * - quran_reciters: qari milik platform (rekaman sendiri). Qari Quran.Foundation tidak disimpan di sini
 *   (diambil dari API resmi dan hanya di-cache singkat sesuai ketentuan penyedia).
 * - quran_audio_tracks: berkas rekaman per surah, berversi, wajib review sebelum terbit.
 * - quran_audio_ayah_segments: penanda waktu per ayat (opsional) untuk sorot ayat.
 * @2026-Alifiandra
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_reciters', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->string('style', 60)->nullable();          // murattal, mujawwad, dll.
            $table->string('license_note', 255)->nullable();   // dasar hak penggunaan rekaman
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quran_audio_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reciter_id')->constrained('quran_reciters')->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_number');
            $table->unsignedInteger('version')->default(1);
            $table->string('disk', 40);
            $table->string('path', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 60);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->char('checksum', 64);
            $table->string('review_status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['reciter_id', 'surah_number', 'review_status']);
        });

        Schema::create('quran_audio_ayah_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('quran_audio_tracks')->cascadeOnDelete();
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedInteger('start_ms');
            $table->unsignedInteger('end_ms');
            $table->unique(['track_id', 'ayah_number']);
        });

        Schema::table('quran_reading_preferences', function (Blueprint $table) {
            $table->string('reciter', 80)->nullable()->after('audio_mode');
        });
    }

    public function down(): void
    {
        Schema::table('quran_reading_preferences', function (Blueprint $table) {
            $table->dropColumn('reciter');
        });
        Schema::dropIfExists('quran_audio_ayah_segments');
        Schema::dropIfExists('quran_audio_tracks');
        Schema::dropIfExists('quran_reciters');
    }
};
