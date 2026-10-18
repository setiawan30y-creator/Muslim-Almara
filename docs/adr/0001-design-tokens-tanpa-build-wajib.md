# ADR 0001 — Design tokens CSS tanpa langkah build wajib

- Status: diterima · 19 Okt 2026 · @2026-Alifiandra

## Konteks
Blueprint §3.3 menyarankan Blade + Livewire + Tailwind. Blueprint juga menekankan design tokens terpusat (bagian *Pengaturan Tampilan & Tema*), aplikasi harus jalan di Laragon dengan langkah sesedikit mungkin, dan JavaScript awal dikurangi (§8.6).

## Keputusan
- UI memakai **Blade (SSR) + design tokens CSS** di `public/css/app.css` (warna, aksen, mode tema, skala teks) + JavaScript ringan tanpa framework (`public/js/app.js`).
- Tailwind v4 & Vite tetap tersedia di `package.json` untuk pengembangan berikutnya, tetapi **tidak wajib** untuk menjalankan aplikasi.
- Livewire belum dipasang; interaksi yang ada (pratinjau tema, bookmark, catatan) memakai API `/api/v1` yang sama dengan klien lain (API-first).

## Konsekuensi
Instalasi cukup `composer install`. Bila kelak memakai Tailwind, utilitas harus memetakan token yang sama (`--color-primary`, dsb.) agar tema/aksen tetap konsisten.
