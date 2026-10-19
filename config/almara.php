<?php

// Konfigurasi ALMARA MUSLIM — @2026-Alifiandra

return [
    'version' => '2.2.0',

    // Kota bawaan (slug dari resources/data/kota.json) bila pengguna belum memilih lokasi
    'default_city' => env('ALMARA_DEFAULT_CITY', 'jakarta'),

    // Metode jadwal sholat bawaan (key pada tabel prayer_methods); admin dapat mengubahnya
    'default_prayer_method' => env('ALMARA_PRAYER_METHOD', 'kemenag'),

    // Koreksi tanggal Hijriah (hari) — admin dapat mengubahnya dari panel
    'hijri_adjustment' => (int) env('HIJRI_ADJUSTMENT', 0),

    // Mode Mushaf penuh membutuhkan aset halaman Mushaf berlisensi & terverifikasi.
    // Biarkan null sampai edisi tersedia; aplikasi otomatis memakai mode potongan ayat.
    'mushaf_edition' => env('ALMARA_MUSHAF_EDITION'),

    // Audio qari (blueprint §4.12)
    'audio' => [
        // Rekaman Qari ALMARA disimpan di disk privat (tidak bisa diakses langsung lewat URL).
        'disk' => env('ALMARA_AUDIO_DISK', 'local'),
        'max_upload_mb' => (int) env('ALMARA_AUDIO_MAX_MB', 150),
        'max_duration_minutes' => (int) env('ALMARA_AUDIO_MAX_MINUTES', 240),
        // Unduh offline hanya bila hak rekaman mengizinkan.
        'allow_offline' => (bool) env('ALMARA_AUDIO_OFFLINE', false),

        // Qari berlisensi via Quran.Foundation Content API v4.
        // Kredensial dari Developer Console — hanya di server, jangan pernah dikirim ke browser.
        'quran_foundation' => [
            'enabled' => (bool) env('QF_ENABLED', false),
            'client_id' => env('QF_CLIENT_ID'),
            'client_secret' => env('QF_CLIENT_SECRET'),
            'environment' => env('QF_ENV', 'production'), // production | prelive
            // Mode simulasi: pemutar dapat diuji tanpa kredensial (nada uji, bukan bacaan).
            'mock' => (bool) env('QF_MOCK', false),
            // Batas penyedia: data tidak boleh di-cache lebih dari 1 minggu. Bawaan: 1 hari.
            'cache_seconds' => min(604800, (int) env('QF_CACHE_SECONDS', 86400)),
            'attribution' => 'Quran data provided by Quran.Foundation',
            'timeout' => 8,
        ],
    ],

    // Terjemah teks bebas (opsional) via layanan kompatibel LibreTranslate.
    // Tanpa konfigurasi, halaman Terjemah tetap menyediakan kumpulan frasa offline.
    'translate' => [
        'url' => env('TRANSLATE_URL'),          // contoh: https://libretranslate.contoh.id
        'api_key' => env('TRANSLATE_API_KEY'),
        'timeout' => 10,
        'max_chars' => 1000,
        'languages' => ['id' => 'Indonesia', 'ar' => 'Arab', 'en' => 'Inggris'],
    ],

    // Masjid terdekat: data OpenStreetMap via Overpass API (dipanggil dari peramban pengguna).
    'mosque_finder' => [
        'overpass_url' => env('OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
    ],

    'developer' => '@2026-Alifiandra',
];
