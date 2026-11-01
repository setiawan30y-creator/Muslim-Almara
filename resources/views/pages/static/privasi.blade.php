{{-- Kebijakan Privasi (blueprint §6.3, §10.3) — @2026-Alifiandra --}}
<x-layouts.app title="Kebijakan Privasi">
    <x-page-head title="Kebijakan Privasi" subtitle="Terakhir diperbarui {{ \Carbon\Carbon::create(2026, 11, 1)->translatedFormat('j F Y') }}" />
    <article class="card card--pad-lg prose">
        <p>Kami menghormati privasi Anda. Fitur ibadah inti dapat digunakan tanpa akun.</p>
        <h2>Data akun</h2>
        <p>Jika Anda mendaftar, kami menyimpan nama, email, dan kata sandi (dalam bentuk hash). Bookmark, catatan pribadi, progres & target tilawah, serta preferensi tampilan disimpan di akun Anda, bersifat privat, dan hanya dapat diakses oleh Anda.</p>
        <h2>Lokasi</h2>
        <p>Lokasi bersifat opsional. Jika Anda memilih “Gunakan lokasi perangkat”, koordinat dibulatkan dan hanya disimpan di cookie peramban Anda untuk menghitung jadwal sholat dan arah kiblat. Kami tidak menyimpan riwayat lokasi di database.</p>
        <h2>Penyimpanan di perangkat</h2>
        <p>Tanpa akun, pilihan tampilan, pengaturan Al-Qur'an/jadwal sholat, susunan beranda, hitungan tasbih, dan isian kalkulator zakat disimpan di cookie/penyimpanan lokal peramban Anda. Unduhan offline Al-Qur'an disimpan di cache perangkat dan dapat dihapus kapan saja. Halaman akun dan admin tidak pernah disimpan ke cache offline.</p>
        <h2>Hak Anda</h2>
        <ul>
            <li>Mengunduh salinan data Anda (Profil → Data &amp; privasi → Ekspor).</li>
            <li>Menghapus riwayat bacaan, catatan, atau seluruh akun beserta data pribadinya.</li>
            <li>Mematikan pencatatan progres bacaan.</li>
        </ul>
        <h2>Log & keamanan</h2>
        <p>Server dapat mencatat data teknis (waktu akses, jenis peramban) untuk keamanan. Alamat IP pada log audit disimpan dalam bentuk hash. Log tidak memuat kata sandi, token, atau isi catatan pribadi.</p>
        <h2>Layanan pihak ketiga</h2>
        <p>Halaman memuat huruf dari Google Fonts; penyedia tersebut dapat menerima alamat IP Anda saat memuat huruf.</p>
        <p><b>Masjid terdekat:</b> koordinat pencarian dikirim langsung dari peramban Anda ke layanan OpenStreetMap (Overpass API dan server peta), dan pustaka peta dimuat dari cdnjs. Aplikasi tidak menyimpan koordinat tersebut.</p>
        <p><b>Terjemah teks & percakapan</b>: teks yang Anda kirim diteruskan server ke layanan terjemah yang dikonfigurasi pengelola aplikasi (mis. Google Translate, DeepL, LibreTranslate, atau MyMemory) dan tidak disimpan di database. Masukan suara (mikrofon) dan pembacaan suara memakai fitur peramban/perangkat Anda; pada sebagian peramban, suara diproses oleh penyedia peramban tersebut.</p>
        <p><b>Cuaca</b> diambil server aplikasi dari Open-Meteo berdasarkan koordinat lokasi yang dibulatkan (±1 km); alamat IP Anda tidak dikirim ke penyedia.</p>
        <p><b>Pengingat adzan</b> (bila Anda aktifkan): server menyimpan alamat langganan notifikasi peramban, lokasi yang dibulatkan (±1 km), metode jadwal, dan waktu yang dipilih. Isi notifikasi dienkripsi dan dikirim melalui layanan push peramban (Google, Mozilla, Apple, Microsoft). Mematikan pengingat menghapus data tersebut.</p>
        <p><b>Audio qari</b> dari Quran.Foundation (bila diaktifkan) diputar langsung dari server penyedia.</p>
        <h2 id="iklan">Iklan</h2>
        @if (app(\App\Services\Ads\AdManager::class)->enabled())
            <p>Aplikasi ini gratis dan dibiayai iklan dari mitra pihak ketiga (Google AdSense atau jaringan iklan sejenis). Iklan hanya tampil di area daftar dan bagian bawah halaman — <b>tidak pernah</b> di dalam bacaan Al-Qur'an, doa, dzikir, panduan ibadah, pemutar audio, maupun halaman akun.</p>
            <p>Saat pertama berkunjung Anda dapat memilih: <b>Izinkan</b> (iklan yang dipersonalisasi — mitra iklan dapat memakai cookie atau pengenal perangkat untuk menampilkan iklan yang relevan dan mengukur kinerjanya) atau <b>Iklan umum saja</b> (iklan non-personalisasi; cookie tetap dapat dipakai untuk membatasi frekuensi dan mencegah penipuan). Skrip iklan baru dimuat setelah Anda memilih. Pilihan disimpan di cookie <code>{{ \App\Services\Ads\AdManager::CONSENT_COOKIE }}</code> selama 12 bulan dan dapat diubah kapan saja melalui tautan <b>Pengaturan iklan</b> di bagian bawah halaman.</p>
            <p>Cara Google memakai data dari situs mitra dijelaskan di <a href="https://policies.google.com/technologies/partner-sites" rel="noopener" target="_blank">policies.google.com/technologies/partner-sites</a>. Anda juga dapat mengatur personalisasi iklan di <a href="https://adssettings.google.com" rel="noopener" target="_blank">adssettings.google.com</a>.</p>
        @else
            <p>Saat ini aplikasi tidak menampilkan iklan. Bila kelak iklan diaktifkan, bagian ini akan diperbarui dan Anda akan diminta memilih persetujuan sebelum skrip iklan dimuat.</p>
        @endif
        <h2>Perubahan</h2>
        <p>Kebijakan ini dapat diperbarui. Perubahan berlaku sejak dipublikasikan di halaman ini.</p>
    </article>
</x-layouts.app>
