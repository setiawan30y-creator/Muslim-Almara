<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tracker ibadah harian (privat per pengguna) — @2026-Alifiandra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worship_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->json('data');
            $t->unsignedTinyInteger('score')->default(0); // 0–100, untuk ringkasan cepat
            $t->timestamps();
            $t->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worship_logs');
    }
};
