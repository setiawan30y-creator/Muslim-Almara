<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rekaman suara adzan (unggah → disetujui pengguna lain → aktif) — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adzan_sounds', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('kind', 10)->default('regular'); // regular | subuh
            $t->string('disk', 40);
            $t->string('path');
            $t->string('mime', 40);
            $t->unsignedInteger('size_bytes');
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->string('checksum', 64)->unique();
            $t->string('license_note', 500);
            $t->string('status', 12)->default('draft'); // draft | published | inactive
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adzan_sounds');
    }
};
