{{-- Kebijakan Privasi (blueprint §6.3, §10.3) — @2026-Alifiandra --}}
<x-layouts.app title="Kebijakan Privasi">
    <x-page-head title="Kebijakan Privasi" subtitle="Terakhir diperbarui {{ \Carbon\Carbon::create(2026, 10, 19)->translatedFormat('j F Y') }}" />
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
        <p><b>Terjemah teks</b> (bila diaktifkan): teks yang Anda kirim diteruskan ke layanan terjemah yang dikonfigurasi pengelola aplikasi dan tidak disimpan di database.</p>
        <p><b>Cuaca</b> diambil server aplikasi dari Open-Meteo berdasarkan koordinat lokasi yang dibulatkan (±1 km); alamat IP Anda tidak dikirim ke penyedia.</p>
        <p><b>Audio qari</b> dari Quran.Foundation (bila diaktifkan) diputar langsung dari server penyedia.</p>
        <h2>Perubahan</h2>
        <p>Kebijakan ini dapat diperbarui. Perubahan berlaku sejak dipublikasikan di halaman ini.</p>
    </article>
</x-layouts.app>
