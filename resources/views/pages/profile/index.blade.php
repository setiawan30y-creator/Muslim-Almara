{{-- Tab Profil (blueprint §8.2) — @2026-Alifiandra --}}
<x-layouts.app title="Profil" :noindex="true">
    @if ($user)
        <x-page-head :title="$user->name" :subtitle="$user->email" />
        <div class="stack">
            @unless ($user->hasVerifiedEmail())
                <div class="alert alert--warning"><x-icon name="alert" /><div>Email belum diverifikasi. <form method="post" action="{{ route('verification.send') }}" style="display:inline">@csrf<button class="link-btn">Kirim ulang tautan verifikasi</button></form></div></div>
            @endunless

            <section class="stats" aria-label="Ringkasan progres">
                <div class="stat"><b>{{ $stats['today'] }}</b><span>ayat dibaca hari ini</span></div>
                <div class="stat"><b>{{ $stats['khatam_percent'] }}%</b><span>menuju khatam</span></div>
                <div class="stat"><b>{{ $counts['bookmarks'] }}</b><span>bookmark</span></div>
                <div class="stat"><b>{{ $counts['notes'] }}</b><span>catatan</span></div>
            </section>

            <div class="card card--list">
                <ul class="list">
                    @foreach ([
                        ['profile.progress', 'chart', 'Progres & target tilawah', 'Target harian, khatam, riwayat baca'],
                        ['profile.bookmarks', 'bookmark', 'Bookmark & koleksi', 'Ayat tersimpan per tema'],
                        ['hafalan', 'repeat', 'Hafalan & murojaah', 'Progres hafalan & jadwal ulang'],
                        ['profile.notes', 'note', 'Jurnal & catatan', 'Catatan tadabur & jurnal — privat'],
                        ['settings.index', 'settings', 'Pengaturan', 'Tampilan & tema, Al-Qur\'an, jadwal sholat, beranda'],
                        ['profile.edit', 'user', 'Akun', 'Nama, email, kata sandi'],
                        ['profile.data', 'shield', 'Data & privasi', 'Ekspor atau hapus data Anda'],
                    ] as [$r, $i, $t, $d])
                        <li><a class="list-row" href="{{ route($r) }}"><x-icon :name="$i" style="width:22px;height:22px;color:var(--color-primary);flex:none" />
                            <span class="list-row__main"><span class="list-row__title">{{ $t }}</span><span class="list-row__meta" style="display:block">{{ $d }}</span></span>
                            <x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                    @endforeach
                    @can('admin.access')
                        <li><a class="list-row" href="{{ route('admin.dashboard') }}"><x-icon name="layout" style="width:22px;height:22px;color:var(--color-accent);flex:none" />
                            <span class="list-row__main"><span class="list-row__title">Panel admin</span><span class="list-row__meta" style="display:block">{{ $user->roles->pluck('label')->join(', ') }}</span></span>
                            <x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                    @endcan
                </ul>
            </div>

            <section class="card" aria-labelledby="pwa-h">
                <h2 id="pwa-h"><x-icon name="phone" style="width:20px;height:20px;display:inline;vertical-align:-4px;color:var(--color-primary)" /> Aplikasi &amp; offline</h2>
                <p class="small muted" data-pwa-state>Memeriksa dukungan aplikasi…</p>
                <button type="button" class="btn btn--sm" data-install-action hidden><x-icon name="download" />Pasang aplikasi</button>
            </section>

            <form method="post" action="{{ route('logout') }}">@csrf
                <button class="btn btn--outline btn--block"><x-icon name="logout" />Keluar</button>
            </form>
        </div>
    @else
        <x-page-head title="Profil" subtitle="Masuk untuk menyimpan progres di akun Anda" />
        <div class="stack">
            <section class="card card--pad-lg">
                <h2>Akun gratis</h2>
                <p class="muted">Dengan akun, bookmark, catatan pribadi, progres tilawah, dan pengaturan tampilan tersinkron antar perangkat. Data Anda privat secara default.</p>
                <div class="form-actions"><a href="{{ route('login') }}" class="btn">Masuk</a><a href="{{ route('register') }}" class="btn btn--outline">Daftar</a></div>
            </section>
            <div class="card card--list">
                <ul class="list">
                    <li><a class="list-row" href="{{ route('settings.index') }}"><x-icon name="settings" style="width:22px;height:22px;color:var(--color-primary);flex:none" />
                        <span class="list-row__main"><span class="list-row__title">Pengaturan</span><span class="list-row__meta" style="display:block">Tersimpan di perangkat ini</span></span>
                        <x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                    <li><a class="list-row" href="{{ route('about') }}"><x-icon name="info" style="width:22px;height:22px;color:var(--color-primary);flex:none" />
                        <span class="list-row__main"><span class="list-row__title">Tentang &amp; sumber data</span></span>
                        <x-icon name="chevron-right" style="width:20px;height:20px;color:var(--color-text-muted)" /></a></li>
                </ul>
            </div>
            <section class="card" aria-labelledby="pwa-h">
                <h2 id="pwa-h">Aplikasi &amp; offline</h2>
                <p class="small muted" data-pwa-state>Memeriksa dukungan aplikasi…</p>
                <button type="button" class="btn btn--sm" data-install-action hidden><x-icon name="download" />Pasang aplikasi</button>
            </section>
        </div>
    @endif
</x-layouts.app>
