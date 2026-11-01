<?php

// Konfigurasi ALMARA MUSLIM — @2026-Alifiandra

return [
    'version' => '2.6.0',

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
        // Qari bawaan bagi pengguna yang belum memilih (bila tersedia)
        'default_reciter' => env('ALMARA_DEFAULT_RECITER', 'in:ar.alafasy'),

        // Pilihan qari dari Islamic Network / AlQuran Cloud (tanpa kunci). Batasi daftar dengan
        // ISLAMIC_NETWORK_RECITERS=ar.alafasy,ar.husary,... (kosong = semua qari berbahasa Arab).
        'islamic_network' => [
            'enabled' => (bool) env('ISLAMIC_NETWORK_AUDIO', true),
            'reciters' => env('ISLAMIC_NETWORK_RECITERS', ''),
        ],

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

    // Terjemah teks & percakapan. Penyedia dipilih otomatis (TRANSLATE_DRIVER=auto):
    //   google (TRANSLATE_GOOGLE_KEY) → deepl (TRANSLATE_DEEPL_KEY) → libretranslate (TRANSLATE_URL) → mymemory (tanpa kunci).
    // MyMemory gratis tanpa kunci namun berkuota harian kecil — untuk produksi gunakan Google/DeepL/LibreTranslate.
    'translate' => [
        'driver' => env('TRANSLATE_DRIVER', 'auto'),
        'google_key' => env('TRANSLATE_GOOGLE_KEY'),
        'deepl_key' => env('TRANSLATE_DEEPL_KEY'),
        'url' => env('TRANSLATE_URL'),          // LibreTranslate, mis. http://127.0.0.1:5000
        'api_key' => env('TRANSLATE_API_KEY'),
        'mymemory_email' => env('TRANSLATE_MYMEMORY_EMAIL'), // menaikkan kuota MyMemory
        'timeout' => 10,
        'max_chars' => 2000,
        'languages' => [
            'id' => ['Indonesia', 'id-ID'],
            'ar' => ['Arab', 'ar-SA'],
            'en' => ['Inggris', 'en-US'],
            'ms' => ['Melayu', 'ms-MY'],
            'tr' => ['Turki', 'tr-TR'],
            'ur' => ['Urdu', 'ur-PK'],
        ],
    ],

    // Pengingat adzan (Web Push, VAPID). Buat kunci: php artisan almara:push:keys
    // Jalankan penjadwal tiap menit di server: * * * * * php artisan schedule:run
    'push' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
    ],

    // Play Store (TWA): isi setelah membuat paket Android, untuk /.well-known/assetlinks.json
    'twa' => [
        'package' => env('TWA_PACKAGE_NAME'),
        'fingerprints' => env('TWA_SHA256_FINGERPRINTS'), // dipisah koma
    ],

    // Cuaca (Open-Meteo, data CC BY 4.0). API gratis hanya untuk penggunaan NON-komersial;
    // untuk aplikasi berbayar/beriklan/disewakan isi OPEN_METEO_API_KEY (paket berlangganan).
    'weather' => [
        'enabled' => (bool) env('ALMARA_WEATHER', true),
        'url' => env('OPEN_METEO_URL'),             // kosong = otomatis (gratis / customer-api bila ada kunci)
        'api_key' => env('OPEN_METEO_API_KEY'),
        'cache_seconds' => (int) env('ALMARA_WEATHER_CACHE', 1800),
        'timeout' => 6,
    ],

    // Masjid terdekat: data OpenStreetMap via Overpass API (dipanggil dari peramban pengguna).
    'mosque_finder' => [
        'overpass_url' => env('OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
    ],

    'developer' => '@2026-Alifiandra',
];
