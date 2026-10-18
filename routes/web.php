<?php

use App\Http\Controllers\Account\LibraryController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\Quran\QuranController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Worship\DoaDzikirController;
use App\Http\Controllers\Worship\PrayerController;
use App\Http\Controllers\Worship\QiblaController;
use App\Http\Controllers\Worship\ToolsController;
use Illuminate\Support\Facades\Route;

// ALMARA MUSLIM — rute web. @2026-Alifiandra

Route::get('/', HomeController::class)->name('home');

// Navigasi utama (blueprint §8.2): Beranda · Qur'an · Belajar · Ibadah · Profil
Route::get('/ibadah', [HubController::class, 'worship'])->name('hub.worship');
Route::get('/belajar', [HubController::class, 'learning'])->name('hub.learning');

// Al-Qur'an
Route::prefix('al-quran')->name('quran.')->controller(QuranController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/cari', 'search')->middleware('throttle:search')->name('search');
    Route::get('/{nomor}', 'show')->whereNumber('nomor')->name('show');
});

// Ibadah harian
Route::get('/jadwal-sholat', [PrayerController::class, 'index'])->name('prayer');
Route::get('/lokasi', [PrayerController::class, 'location'])->name('location');
Route::get('/kiblat', QiblaController::class)->name('qibla');
Route::get('/doa-dzikir', [DoaDzikirController::class, 'index'])->name('doa');
Route::get('/doa-dzikir/{type}/{slug}', [DoaDzikirController::class, 'show'])->whereIn('type', ['doa', 'dzikir'])->name('doa.show');
Route::get('/tasbih', [ToolsController::class, 'tasbih'])->name('tasbih');
Route::get('/kalender', [ToolsController::class, 'calendar'])->name('calendar');
Route::get('/zakat', [ToolsController::class, 'zakat'])->name('zakat');
Route::get('/asmaul-husna', [ToolsController::class, 'asmaulHusna'])->name('asmaul-husna');

// Pengaturan (tamu: tersimpan di perangkat; login: tersimpan di akun)
Route::prefix('pengaturan')->name('settings.')->controller(SettingsController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/tampilan', 'appearance')->name('appearance');
    Route::post('/tampilan', 'saveAppearance')->name('appearance.save');
    Route::get('/al-quran', 'quran')->name('quran');
    Route::post('/al-quran', 'saveQuran')->name('quran.save');
    Route::get('/jadwal-sholat', 'prayer')->name('prayer');
    Route::post('/jadwal-sholat', 'savePrayer')->name('prayer.save');
    Route::get('/beranda', 'home')->name('home');
    Route::post('/beranda', 'saveHome')->name('home.save');
});

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::get('/lupa-sandi', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-sandi/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-sandi', [PasswordResetController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});

Route::get('/profil', [ProfileController::class, 'index'])->name('profile');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::get('/verifikasi-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verifikasi-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/verifikasi-email/kirim', [VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::prefix('profil')->name('profile.')->group(function () {
        Route::get('/akun', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/akun', [ProfileController::class, 'update'])->name('update');
        Route::put('/kata-sandi', [ProfileController::class, 'password'])->name('password');
        Route::get('/data', [ProfileController::class, 'data'])->name('data');
        Route::get('/data/ekspor', [ProfileController::class, 'export'])->name('export');
        Route::delete('/data/progres', [ProfileController::class, 'resetProgress'])->name('progress.reset');
        Route::delete('/akun', [ProfileController::class, 'destroy'])->name('destroy');

        Route::get('/bookmark', [LibraryController::class, 'bookmarks'])->name('bookmarks');
        Route::get('/catatan', [LibraryController::class, 'notes'])->name('notes');
        Route::get('/progres', [LibraryController::class, 'progress'])->name('progress');
        Route::post('/progres/target', [LibraryController::class, 'saveGoal'])->name('goal');
    });
});

// Panel admin (blueprint §8.5) — izin dicek per aksi
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.access'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/konten/{type}', [Admin\ContentController::class, 'index'])->whereIn('type', ['doa', 'dzikir'])->name('content.index');
    Route::get('/konten/{type}/baru', [Admin\ContentController::class, 'create'])->whereIn('type', ['doa', 'dzikir'])->name('content.create');
    Route::post('/konten/{type}', [Admin\ContentController::class, 'store'])->whereIn('type', ['doa', 'dzikir'])->name('content.store');
    Route::get('/konten/{type}/{id}', [Admin\ContentController::class, 'edit'])->whereIn('type', ['doa', 'dzikir'])->whereNumber('id')->name('content.edit');
    Route::put('/konten/{type}/{id}', [Admin\ContentController::class, 'update'])->whereIn('type', ['doa', 'dzikir'])->whereNumber('id')->name('content.update');
    Route::post('/konten/{type}/{id}/{action}', [Admin\ContentController::class, 'transition'])
        ->whereIn('type', ['doa', 'dzikir'])->whereNumber('id')
        ->whereIn('action', ['submit', 'approve', 'publish', 'approve-publish', 'reject', 'suspend'])->name('content.transition');
    Route::post('/konten/{type}/massal', [Admin\ContentController::class, 'bulk'])->whereIn('type', ['doa', 'dzikir'])->name('content.bulk');

    Route::get('/sumber', [Admin\SourceController::class, 'index'])->name('sources');
    Route::post('/sumber/{source}/tinjau', [Admin\SourceController::class, 'markReviewed'])->name('sources.review');

    Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('users');
    Route::put('/pengguna/{user}/peran', [Admin\UserController::class, 'roles'])->name('users.roles');

    Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('settings');
    Route::put('/pengaturan', [Admin\SettingController::class, 'update'])->name('settings.update');

    Route::get('/audit', Admin\AuditController::class)->name('audit');
});

// Halaman informasi
Route::get('/tentang', [\App\Http\Controllers\PageController::class, 'about'])->name('about');
Route::view('/kebijakan-privasi', 'pages.static.privasi')->name('privacy');
Route::view('/syarat-ketentuan', 'pages.static.syarat')->name('terms');

// PWA
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/service-worker.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [PwaController::class, 'offline'])->name('offline');
Route::get('/sitemap.xml', [PwaController::class, 'sitemap'])->name('sitemap');

// Alamat lama tetap berfungsi
Route::redirect('/doa', '/doa-dzikir', 301);
Route::redirect('/menu', '/ibadah', 301);
Route::get('/sw.js', [PwaController::class, 'serviceWorker']); // klien lama
