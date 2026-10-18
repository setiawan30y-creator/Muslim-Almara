<?php

use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrayerTimeController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\QuranController;
use Illuminate\Support\Facades\Route;

// Muslim Almara — @2026-Alifiandra

Route::get('/', HomeController::class)->name('home');
Route::get('/jadwal-sholat', [PrayerTimeController::class, 'index'])->name('prayer');
Route::get('/lokasi', [PrayerTimeController::class, 'location'])->name('location');

Route::get('/al-quran', [QuranController::class, 'index'])->name('quran.index');
Route::get('/al-quran/{nomor}', [QuranController::class, 'show'])->whereNumber('nomor')->name('quran.show');

Route::get('/kiblat', [FeatureController::class, 'qibla'])->name('qibla');
Route::get('/doa', [FeatureController::class, 'doa'])->name('doa');
Route::get('/asmaul-husna', [FeatureController::class, 'asmaulHusna'])->name('asmaul-husna');
Route::get('/tasbih', [FeatureController::class, 'tasbih'])->name('tasbih');
Route::get('/kalender', [FeatureController::class, 'calendar'])->name('calendar');
Route::get('/zakat', [FeatureController::class, 'zakat'])->name('zakat');
Route::get('/menu', [FeatureController::class, 'menu'])->name('menu');

Route::view('/tentang', 'pages.static.tentang')->name('about');
Route::view('/kebijakan-privasi', 'pages.static.privasi')->name('privacy');
Route::view('/syarat-ketentuan', 'pages.static.syarat')->name('terms');

// PWA
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [PwaController::class, 'offline'])->name('offline');

Route::get('/sitemap.xml', [FeatureController::class, 'sitemap'])->name('sitemap');
