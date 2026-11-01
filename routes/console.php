<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------------------------------------------- ALMARA — @2026-Alifiandra
use App\Services\Push\AdzanReminder;
use App\Services\Push\WebPush;
use Illuminate\Support\Facades\Schedule;

Artisan::command('almara:push:keys', function () {
    $k = WebPush::generateKeys();
    $this->info('Salin ke .env (cukup sekali; mengganti kunci membuat semua langganan lama tidak berlaku):');
    $this->line('VAPID_PUBLIC_KEY='.$k['public']);
    $this->line('VAPID_PRIVATE_KEY='.$k['private']);
})->purpose('Membuat kunci VAPID untuk pengingat adzan (Web Push)');

Artisan::command('almara:push:adzan', function (AdzanReminder $reminder) {
    $s = $reminder->run();
    if ($s['sent'] || $s['removed'] || $this->getOutput()->isVerbose()) {
        $this->info("Diperiksa {$s['checked']}, terkirim {$s['sent']}, dihapus {$s['removed']}.");
    }
})->purpose('Mengirim pengingat adzan yang jatuh tempo (jalankan tiap menit lewat penjadwal)');

Schedule::command('almara:push:adzan')->everyMinute()->withoutOverlapping(5);
