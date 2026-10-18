<?php

namespace Tests;

use App\Enums\ContentStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Quran\QuranImporter;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\IslamicEventSeeder;
use Database\Seeders\PrayerMethodSeeder;
use Database\Seeders\WorshipContentSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

// @2026-Alifiandra
abstract class TestCase extends BaseTestCase
{
    /** Surah yang diimpor untuk pengujian (cukup untuk ayat harian & dzikir). */
    protected const TEST_SURAHS = [1, 2, 3, 17, 20, 112, 113, 114];

    protected function seedAlmara(bool $publishContent = true): void
    {
        Cache::flush();
        $this->seed([AccessControlSeeder::class, PrayerMethodSeeder::class, IslamicEventSeeder::class]);
        app(QuranImporter::class)->import(self::TEST_SURAHS, withTafsir: true);
        (new WorshipContentSeeder($publishContent ? ContentStatus::Published->value : null))->run();
    }

    protected function userWithRole(?string $role = null): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->roles()->attach(Role::query()->where('name', $role)->value('id'), ['assigned_at' => now()]);
        }

        return $user->fresh();
    }
}
