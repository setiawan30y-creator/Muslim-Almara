{{-- Pengaturan Al-Qur'an (blueprint: Dua Mode Tampilan Al-Qur'an) — @2026-Alifiandra --}}
@php use App\Enums\QuranReadingMode; @endphp
<x-layouts.app title="Pengaturan Al-Qur'an">
    <x-page-head title="Pengaturan Al-Qur'an" :back="route('settings.index')" />
    <form method="post" action="{{ route('settings.quran.save') }}" class="card form" style="max-width:760px">
        @csrf
        <div class="setting-group">
            <h2>Mode baca</h2>
            <div class="segmented" role="radiogroup" aria-label="Mode baca">
                @foreach (QuranReadingMode::cases() as $m)
                    <label><input type="radio" name="reading_mode" value="{{ $m->value }}" @checked($current['reading_mode'] === $m->value)><span>{{ $m->label() }}</span></label>
                @endforeach
            </div>
            @unless ($mushafAvailable)
                <p class="small muted" style="margin:8px 0 0">Mode <b>Mushaf penuh</b> membutuhkan aset halaman Mushaf digital yang sumber, versi, dan hak penggunaannya telah diverifikasi. Selama belum tersedia, pembaca otomatis memakai mode potongan ayat dan pilihan Anda tetap tersimpan.</p>
            @endunless
        </div>
        <div class="setting-row"><div><b>Tampilkan terjemahan Indonesia</b><span class="muted">Dapat disembunyikan kapan saja di halaman surah</span></div>
            <label class="switch"><input type="checkbox" name="show_translation" value="1" @checked($current['show_translation'])><span class="switch__track"></span><span class="sr-only">Tampilkan terjemahan</span></label></div>
        <div class="setting-row"><div><b>Tampilkan tafsir ringkas</b><span class="muted">Tafsir Kemenag di bawah setiap ayat</span></div>
            <label class="switch"><input type="checkbox" name="show_tafsir" value="1" @checked($current['show_tafsir'])><span class="switch__track"></span><span class="sr-only">Tampilkan tafsir</span></label></div>
        <div class="setting-row"><div><b>Transliterasi Latin ayat</b><span class="muted">Belum tersedia — menunggu dataset transliterasi berlisensi</span></div>
            <label class="switch"><input type="checkbox" name="show_transliteration" value="1" disabled @checked($current['show_transliteration'])><span class="switch__track"></span><span class="sr-only">Transliterasi</span></label></div>
        <div class="setting-row"><div><b>Mode audio</b><span class="muted">Audio Qari akan aktif setelah rekaman berlisensi diterbitkan</span></div>
            <select name="audio_mode" class="select" style="max-width:200px">
                <option value="surah" @selected($current['audio_mode'] === 'surah')>Satu surah penuh</option>
                <option value="verse" @selected($current['audio_mode'] === 'verse')>Per ayat</option>
            </select></div>
        <div class="form-actions"><button class="btn" data-loading>Simpan</button><a class="btn btn--ghost" href="{{ route('settings.appearance') }}">Ukuran huruf Arab →</a></div>
    </form>
</x-layouts.app>
