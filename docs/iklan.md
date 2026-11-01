# Modul Iklan (AdSense / CPM)

> @2026-Alifiandra

Iklan **mati secara default**. Iklan hanya tampil di bagian bawah halaman dan di area daftar beranda. Iklan **tidak pernah** dipasang di:

- halaman surah atau ayat Al-Qur'an, dan pemutar audio
- detail doa/dzikir, tasbih, panduan (Umroh, Haji, Ziarah, Tahlil), dan hafalan
- halaman akun, pengaturan, login/daftar, admin, kebijakan privasi, dan syarat

Daftar rute bebas iklan ada di `App\Services\Ads\AdManager::BLOCKED_ROUTES`.

## Mengaktifkan

1. Daftar Google AdSense dan tambahkan domain Anda. Domain harus sudah online dengan HTTPS dan konten yang cukup.
2. Buat 2 unit iklan *Display* (responsif): satu untuk **bawah halaman** dan satu untuk **daftar beranda**.
3. Isi `.env`:

   ```env
   ADS_ENABLED=true
   ADSENSE_CLIENT=ca-pub-XXXXXXXXXXXXXXXX
   ADSENSE_SLOT_FOOTER=1234567890
   ADSENSE_SLOT_LIST=0987654321
   # baris ads.txt dari jaringan lain (opsional), pisahkan dengan |
   ADS_TXT_EXTRA=
   ADS_HIDE_FOR_MEMBERS=false
   ```

4. Jalankan `php artisan optimize:clear`. Setelah itu cek `https://domain-anda/ads.txt`; isinya otomatis dibuat dari `ADSENSE_CLIENT`.

## Persetujuan (consent)

Pengunjung baru melihat banner dengan dua pilihan: **Izinkan** (iklan dipersonalisasi) atau **Iklan umum saja** (`requestNonPersonalizedAds=1`). Skrip AdSense baru dimuat setelah pengunjung memilih, dan dimuat lambat saat slot iklan mendekati layar. Pengunjung bisa mengubah pilihannya lewat tautan *Pengaturan iklan* di footer.

Untuk pengunjung dari EEA/Inggris/Swiss, Google mewajibkan CMP bersertifikat IAB TCF. Jika Anda menargetkan wilayah itu, aktifkan pesan *Privasi & pesan* di dashboard AdSense.

## Rekomendasi pemblokiran kategori (AdSense → Kontrol merek)

Blokir kategori yang tidak pantas untuk aplikasi ibadah: Perjudian & taruhan, Kencan, Alkohol, Seksual/sugestif, Obat & suplemen yang meragukan, Astrologi & paranormal, dan Pinjaman berbunga tinggi. Pertimbangkan juga Agama & spiritual (jika iklannya dari aliran lain) dan Politik.

Aktifkan juga *Ulasan iklan* agar iklan dari pengiklan tertentu bisa ditolak.
