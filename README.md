# ALMARA MUSLIM

Pendamping Muslim pribadi — Al-Qur'an, jadwal sholat, kiblat, dzikir & doa, kalender Hijriah, progres ibadah privat, dan PWA yang dapat dipasang.
Dibangun dengan **Laravel 13** mengikuti *ALMARA MUSLIM — Blueprint Teknis & Roadmap Implementasi*.

> Dikembangkan oleh **@2026-Alifiandra**

## Status rilis

Versi **2.3.0** mencakup Rilis Pertama (MVP, blueprint §1.1) + *Pengaturan Tampilan & Tema*:

| Area | Isi |
|---|---|
| Navigasi | Beranda · Qur'an · Belajar · Ibadah · Profil |
| Beranda | Salam & lokasi, sholat berikutnya + hitung mundur, cuaca & prakiraan per jam, aksi cepat, lanjutkan bacaan, target tilawah, ayat harian, hari besar, catatan — kartu dapat diurutkan/disembunyikan |
| Al-Qur'an | 114 surah & 30 juz, terjemahan & tafsir Kemenag, pencarian, bookmark, catatan pribadi, tandai dibaca, unduh offline, pilihan mode baca |
| Audio qari | Pemutar murattal (putar dari ayat, sorot ayat, mode per ayat, ulang); **Qari ALMARA** (rekaman sendiri: unggah → karantina → review → terbit) & qari berlisensi **Quran.Foundation** |
| Ibadah | Jadwal sholat (8 metode, mazhab Asar, koreksi menit), kiblat + kalibrasi, masjid terdekat (OpenStreetMap), dzikir & doa bersumber, tasbih, kalender Hijriah, zakat, panduan Umroh, Haji & Ziarah Kubur |
| Belajar | Tajwid bertingkat + latihan, Hafalan & Murojaah (pengulangan berjarak), Terjemah & Frasa (offline + layanan opsional), Jurnal bertag & koleksi ayat |
| Akun | Daftar/masuk, reset sandi, verifikasi email, progres & target, ekspor & hapus data |
| Pengaturan | Tampilan & Tema (Terang/Gelap/Sistem, 5 aksen, ukuran teks UI/Arab/terjemahan, pratinjau, reset), Al-Qur'an, Jadwal Sholat, Beranda |
| Admin | Peran admin/editor/reviewer, alur draft → review → terbit, versi konten, audio qari, sumber & lisensi, pengguna, konfigurasi, log audit |
| API | `/api/v1` format `{data, meta}` — lihat `docs/api.md` |
| PWA | Manifest, service worker berversi, offline, halaman pribadi tidak pernah dicache |

Belum diaktifkan: Mushaf penuh & transliterasi per ayat (menunggu dataset berlisensi), Premium/Iklan, notifikasi push, TWA.

## Instalasi lokal (Laragon, PowerShell)

Prasyarat: PHP 8.3+ (8.4 disarankan), Composer, MySQL, Git.

```powershell
cd C:\laragon\www\Muslim-Almara
composer install
Copy-Item .env.example .env
php artisan key:generate
# Buat database kosong "almara_muslim" (HeidiSQL/Laragon), lalu:
php artisan migrate --seed
php artisan almara:quran:validate
php artisan serve
```

Buka http://127.0.0.1:8000 (atau http://muslim-almara.test setelah *Reload* Laragon).

### Menjadikan akun Anda admin & meninjau konten

```powershell
# 1) Daftar akun melalui /daftar, lalu:
php artisan almara:make-admin email-anda@domain.com
# 2) (Disarankan) daftarkan akun kedua sebagai reviewer agama:
php artisan almara:make-admin reviewer@domain.com --role=reviewer
```

Doa, dzikir, dan materi panduan (Tajwid, Umroh, Haji, Ziarah) masuk berstatus **Menunggu review** dan baru tampil setelah disetujui di **Admin → Doa / Dzikir / Panduan** (blueprint §5.3). Gunakan tindakan massal *Setujui & terbitkan* setelah memeriksa isinya.

### Pengujian

```powershell
php artisan test
```

95 test mencakup perhitungan sholat/kiblat/Hijriah, integritas dataset, autentikasi, isolasi data pribadi (IDOR), alur review admin, audio qari, panduan, hafalan, terjemah, jurnal & koleksi, preferensi tema, API, dan PWA.

## Konfigurasi `.env`

| Kunci | Bawaan | Fungsi |
|---|---|---|
| `APP_NAME` | `ALMARA MUSLIM` | Nama aplikasi (juga di manifest PWA) |
| `ALMARA_DEFAULT_CITY` | `jakarta` | Kota bawaan |
| `ALMARA_PRAYER_METHOD` | `kemenag` | Metode jadwal bawaan |
| `HIJRI_ADJUSTMENT` | `0` | Koreksi tanggal Hijriah |
| `ALMARA_MUSHAF_EDITION` | kosong | Aktifkan Mushaf penuh setelah aset berlisensi tersedia |
| `SANCTUM_STATEFUL_DOMAINS` | localhost… | Domain yang memakai sesi untuk API |
| `ALMARA_AUDIO_DISK` | `local` | Disk privat rekaman Qari ALMARA (`storage/app/private`) |
| `ALMARA_AUDIO_MAX_MB` / `ALMARA_AUDIO_MAX_MINUTES` | `150` / `240` | Batas unggah rekaman |
| `QF_ENABLED`, `QF_CLIENT_ID`, `QF_CLIENT_SECRET` | nonaktif | Qari berlisensi Quran.Foundation (kredensial hanya di server) |
| `QF_ENV` | `production` | `prelive` hanya berisi surah 1–2 |
| `QF_MOCK` | `false` | Mode simulasi pemutar (nada uji) tanpa kredensial |
| `TRANSLATE_URL`, `TRANSLATE_API_KEY` | kosong | Terjemah teks bebas via layanan kompatibel LibreTranslate (frasa offline tetap tersedia) |
| `OVERPASS_URL` | overpass-api.de | Sumber data masjid terdekat (OpenStreetMap) |
| `ALMARA_WEATHER` | `true` | Kartu cuaca di beranda (Open-Meteo, di-cache 30 menit per lokasi) |
| `OPEN_METEO_API_KEY` | kosong | **Wajib untuk penggunaan komersial** (aplikasi disewakan/berbayar/beriklan); API gratis Open-Meteo hanya untuk non-komersial |

## Audio qari

1. **Qari ALMARA** — Admin → *Audio qari* → *Tambah qari* (isi dasar hak penggunaan) → *Unggah rekaman* per surah (MP3/M4A/OGG/WAV). Berkas masuk karantina berstatus draf, dicek tanda tangan berkas, ukuran, durasi, dan checksum SHA-256. Opsional: penanda waktu per ayat (`ayat mulai selesai`) untuk sorot ayat & mode per ayat. Ajukan review → reviewer lain menyetujui → terbit; versi lama otomatis diarsipkan. Rekaman terbit dapat diputar tanpa login dengan dukungan HTTP Range.
   Naikkan batas unggah PHP agar rekaman besar dapat diunggah (Laragon: Menu → PHP → php.ini): `upload_max_filesize = 200M`, `post_max_size = 210M`, `max_execution_time = 300`, lalu *Reload*.
2. **Quran.Foundation** — daftar di Developer Console (https://api-docs.quran.foundation), isi `QF_ENABLED=true`, `QF_CLIENT_ID`, `QF_CLIENT_SECRET`, lalu `php artisan config:clear`. Atribusi *"Quran data provided by Quran.Foundation"* tampil otomatis; data di-cache ≤ 1 hari (batas penyedia 1 minggu) dan tidak disediakan untuk offline. Penggunaan komersial/penyewaan aplikasi: pastikan sesuai Developer Terms penyedia.

Nilai bawaan metode/kota/Hijriah juga dapat diubah di **Admin → Konfigurasi**.

## PWA & HTTPS

PWA aktif di HTTPS atau `localhost`/`127.0.0.1`. Untuk `muslim-almara.test`, aktifkan SSL Laragon (Menu → Apache → SSL → Enabled). Untuk HP, gunakan domain HTTPS atau tunnel aman.

## Dokumentasi

`docs/architecture.md` · `docs/api.md` · `docs/content-sources.md` · `docs/security.md` · `docs/deployment.md` · `docs/adr/`

## Sumber data

Teks, terjemahan & tafsir Al-Qur'an: Kementerian Agama RI via [rioastamal/quran-json](https://github.com/rioastamal/quran-json) (MIT). Periksa ketentuan redistribusi sebelum produksi — lihat `docs/content-sources.md`.

---
© 2026 ALMARA MUSLIM · @2026-Alifiandra
