{{-- Tentang — @2026-Alifiandra --}}
<x-layouts.app title="Tentang">
    <x-page-head title="Tentang {{ config('app.name') }}" />
    <article class="card card--pad-lg prose">
        <p>{{ config('app.name') }} adalah pendamping ibadah harian yang ringan dan dapat dibuka di ponsel, tablet, maupun komputer. Aplikasi ini menghimpun fitur yang paling sering dibutuhkan dalam satu tempat.</p>
        <h2>Fitur</h2>
        <ul>
            <li>Jadwal sholat harian dan bulanan berdasarkan lokasi (kota atau GPS).</li>
            <li>Al-Qur'an 114 surah dengan teks Arab, terjemahan, dan tafsir ringkas Kementerian Agama RI.</li>
            <li>Arah kiblat dengan kompas perangkat.</li>
            <li>Doa harian beserta sumber dalil, Asmaul Husna, tasbih digital.</li>
            <li>Kalender Hijriah, hari besar Islam, dan kalkulator zakat.</li>
        </ul>
        <h2>Sumber data &amp; metode</h2>
        <ul>
            <li>Teks dan terjemahan Al-Qur'an: Kementerian Agama RI, melalui dataset terbuka quran-json (lisensi MIT).</li>
            <li>Jadwal sholat dihitung secara astronomis dengan kriteria yang umum dipakai Kemenag RI (Subuh 20°, Isya 18°, ihtiyat 2 menit).</li>
            <li>Kalender Hijriah dihitung secara aritmetika; penetapan resmi awal bulan mengikuti keputusan pemerintah.</li>
        </ul>
        <p class="muted">Jika menemukan kekeliruan, mohon sampaikan kepada pengelola agar dapat segera diperbaiki.</p>
        <p class="small muted">Versi {{ config('muslim.version', '1.0.0') }} · Dikembangkan oleh @2026-Alifiandra</p>
    </article>
</x-layouts.app>
