<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langganan pengingat adzan (Web Push). Tamu boleh berlangganan; lokasi & metode disalin
 * saat berlangganan (dibulatkan ±1 km) agar jadwal dapat dihitung di server.
 * @2026-Alifiandra
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->string('p256dh', 120);
            $table->string('auth', 40);
            $table->decimal('lat', 6, 2);
            $table->decimal('lng', 6, 2);
            $table->decimal('tz', 4, 2);
            $table->string('location_name', 80)->nullable();
            $table->string('method', 30);
            $table->string('asr', 20)->default('standard');
            $table->json('offsets_json')->nullable();
            $table->json('prayers_json');                 // ["subuh","zuhur",...]
            $table->unsignedTinyInteger('minutes_before')->default(0);
            $table->string('last_sent_key', 40)->nullable(); // "2026-11-01|zuhur"
            $table->unsignedSmallInteger('failures')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
