<?php

// Konfigurasi ALMARA MUSLIM — @2026-Alifiandra

return [
    'version' => '2.0.0',

    // Kota bawaan (slug dari resources/data/kota.json) bila pengguna belum memilih lokasi
    'default_city' => env('ALMARA_DEFAULT_CITY', 'jakarta'),

    // Metode jadwal sholat bawaan (key pada tabel prayer_methods); admin dapat mengubahnya
    'default_prayer_method' => env('ALMARA_PRAYER_METHOD', 'kemenag'),

    // Koreksi tanggal Hijriah (hari) — admin dapat mengubahnya dari panel
    'hijri_adjustment' => (int) env('HIJRI_ADJUSTMENT', 0),

    // Mode Mushaf penuh membutuhkan aset halaman Mushaf berlisensi & terverifikasi.
    // Biarkan null sampai edisi tersedia; aplikasi otomatis memakai mode potongan ayat.
    'mushaf_edition' => env('ALMARA_MUSHAF_EDITION'),

    'developer' => '@2026-Alifiandra',
];
