<?php

namespace App\Providers;

use App\Services\ContentRepository;
use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Services\QiblaService;
use Illuminate\Support\ServiceProvider;

// @2026-Alifiandra
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrayerTimeService::class, fn () => PrayerTimeService::fromConfig(config('muslim.prayer', [])));
        $this->app->singleton(HijriCalendar::class, fn () => new HijriCalendar((int) config('muslim.hijri_adjustment', 0)));
        $this->app->singleton(QiblaService::class);
        $this->app->singleton(ContentRepository::class, fn () => new ContentRepository(config('muslim.data_path')));
    }

    public function boot(): void
    {
        \Carbon\CarbonImmutable::setLocale('id');
        \Illuminate\Support\Carbon::setLocale('id');
    }
}
