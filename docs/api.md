# API v1

Basis: `/api/v1` · format (blueprint §7.1): berhasil `{"data": …, "meta": {…}}`, gagal `{"message": "…", "errors": {…}}`. Setiap respons membawa header `X-Request-Id`. Rate limit: 120/menit (umum), 30/menit (pencarian), 5/menit per email (login/daftar).

## Publik

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/health` | status layanan & database |
| GET | `/quran/surahs` · `/quran/surahs/{number}` | metadata surah (+sumber) |
| GET | `/quran/verses?surah=&from=&to=&tafsir=1&per_page=` | ayat + terjemahan (+tafsir), paginasi maks. 300 |
| GET | `/quran/verses/{id}` | detail ayat |
| GET | `/quran/search?q=` | cari di terjemahan atau rujukan `2:255` |
| GET | `/quran/translations` | daftar terjemahan + atribusi |
| GET | `/quran/audio/reciters?surah=` | daftar qari (`almara:{slug}` / `qf:{id}`), `meta.default` = qari pilihan pengguna |
| GET | `/quran/audio/surah/{surah}?reciter=` | `url` audio, `segments` (`ayah`, `start_ms`, `end_ms`), `attribution`, `offline_allowed`; 404 bila belum tersedia; `Cache-Control: no-store` |
| GET | `/prayer/methods` | metode perhitungan |
| GET | `/prayer/times?city=|lat=&lng=&tz=&date=&method=&asr=&offsets[subuh]=` | jadwal + metode & lokasi di `meta` |
| GET | `/qibla?lat=&lng=` | azimut & jarak |
| GET | `/dhikr/categories` · `/dhikr/items?category=` | hanya konten terbit |
| GET | `/dua/categories` · `/dua/items?category=` | hanya konten terbit |
| GET | `/islamic-events` | peristiwa + tanggal perkiraan |

## Autentikasi

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/auth/register` · `/auth/login` | mengembalikan token Sanctum (`device_name` opsional) |
| POST | `/auth/logout` | mencabut token saat ini |

Web memakai sesi (cookie) — domain harus terdaftar di `SANCTUM_STATEFUL_DOMAINS` (APP_URL otomatis).

## Pribadi (`auth:sanctum`)

| Method | Endpoint |
|---|---|
| GET/PATCH | `/me` |
| GET/POST | `/me/bookmarks` · DELETE `/me/bookmarks/{id}` |
| GET/POST | `/me/notes` · PATCH/DELETE `/me/notes/{id}` |
| GET/PUT | `/me/reading-progress` (`verse_id`, `mark_read`, `from_ayah`) |
| GET/PUT | `/me/reading-goals` |
| GET | `/me/data-export` · POST `/me/delete-account` (`password`) |
| GET/PUT | `/user/appearance-preferences` |
| GET/PUT | `/quran/reading-preferences` (termasuk `audio_mode`, `reciter`) |

Semua endpoint pribadi dibatasi ke pemilik akun (Policy) dan diuji terhadap IDOR (`tests/Feature/PersonalDataTest.php`).
