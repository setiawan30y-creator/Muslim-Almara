# ALMARA MUSLIM

Pendamping Muslim pribadi — Al-Qur'an, jadwal sholat, kiblat, dzikir & doa, kalender Hijriah, progres ibadah privat, dan PWA yang dapat dipasang.
Dibangun dengan **Laravel 13** mengikuti *ALMARA MUSLIM — Blueprint Teknis & Roadmap Implementasi*.

> Dikembangkan oleh **@2026-Alifiandra**

## Status rilis

Versi **2.0.0** mencakup Rilis Pertama (MVP, blueprint §1.1) + *Pengaturan Tampilan & Tema*:

| Area | Isi |
|---|---|
| Navigasi | Beranda · Qur'an · Belajar · Ibadah · Profil |
| Beranda | Salam & lokasi, sholat berikutnya + hitung mundur, aksi cepat, lanjutkan bacaan, target tilawah, ayat harian, hari besar, catatan — kartu dapat diurutkan/disembunyikan |
| Al-Qur'an | 114 surah & 30 juz, terjemahan & tafsir Kemenag, pencarian, bookmark, catatan pribadi, tandai dibaca, unduh offline, pilihan mode baca |
| Ibadah | Jadwal sholat (8 metode, mazhab Asar, koreksi menit), kiblat + kalibrasi, dzikir & doa bersumber, tasbih, kalender Hijriah, zakat |
| Akun | Daftar/masuk, reset sandi, verifikasi email, progres & target, ekspor & hapus data |
| Pengaturan | Tampilan & Tema (Terang/Gelap/Sistem, 5 aksen, ukuran teks UI/Arab/terjemahan, pratinjau, reset), Al-Qur'an, Jadwal Sholat, Beranda |
| Admin | Peran admin/editor/reviewer, alur draft → review → terbit, versi konten, sumber & lisensi, pengguna, konfigurasi, log audit |
| API | `/api/v1` format `{data, meta}` — lihat `docs/api.md` |
| PWA | Manifest, service worker berversi, offline, halaman pribadi tidak pernah dicache |

Modul tahap berikutnya (Tajwid, Translate, Umroh & Haji, Ziarah Kubur, Qari & audio, Hafalan, Premium/Iklan, Cuaca & Masjid Terdekat, TWA) mengikuti roadmap blueprint §13.

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

Doa & dzikir awal masuk berstatus **Menunggu review** dan baru tampil setelah disetujui di **Admin → Doa/Dzikir** (blueprint §5.3).

### Pengujian

```powershell
php artisan test
```

79 test mencakup perhitungan sholat/kiblat/Hijriah, integritas dataset, autentikasi, isolasi data pribadi (IDOR), alur review admin, preferensi tema, API, dan PWA.

## Konfigurasi `.env`

| Kunci | Bawaan | Fungsi |
|---|---|---|
| `APP_NAME` | `ALMARA MUSLIM` | Nama aplikasi (juga di manifest PWA) |
| `ALMARA_DEFAULT_CITY` | `jakarta` | Kota bawaan |
| `ALMARA_PRAYER_METHOD` | `kemenag` | Metode jadwal bawaan |
| `HIJRI_ADJUSTMENT` | `0` | Koreksi tanggal Hijriah |
| `ALMARA_MUSHAF_EDITION` | kosong | Aktifkan Mushaf penuh setelah aset berlisensi tersedia |
| `SANCTUM_STATEFUL_DOMAINS` | localhost… | Domain yang memakai sesi untuk API |

Nilai bawaan metode/kota/Hijriah juga dapat diubah di **Admin → Konfigurasi**.

## PWA & HTTPS

PWA aktif di HTTPS atau `localhost`/`127.0.0.1`. Untuk `muslim-almara.test`, aktifkan SSL Laragon (Menu → Apache → SSL → Enabled). Untuk HP, gunakan domain HTTPS atau tunnel aman.

## Dokumentasi

`docs/architecture.md` · `docs/api.md` · `docs/content-sources.md` · `docs/security.md` · `docs/deployment.md` · `docs/adr/`

## Sumber data

Teks, terjemahan & tafsir Al-Qur'an: Kementerian Agama RI via [rioastamal/quran-json](https://github.com/rioastamal/quran-json) (MIT). Periksa ketentuan redistribusi sebelum produksi — lihat `docs/content-sources.md`.

---
© 2026 ALMARA MUSLIM · @2026-Alifiandra
