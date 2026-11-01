# Deploy ke Ubuntu, backup & Play Store

Panduan untuk **Ubuntu 24.04 LTS** (VPS/cloud) dengan Nginx, PHP-FPM 8.4, dan MySQL.
Ganti `domain-anda.id` dengan domain Anda dan arahkan **A record** domain ke IP server sebelum mulai.
Semua perintah di bawah dijalankan di terminal server (SSH). @2026-Alifiandra

## 1. Paket server

```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install software-properties-common git unzip curl nginx mysql-server certbot python3-certbot-nginx ufw
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt -y install php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw --force enable
```

Batas unggah PHP (untuk rekaman qari) — edit `/etc/php/8.4/fpm/php.ini`:
`upload_max_filesize = 200M`, `post_max_size = 210M`, `max_execution_time = 300`, lalu `sudo systemctl restart php8.4-fpm`.

## 2. Database

```bash
sudo mysql -e "CREATE DATABASE almara_muslim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'almara'@'localhost' IDENTIFIED BY 'GANTI-SANDI-KUAT';
GRANT ALL PRIVILEGES ON almara_muslim.* TO 'almara'@'localhost'; FLUSH PRIVILEGES;"
```

## 3. Kode aplikasi

Repository sebaiknya **private**. Buat *deploy key* (GitHub → repo → Settings → Deploy keys) lalu:

```bash
sudo mkdir -p /var/www && sudo chown $USER:www-data /var/www
cd /var/www && git clone git@github.com:setiawan30y-creator/Muslim-Almara.git almara && cd almara
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Isi `.env` (minimal):

```
APP_NAME="ALMARA MUSLIM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.id
DB_CONNECTION=mysql
DB_DATABASE=almara_muslim
DB_USERNAME=almara
DB_PASSWORD=GANTI-SANDI-KUAT
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=domain-anda.id
MAIL_MAILER=smtp            # isi SMTP agar verifikasi email & reset sandi terkirim
MAIL_HOST=...
MAIL_FROM_ADDRESS=no-reply@domain-anda.id
VAPID_SUBJECT=mailto:admin@domain-anda.id
```

Pengingat adzan — buat kunci sekali, salin dua baris hasilnya ke `.env`:

```bash
php artisan almara:push:keys
```

Basis data & data awal:

```bash
php artisan migrate --force
php artisan db:seed --force            # peran, metode sholat, impor Al-Qur'an, doa/dzikir/panduan (status review)
php artisan almara:quran:validate
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R ug+rwX storage bootstrap/cache
```

## 4. Nginx & HTTPS

```bash
sudo cp deploy/nginx-almara.conf /etc/nginx/sites-available/almara
sudo sed -i 's/domain-anda.id/DOMAIN-ANDA/g' /etc/nginx/sites-available/almara
sudo ln -s /etc/nginx/sites-available/almara /etc/nginx/sites-enabled/ && sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d DOMAIN-ANDA --redirect -m admin@DOMAIN-ANDA --agree-tos -n
```

Setelah HTTPS aktif, PWA (tombol **Pasang**) langsung tersedia di HP.

## 5. Penjadwal (wajib untuk pengingat adzan)

```bash
( sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/almara && php artisan schedule:run >> /dev/null 2>&1" ) | sudo crontab -u www-data -
```

Periksa: `sudo -u www-data php artisan schedule:list` → `almara:push:adzan` setiap menit.

## 6. Akun admin & penerbitan konten

```bash
# Daftar 2 akun lewat https://DOMAIN-ANDA/daftar, lalu:
php artisan almara:make-admin email-admin@domain-anda.id
php artisan almara:make-admin email-reviewer@domain-anda.id --role=reviewer
# Setelah doa, dzikir, dan panduan DIPERIKSA (Admin → Doa / Dzikir / Panduan):
php artisan almara:content:publish email-reviewer@domain-anda.id
```

Perintah terakhir menerbitkan semua konten berstatus review sekaligus, tetap tercatat di riwayat review & log audit.

## 7. Fitur opsional (butuh layanan/izin pihak ketiga)

| Fitur | Isi di `.env` | Catatan |
|---|---|---|
| Cuaca untuk aplikasi komersial | `OPEN_METEO_API_KEY` | API gratis Open-Meteo hanya non-komersial |
| Qari berlisensi | `QF_ENABLED=true`, `QF_CLIENT_ID`, `QF_CLIENT_SECRET` | Developer Console Quran.Foundation; patuhi Developer Terms |
| Qari ALMARA | — | Unggah rekaman di Admin → Audio qari |
| Terjemah teks bebas | `TRANSLATE_URL`, `TRANSLATE_API_KEY` | Layanan kompatibel LibreTranslate, contoh di server sendiri (Docker): `docker run -d --restart unless-stopped -p 127.0.0.1:5000:5000 libretranslate/libretranslate --load-only id,ar,en` lalu `TRANSLATE_URL=http://127.0.0.1:5000` |
| Play Store (TWA) | `TWA_PACKAGE_NAME`, `TWA_SHA256_FINGERPRINTS` | Lihat bagian 9 |

Setiap mengubah `.env`: `php artisan config:cache`.

## 8. Memperbarui aplikasi

```bash
cd /var/www/almara && sudo -u www-data bash deploy/update.sh
```

## 9. Android / Play Store (TWA)

1. Pastikan PWA berjalan di HTTPS, lalu buat paket Android dengan Bubblewrap (`npm i -g @bubblewrap/cli`, `bubblewrap init --manifest https://DOMAIN-ANDA/manifest.webmanifest`).
2. Isi `TWA_PACKAGE_NAME` (mis. `id.domainanda.almara`) dan `TWA_SHA256_FINGERPRINTS` (dari Play Console → App integrity) di `.env`, `php artisan config:cache`.
3. Periksa `https://DOMAIN-ANDA/.well-known/assetlinks.json` berisi paket & fingerprint.
4. Uji login, tombol kembali, tautan eksternal, offline, notifikasi adzan, dan pembaruan service worker di perangkat nyata.

## 10. Backup & pemulihan

```bash
sudo mkdir -p /var/backups/almara
( sudo crontab -l 2>/dev/null; echo "30 2 * * * mysqldump --single-transaction almara_muslim | gzip > /var/backups/almara/db-\$(date +\%F).sql.gz && find /var/backups/almara -mtime +14 -delete" ) | sudo crontab -
```

- Cadangkan juga `storage/app/private` (rekaman qari) dan `.env` di lokasi terpisah & terenkripsi.
- Uji pemulihan berkala ke database terpisah, lalu `php artisan almara:quran:validate`.
