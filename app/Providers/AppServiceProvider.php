<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\Calendar\HijriCalendar;
use App\Services\Content\StaticContent;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Qibla\QiblaService;
use App\Services\Quran\QuranService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

// @2026-Alifiandra
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrayerTimeCalculator::class);
        $this->app->singleton(QiblaService::class);
        $this->app->scoped(QuranService::class);
        $this->app->scoped(\App\Services\Preferences\PreferenceService::class);
        $this->app->singleton(StaticContent::class, fn () => new StaticContent(resource_path('data')));
        $this->app->bind(HijriCalendar::class, fn () => new HijriCalendar(
            (int) AppSetting::get('hijri.adjustment', config('almara.hijri_adjustment', 0))
        ));
    }

    public function boot(): void
    {
        CarbonImmutable::setLocale('id');
        Carbon::setLocale('id');

        // Atribut tema/aksen/ukuran teks dirender server agar tidak berkedip
        View::composer(['components.layouts.app', 'components.layouts.admin'], function ($view) {
            $view->with('appearance', app(\App\Services\Preferences\PreferenceService::class)->appearance());
        });

        // Izin berbasis peran: @can('content.review') dsb.
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.') && array_key_exists($ability, \App\Models\Permission::ALL)) {
                return $user->hasPermission($ability) ?: null;
            }

            return null;
        });

        // Rate limit (blueprint §7.3, §10.2)
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('auth', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));
    }
}
