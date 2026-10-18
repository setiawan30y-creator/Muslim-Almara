# Sumber konten, lisensi & validasi

| Kunci `content_sources` | Isi | Sumber | Lisensi / catatan |
|---|---|---|---|
| `quran-text-kemenag` | Teks Al-Qur'an (rasm Kemenag) | Kementerian Agama RI via [rioastamal/quran-json](https://github.com/rioastamal/quran-json) @73b1d52 | Dataset MIT; teks bersumber Kemenag RI |
| `quran-translation-kemenag-id` | Terjemahan Indonesia | idem | **Periksa ketentuan redistribusi Kemenag sebelum produksi** |
| `quran-tafsir-kemenag-id` | Tafsir ringkas | idem | idem |
| `almara-doa-dzikir` | 18 doa + 12 dzikir | Kompilasi tim; rujukan hadis per item; ayat diambil dari dataset Kemenag | Wajib review reviewer agama |
| `hijri-tabular` | Kalender Hijriah | Perhitungan aritmetika (urfi) | Dapat berbeda ±1 hari dari isbat |

| Qari ALMARA (`quran_reciters`, `quran_audio_tracks`) | Rekaman murattal milik platform | Unggahan admin; dasar hak dicatat per qari | Wajib review; bukan suara tiruan AI; checksum SHA-256 & versi |
| Quran.Foundation (tidak disimpan) | Audio qari berlisensi + penanda ayat | Content API v4 (OAuth2 client credentials) | Atribusi wajib; cache ≤ 1 minggu (aplikasi: 1 hari); tidak untuk offline; redistribusi/sublisensi butuh lisensi komersial — lihat [Developer Terms](https://api-docs.quran.foundation/legal/developer-terms/) |

Metode jadwal sholat: parameter publik masing-masing otoritas (Kemenag RI, MWL, Umm al-Qura, Egypt, ISNA, Karachi, JAKIM, MUIS). Validasi terhadap jadwal resmi setempat sebelum produksi.

## Perintah

```powershell
php artisan almara:quran:validate          # jumlah surah/ayat, urutan, teks kosong, checksum vs database
php artisan almara:quran:import            # validasi lalu impor idempoten (transaksi)
php artisan almara:quran:import --dry-run  # validasi saja
php artisan almara:content:check-licenses  # sumber tanpa lisensi/review & konten belum ditinjau
```

## Gerbang publikasi (blueprint §5.3)
Draf/impor → validasi format → cek sumber & lisensi (panel *Sumber & lisensi* → *Tandai*) → review reviewer → terbit → versi & audit → pantau laporan koreksi.
