# ADR 0002 — Dataset Al-Qur'an lokal berversi, diimpor ke MySQL

- Status: diterima · 19 Okt 2026 · @2026-Alifiandra

## Keputusan
- Teks, terjemahan, dan tafsir Kemenag RI disertakan di `resources/data/quran` (dari rioastamal/quran-json, MIT) dan **diimpor** ke tabel `quran_*` oleh `php artisan almara:quran:import` (validasi → transaksi → upsert idempoten).
- Metadata sumber, versi dataset, checksum SHA-256, dan catatan validasi disimpan di `content_sources` (§5.2).
- Teks mushaf **tidak dapat diubah** dari panel admin (§1.2). Koreksi = dataset baru → validasi → review → impor ulang.
- Nomor halaman Mushaf belum tersedia (kolom `page_number` null) sampai sumber halaman berlisensi dipilih.

## Konsekuensi
Aplikasi tidak bergantung pada API eksternal. Sebelum produksi, ketentuan redistribusi terjemahan/tafsir Kemenag wajib diperiksa ulang (lihat `docs/content-sources.md`).
