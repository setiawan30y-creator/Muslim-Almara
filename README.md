# Muslim Almara

Pendamping ibadah harian berbasis web — **mobile-first, Material-inspired**, dibangun dengan Laravel 13.

> Dikembangkan oleh **@2026-Alifiandra**

## Fitur

| Fitur | Rute | Keterangan |
|---|---|---|
| Beranda | `/` | Sholat berikutnya + hitung mundur, jadwal hari ini, ayat harian, hari besar Islam |
| Jadwal Sholat | `/jadwal-sholat` | Harian & bulanan; metode Kemenag RI (Subuh 20°, Isya 18°, ihtiyat 2 menit) |
| Pilih Lokasi | `/lokasi` | 48 kota atau GPS perangkat (disimpan di cookie pengguna) |
| Al-Qur'an | `/al-quran`, `/al-quran/{1-114}` | Teks Arab, terjemahan & tafsir ringkas Kemenag, penanda terakhir dibaca, ukuran huruf |
| Arah Kiblat | `/kiblat` | Derajat dari utara sejati, jarak ke Ka'bah, kompas sensor perangkat |
| Doa Harian | `/doa` | Arab, latin, arti, dan sumber dalil |
| Asmaul Husna | `/asmaul-husna` | 99 nama Allah |
| Tasbih Digital | `/tasbih` | Target 33/99/100/bebas, getar, tersimpan di perangkat |
| Kalender Hijriah | `/kalender` | Kalender ganda Masehi–Hijriah + hari besar Islam |
| Kalkulator Zakat | `/zakat` | Maal, penghasilan, fitrah (nisab 85 g emas) |

Semua data (Al-Qur'an, doa, kota) disertakan di `resources/data`, sehingga aplikasi **tidak bergantung pada API eksternal** dan **tidak membutuhkan database** untuk berjalan.

### REST API `/api/v1`

Format respons: `{"success": true, "data": ...}` · rate limit 120 request/menit.

```
GET /api/v1/jadwal-sholat?kota=jakarta&tanggal=2026-10-18
GET /api/v1/jadwal-sholat?lat=-6.2&lng=106.8&tz=7
GET /api/v1/kota
GET /api/v1/surat
GET /api/v1/surat/{nomor}?tafsir=1
GET /api/v1/doa
GET /api/v1/asmaul-husna
GET /api/v1/hijriah?tanggal=2026-10-18
GET /api/v1/kiblat?lat=-6.2&lng=106.8
```

## Instalasi (Laragon / Windows, PowerShell)

```powershell
cd C:\laragon\www
git clone https://github.com/setiawan30y-creator/Muslim-Almara.git
cd Muslim-Almara
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan serve
```

Buka http://127.0.0.1:8000 — atau, setelah *Reload* di Laragon, http://muslim-almara.test.

Database **tidak wajib** (sesi & cache memakai file). Jika nanti fitur akun ditambahkan, buat database `muslim_almara` lalu jalankan `php artisan migrate`.

### Pengaturan `.env`

| Kunci | Bawaan | Fungsi |
|---|---|---|
| `MUSLIM_DEFAULT_CITY` | `jakarta` | Kota bawaan (slug dari `resources/data/kota.json`) |
| `HIJRI_ADJUSTMENT` | `0` | Koreksi tanggal Hijriah (hari) agar sesuai penetapan resmi |
| `PRAYER_FAJR_ANGLE` / `PRAYER_ISHA_ANGLE` | `20` / `18` | Sudut Subuh/Isya |
| `PRAYER_IHTIYAT` | `2` | Menit pengaman |

### Pengujian

```powershell
php artisan test
```

## Struktur

```
app/Services/PrayerTimeService.php   perhitungan waktu sholat (astronomis)
app/Services/HijriCalendar.php       konversi Masehi ⇄ Hijriah, hari besar
app/Services/QiblaService.php        arah & jarak kiblat
app/Services/ContentRepository.php   data Al-Qur'an, doa, Asmaul Husna, kota
app/Support/Location.php             penentuan lokasi (query → cookie → bawaan)
public/css/app.css                   design system (token warna, tema terang/gelap)
public/js/app.js                     interaksi ringan tanpa framework
resources/views/components/          layout, ikon, page-head, empty state
resources/views/pages/               halaman
```

## Sumber data

- Teks & terjemahan Al-Qur'an serta tafsir ringkas: Kementerian Agama RI (quran.kemenag.go.id), melalui [rioastamal/quran-json](https://github.com/rioastamal/quran-json) (MIT).
- Jadwal sholat, kiblat, dan kalender Hijriah: dihitung secara lokal; hasil bersifat perkiraan.

## Roadmap

1. Akun pengguna (bookmark & preferensi tersinkron)
2. Audio murottal
3. Notifikasi adzan (PWA / Flutter)
4. Aplikasi Flutter Android/iOS memakai API `/api/v1`

---
© 2026 Muslim Almara · @2026-Alifiandra
