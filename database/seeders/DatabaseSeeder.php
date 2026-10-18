<?php

namespace Database\Seeders;

use App\Models\QuranVerse;
use App\Services\Quran\QuranImporter;
use Illuminate\Database\Seeder;

// Seeder pengembangan — tanpa kredensial atau data pribadi nyata (blueprint §12). @2026-Alifiandra
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([AccessControlSeeder::class, PrayerMethodSeeder::class, IslamicEventSeeder::class]);

        if (QuranVerse::query()->count() === 0) {
            $this->command?->info("Mengimpor dataset Al-Qur'an (sekali saja)…");
            app(QuranImporter::class)->import();
        }

        $this->call(WorshipContentSeeder::class);
    }
}
