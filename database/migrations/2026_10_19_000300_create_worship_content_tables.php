<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dzikir, doa, metode sholat, peristiwa Islam, review konten — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        foreach (['dhikr', 'dua'] as $type) {
            Schema::create("{$type}_categories", function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique();
                $table->string('title', 120);
                $table->string('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });

            Schema::create("{$type}_items", function (Blueprint $table) use ($type) {
                $table->id();
                $table->foreignId('category_id')->constrained("{$type}_categories")->cascadeOnDelete();
                $table->string('slug', 120)->unique();
                $table->string('title', 160);
                $table->text('arabic_text');
                $table->text('transliteration');
                $table->text('translation');
                $table->string('translation_status', 20)->default('verified');
                $table->unsignedSmallInteger('count_target')->nullable();
                $table->text('context_note')->nullable();
                $table->string('source_reference');
                $table->string('source_kind', 30)->default('hadith'); // quran|hadith|general
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
        }

        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 40);
            $table->unsignedBigInteger('content_id');
            $table->unsignedInteger('content_version');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 20);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['content_type', 'content_id']);
        });

        Schema::create('prayer_methods', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 120);
            $table->json('parameters_json');
            $table->string('source');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('islamic_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->unsignedTinyInteger('hijri_month');
            $table->unsignedTinyInteger('hijri_day');
            $table->date('gregorian_date')->nullable();
            $table->string('date_basis', 20)->default('calculated'); // calculated|official
            $table->string('description')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->timestamps();
            $table->unique(['hijri_month', 'hijri_day', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('islamic_events');
        Schema::dropIfExists('prayer_methods');
        Schema::dropIfExists('content_reviews');
        foreach (['dua', 'dhikr'] as $type) {
            Schema::dropIfExists("{$type}_items");
            Schema::dropIfExists("{$type}_categories");
        }
    }
};
