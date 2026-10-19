# Arsitektur ALMARA MUSLIM

> Diperbarui 19 Oktober 2026 · @2026-Alifiandra · acuan: *ALMARA MUSLIM — Blueprint Teknis & Roadmap Implementasi*

## Ringkasan

Monolit modular Laravel 13 (blueprint §3.2). Web (Blade, SSR), PWA, dan kelak Android TWA memakai backend & API `/api/v1` yang sama. Klien tidak pernah terhubung langsung ke MySQL.

```
Peramban / PWA / TWA ──> Laravel Web (Blade) ──┐
                                               ├─> Service layer ──> MySQL (sumber kebenaran)
Klien API (token Sanctum) ──> /api/v1 ─────────┘        │
                                                         ├─> Cache file (data non-objek)
                                                         └─> resources/data (dataset berversi)
```

## Lapisan

| Lapisan | Lokasi | Tanggung jawab |
|---|---|---|
| Controller tipis | `app/Http/Controllers/{Quran,Worship,Account,Settings,Auth,Admin,Api/V1}` | validasi, otorisasi, panggil service, kembalikan respons |
| Form Request | `app/Http/Requests` | validasi input (mis. `ContentItemRequest`) |
| Policy / Gate | `app/Policies`, `AppServiceProvider` | kepemilikan data pribadi; izin berbasis peran |
| API Resource | `app/Http/Resources` | bentuk respons stabil |
| Service | `app/Services/{Quran,Prayer,Qibla,Calendar,Content,Preferences,UserProgress,Account}` | logika domain teruji |
| Enum | `app/Enums` | status konten, tema, aksen, ukuran teks, mode baca, mazhab Asar |
| Support | `app/Support` | `Location` (lokasi tanpa simpan server), `Audit` |
| Command | `app/Console/Commands` | impor/validasi dataset, cek lisensi, beri peran admin |

## Modul yang aktif (Rilis Pertama / MVP §1.1)

- **Beranda** (§8.3): salam & lokasi, sholat berikutnya + hitung mundur, aksi cepat, lanjutkan bacaan, target tilawah, ayat harian (+sumber terjemahan), hari besar, catatan terbaru. Kartu dapat diurutkan & disembunyikan.
- **Al-Qur'an** (§4.1): 114 surah, juz, teks Arab RTL, terjemahan & tafsir Kemenag, pencarian dasar, bookmark, catatan pribadi, tandai dibaca, lanjutkan bacaan, unduh offline. Pilihan mode baca (bagian *Dua Mode Tampilan*): preferensi bawaan **Mushaf penuh**, namun dirender sebagai potongan ayat sampai edisi Mushaf berlisensi dikonfigurasi (`ALMARA_MUSHAF_EDITION`).
- **Jadwal sholat** (§4.2): 8 metode terdokumentasi, mazhab Asar, koreksi menit per waktu, nama metode & sumber lokasi selalu ditampilkan.
- **Kiblat** (§4.3): azimut great-circle, kompas perangkat, status kalibrasi, fallback angka.
- **Dzikir & Doa** (§4.4, *Standar Wajib Bacaan*): Arab + Latin + arti Indonesia + rujukan + konteks; hanya status `published` yang tampil.
- **Kalender Hijriah** (§4.7), tasbih, zakat, Asmaul Husna.
- **Akun & privasi**: daftar/masuk/keluar, reset sandi, verifikasi email, ekspor & hapus data.
- **Pengaturan → Tampilan & Tema**: Terang/Gelap/Sistem, 5 aksen (diuji WCAG AA), ukuran teks UI/Arab/terjemahan, pratinjau, reset; per akun atau per perangkat.
- **Panel admin** (§8.5): peran admin/editor/reviewer, draft → review → terbit, versi konten, log audit, sumber & lisensi, konfigurasi.
- **PWA** (§9.1): manifest, service worker berversi, cache terpisah, halaman pribadi tidak pernah dicache.

## Modul lanjutan (aktif sejak 2.2.0)

- **Panduan** (`/panduan/{tajwid|umroh|haji|ziarah}`): tabel `guide_categories` + `guide_items`, tipe konten `panduan` pada alur review yang sama dengan doa/dzikir; contoh ayat diambil dari teks Kemenag di database; latihan pilihan ganda (`quiz_json`). Data awal `resources/data/panduan.json` masuk berstatus *review*.
- **Qari & audio** (§4.12): Qari ALMARA + Quran.Foundation — lihat README.
- **Hafalan & Murojaah** (§4.9.1): `user_memorizations`, pengulangan berjarak (lupa/ragu/lancar), data pribadi, termasuk ekspor.
- **Terjemah & Frasa** (§4.14–4.15): frasa offline `resources/data/frasa.json` + terjemah teks opsional lewat layanan kompatibel LibreTranslate (`TRANSLATE_URL`).
- **Masjid terdekat**: OpenStreetMap via Overpass API dari peramban + peta Leaflet.
- **Jurnal & Koleksi**: tag pada `user_notes`, `user_collections` untuk bookmark.

Belum diaktifkan: Mushaf penuh & transliterasi per ayat (menunggu dataset berlisensi), Premium & iklan, notifikasi push, TWA.

- **Cuaca** (beranda): `WeatherService` memanggil Open-Meteo dari server (IP pengguna tidak dikirim ke penyedia), cache 30 menit per koordinat ±1 km; API `GET /api/v1/weather`.

## Keputusan arsitektur

Lihat `docs/adr/`.
