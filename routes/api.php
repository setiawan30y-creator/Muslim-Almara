<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

// REST API v1 (blueprint §7) — dipakai web, PWA, dan TWA. @2026-Alifiandra

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('/health', V1\HealthController::class)->name('health');

    // Al-Qur'an
    Route::get('/quran/surahs', [V1\QuranController::class, 'surahs'])->name('quran.surahs');
    Route::get('/quran/surahs/{number}', [V1\QuranController::class, 'surah'])->whereNumber('number')->name('quran.surah');
    Route::get('/quran/verses', [V1\QuranController::class, 'verses'])->name('quran.verses');
    Route::get('/quran/verses/{id}', [V1\QuranController::class, 'verse'])->whereNumber('id')->name('quran.verse');
    Route::get('/quran/search', [V1\QuranController::class, 'search'])->middleware('throttle:search')->name('quran.search');
    Route::get('/quran/translations', [V1\QuranController::class, 'translations'])->name('quran.translations');
    Route::post('/translate', V1\TranslateController::class)->middleware('throttle:search')->name('translate');
    Route::get('/quran/audio/reciters', [V1\AudioController::class, 'reciters'])->name('quran.audio.reciters');
    Route::get('/quran/audio/surah/{surah}', [V1\AudioController::class, 'surah'])->whereNumber('surah')->name('quran.audio.surah');

    // Ibadah harian
    Route::get('/prayer/methods', [V1\PrayerController::class, 'methods'])->name('prayer.methods');
    Route::get('/prayer/times', [V1\PrayerController::class, 'times'])->name('prayer.times');
    Route::get('/weather', V1\WeatherController::class)->name('weather');
    Route::get('/qibla', [V1\PrayerController::class, 'qibla'])->name('qibla');
    Route::get('/{type}/categories', [V1\WorshipContentController::class, 'categories'])->whereIn('type', ['dhikr', 'dua'])->name('worship.categories');
    Route::get('/{type}/items', [V1\WorshipContentController::class, 'items'])->whereIn('type', ['dhikr', 'dua'])->name('worship.items');
    Route::get('/islamic-events', [V1\WorshipContentController::class, 'events'])->name('events');

    // Autentikasi token
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/register', [V1\AuthController::class, 'register'])->name('auth.register');
        Route::post('/auth/login', [V1\AuthController::class, 'login'])->name('auth.login');
    });

    // Data pribadi
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [V1\MeController::class, 'show'])->name('me');
        Route::patch('/me', [V1\MeController::class, 'update'])->name('me.update');

        Route::get('/me/bookmarks', [V1\MeController::class, 'bookmarks'])->name('me.bookmarks');
        Route::post('/me/bookmarks', [V1\MeController::class, 'storeBookmark'])->name('me.bookmarks.store');
        Route::delete('/me/bookmarks/{bookmark}', [V1\MeController::class, 'destroyBookmark'])->name('me.bookmarks.destroy');

        Route::get('/me/notes', [V1\MeController::class, 'notes'])->name('me.notes');
        Route::post('/me/notes', [V1\MeController::class, 'storeNote'])->name('me.notes.store');
        Route::patch('/me/notes/{note}', [V1\MeController::class, 'updateNote'])->name('me.notes.update');
        Route::delete('/me/notes/{note}', [V1\MeController::class, 'destroyNote'])->name('me.notes.destroy');

        Route::get('/me/reading-progress', [V1\MeController::class, 'readingProgress'])->name('me.progress');
        Route::put('/me/reading-progress', [V1\MeController::class, 'updateReadingProgress'])->name('me.progress.update');
        Route::get('/me/reading-goals', [V1\MeController::class, 'readingGoals'])->name('me.goals');
        Route::put('/me/reading-goals', [V1\MeController::class, 'updateReadingGoals'])->name('me.goals.update');

        Route::get('/me/memorization', [V1\MemorizationController::class, 'index'])->name('me.memorization');
        Route::post('/me/memorization', [V1\MemorizationController::class, 'store'])->name('me.memorization.store');
        Route::delete('/me/memorization', [V1\MemorizationController::class, 'destroy'])->name('me.memorization.destroy');

        Route::get('/me/data-export', [V1\MeController::class, 'export'])->name('me.export');
        Route::post('/me/delete-account', [V1\MeController::class, 'deleteAccount'])->name('me.delete');

        Route::get('/user/appearance-preferences', [V1\MeController::class, 'appearance'])->name('user.appearance');
        Route::put('/user/appearance-preferences', [V1\MeController::class, 'updateAppearance'])->name('user.appearance.update');
        Route::get('/quran/reading-preferences', [V1\MeController::class, 'quranPreferences'])->name('quran.preferences');
        Route::put('/quran/reading-preferences', [V1\MeController::class, 'updateQuranPreferences'])->name('quran.preferences.update');
    });
});
