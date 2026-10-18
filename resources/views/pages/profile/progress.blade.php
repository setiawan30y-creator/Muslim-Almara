{{-- Progres & target tilawah (blueprint §4.8) — @2026-Alifiandra --}}
<x-layouts.app title="Progres tilawah" :noindex="true">
    <x-page-head title="Progres & target tilawah" subtitle="Privat — hanya untuk Anda" :back="route('profile')" />
    <div class="stack" style="max-width:820px">
        <section class="stats">
            <div class="stat"><b>{{ $stats['today'] }}</b><span>ayat hari ini</span></div>
            <div class="stat"><b>{{ $stats['total'] }}</b><span>ayat tercatat</span></div>
            <div class="stat"><b>{{ $stats['khatam_percent'] }}%</b><span>menuju khatam</span></div>
            <div class="stat"><b>{{ $stats['streak'] }}</b><span>hari berturut-turut</span></div>
        </section>
        @if ($stats['target'])
            <section class="card">
                <h2>Target hari ini</h2>
                <p style="margin-bottom:8px"><b class="tabular">{{ $stats['today'] }}</b> dari {{ $stats['target'] }} ayat</p>
                <div class="meter"><span style="width:{{ $stats['target_percent'] }}%"></span></div>
            </section>
        @endif
        @if ($lastRead)
            <a class="card" href="{{ route('quran.show', $lastRead->verse->surah->number) }}#ayat-{{ $lastRead->verse->ayah_number }}" style="display:block">
                <span class="small muted">Terakhir dibaca</span><br><b>QS. {{ $lastRead->verse->surah->name_latin }} · ayat {{ $lastRead->verse->ayah_number }}</b>
            </a>
        @endif
        <form method="post" action="{{ route('profile.goal') }}" class="card form">
            @csrf
            <h2>Atur target</h2>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="daily_target">Target ayat per hari</label>
                    <input id="daily_target" name="daily_target" type="number" min="1" max="1000" class="input" value="{{ old('daily_target', $goal?->daily_target) }}" placeholder="Contoh: 20">
                    <span class="hint">Kosongkan untuk menonaktifkan target.</span></div>
                <div class="field"><label for="khatam_target_date">Target khatam (opsional)</label>
                    <input id="khatam_target_date" name="khatam_target_date" type="date" class="input" value="{{ old('khatam_target_date', $goal?->khatam_target_date?->format('Y-m-d')) }}"></div>
            </div>
            <label class="check"><input type="checkbox" name="track_reading" value="1" @checked(old('track_reading', $tracking))> <span>Catat ayat yang saya tandai “dibaca” (dapat dimatikan & dihapus kapan saja).</span></label>
            <div class="form-actions"><button class="btn" data-loading>Simpan target</button><a class="btn btn--ghost" href="{{ route('profile.data') }}">Hapus riwayat</a></div>
        </form>
        <p class="small muted">Tandai bacaan dengan ikon <x-icon name="check-circle" style="width:16px;height:16px;display:inline;vertical-align:-3px" /> pada ayat terakhir yang Anda baca. Progres tidak dibagikan dan tidak ada papan peringkat.</p>
    </div>
</x-layouts.app>
