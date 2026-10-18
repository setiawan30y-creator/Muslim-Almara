# Keamanan & privasi

- **Autentikasi**: hash kata sandi bawaan Laravel; kebijakan sandi min. 8 karakter huruf+angka; reset sandi bertoken (60 menit) dengan pesan seragam (tidak membocorkan email terdaftar); verifikasi email; ganti sandi mencabut token API & sesi perangkat lain.
- **Rate limit**: login/daftar/reset 5/menit per email + 20/menit per IP; API 120/menit; pencarian 30/menit.
- **Otorisasi**: izin berbasis peran (`admin.access`, `content.edit`, `content.review`, `content.publish`, `users.manage`, `settings.manage`, `audit.view`). Editor ≠ reviewer; pembuat/pengaju tidak dapat menyetujui konten sendiri; admin tidak dapat mencabut peran admin dirinya.
- **Data pribadi**: bookmark, catatan, progres, preferensi terikat `user_id` dan dicek Policy; diuji IDOR. Panel admin tidak menampilkan data ibadah pengguna.
- **Lokasi**: opsional; dibulatkan 3 desimal; hanya di cookie terenkripsi peramban; tidak disimpan di database/log.
- **Cache**: respons saat login, `/profil*`, `/admin*`, `/masuk`, `/api/v1/me*` diberi `Cache-Control: no-store, private` + `X-Almara-Private`; service worker tidak pernah menyimpan jalur pribadi dan membersihkan cache halaman saat keluar.
- **Header**: `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, `X-Request-Id`. CSP direncanakan setelah inventaris aset (Google Fonts).
- **Cookie preferensi** (`almara_tampilan`, `almara_quran`, `almara_sholat`, `almara_beranda`) tidak dienkripsi agar dapat dibaca JS, namun setiap nilai divalidasi terhadap daftar yang diizinkan.
- **Audit**: aksi admin, perubahan peran, perubahan & publikasi konten, ekspor/penghapusan akun. IP disimpan sebagai HMAC; metadata menyaring `password`, `token`, `body`.
- **Produksi**: `APP_DEBUG=false`, HTTPS, cookie `Secure` (`SESSION_SECURE_COOKIE=true`), `.env` hanya di server.
