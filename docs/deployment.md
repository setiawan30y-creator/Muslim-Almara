# Deployment, backup & TWA

## Produksi (blueprint §15)
1. Server Linux + Nginx + PHP-FPM (8.3/8.4) + MySQL, **HTTPS**; root web → `public/`.
2. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, `SESSION_SECURE_COOKIE=true`, kredensial DB & mail.
3. ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate   # sekali saja
   php artisan migrate --force
   php artisan db:seed --force          # peran, metode, peristiwa, impor Al-Qur'an, konten awal (status review)
   php artisan almara:quran:validate
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
4. Worker antrean & scheduler: `php artisan queue:work` (Supervisor) dan cron `* * * * * php artisan schedule:run`.

## Backup & pemulihan
- Basis data: `mysqldump --single-transaction almara_muslim | gzip > almara-$(date +%F).sql.gz` terjadwal harian; simpan terenkripsi di lokasi terpisah.
- Berkas: `storage/app` (bila nanti ada unggahan). `.env` disimpan di secret manager.
- **Uji pemulihan** berkala ke database terpisah, lalu jalankan `php artisan almara:quran:validate` dan `php artisan test`.

## Android TWA (setelah PWA stabil di HTTPS)
- Buat proyek TWA (mis. Bubblewrap) dengan `start_url` `/?source=pwa`.
- Sediakan `public/.well-known/assetlinks.json` berisi package name dan SHA-256 fingerprint sertifikat penandatangan aplikasi.
- Uji login, navigasi kembali, tautan eksternal, offline, dan pembaruan service worker di perangkat nyata.
