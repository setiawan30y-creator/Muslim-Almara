# ADR 0003 — Konten agama wajib review sebelum terbit

- Status: diterima · 19 Okt 2026 · @2026-Alifiandra

## Keputusan
- Doa & dzikir disimpan dengan `review_status` (draft/review/approved/published/suspended/archived) dan `content_version`.
- Seeder memasukkan konten berstatus **review** — tidak tampil sampai disetujui reviewer agama di panel admin.
- Pembuat/pengaju tidak dapat menyetujui kontennya sendiri; perubahan teks konten terbit membuat versi baru (draf) dan jejak versi lama di log audit.
- Komponen wajib: teks Arab, transliterasi, arti Indonesia, rujukan; validasi menolak publikasi tanpa ketiganya.
- Kutipan ayat dalam doa diambil langsung dari dataset Kemenag (dicocokkan otomatis) agar tidak terjadi salah tulis.

## Konsekuensi
Setelah instalasi, pemilik perlu menunjuk reviewer dan meninjau konten awal sebelum halaman Dzikir & Doa terisi.
