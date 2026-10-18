<?php

// Konfigurasi Muslim Almara — @2026-Alifiandra

return [
    'default_city' => env('MUSLIM_DEFAULT_CITY', 'jakarta'),

    'hijri_adjustment' => (int) env('HIJRI_ADJUSTMENT', 0),

    'prayer' => [
        'fajr_angle' => (float) env('PRAYER_FAJR_ANGLE', 20),
        'isha_angle' => (float) env('PRAYER_ISHA_ANGLE', 18),
        'asr_factor' => 1, // 1 = Syafi'i, 2 = Hanafi
        'ihtiyat' => (int) env('PRAYER_IHTIYAT', 2),
        'imsak_minutes' => 10,
        'duha_altitude' => 4.5,
    ],

    'data_path' => resource_path('data'),

    'version' => '1.0.0',

    'developer' => '@2026-Alifiandra',
];
