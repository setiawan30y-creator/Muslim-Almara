<?php

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Support\Facades\Route;

// REST API v1 — @2026-Alifiandra

Route::prefix('v1')->middleware('throttle:120,1')->name('api.v1.')->group(function () {
    Route::get('/jadwal-sholat', [ApiController::class, 'prayerTimes'])->name('prayer');
    Route::get('/kota', [ApiController::class, 'cities'])->name('cities');
    Route::get('/surat', [ApiController::class, 'surahs'])->name('surahs');
    Route::get('/surat/{nomor}', [ApiController::class, 'surah'])->whereNumber('nomor')->name('surah');
    Route::get('/doa', [ApiController::class, 'doa'])->name('doa');
    Route::get('/asmaul-husna', [ApiController::class, 'asmaulHusna'])->name('asmaul-husna');
    Route::get('/hijriah', [ApiController::class, 'hijri'])->name('hijri');
    Route::get('/kiblat', [ApiController::class, 'qibla'])->name('qibla');
});
