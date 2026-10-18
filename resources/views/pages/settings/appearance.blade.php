{{-- Pengaturan → Tampilan & Tema (blueprint: Pengaturan Tampilan & Tema) — @2026-Alifiandra --}}
@php
    use App\Enums\{ThemeMode, AccentColor, TextSize};
    $defaults = \App\Services\Preferences\PreferenceService::APPEARANCE_DEFAULTS;
@endphp
<x-layouts.app title="Tampilan & Tema">
    <x-page-head title="Tampilan & Tema" subtitle="Perubahan langsung terlihat sebagai pratinjau" :back="route('settings.index')" />

    <form method="post" action="{{ route('settings.appearance.save') }}" class="layout-side" data-appearance-form data-defaults='@json($defaults)'>
        @csrf
        <div class="card">
            <div class="setting-group">
                <h2>1. Mode tampilan</h2>
                <div class="segmented" role="radiogroup" aria-label="Mode tampilan">
                    @foreach (ThemeMode::cases() as $m)
                        <label><input type="radio" name="theme_mode" value="{{ $m->value }}" @checked($current['theme_mode'] === $m->value)>
                            <span><x-icon :name="$m->value === 'dark' ? 'moon' : ($m->value === 'light' ? 'sun' : 'theme')" />{{ $m->label() }}</span></label>
                    @endforeach
                </div>
            </div>

            <div class="setting-group">
                <h2>2. Warna aksen</h2>
                <div class="swatches" role="radiogroup" aria-label="Warna aksen">
                    @foreach (AccentColor::cases() as $c)
                        <label class="swatch"><input type="radio" name="accent_color" value="{{ $c->value }}" @checked($current['accent_color'] === $c->value)>
                            <span><i style="background:{{ $c->swatch() }}"></i>{{ $c->label() }}</span></label>
                    @endforeach
                </div>
                <p class="small muted" style="margin:8px 0 0">Warna aksen hanya mengubah tombol, tautan, dan penanda — tidak mengubah teks Al-Qur'an.</p>
            </div>

            <div class="setting-group">
                <h2>3. Ukuran teks antarmuka</h2>
                <div class="segmented" role="radiogroup" aria-label="Ukuran teks antarmuka">
                    @foreach (TextSize::interface() as $s)
                        <label><input type="radio" name="interface_text_size" value="{{ $s->value }}" @checked($current['interface_text_size'] === $s->value)><span>{{ $s->label() }}</span></label>
                    @endforeach
                </div>
            </div>

            <div class="setting-group">
                <h2>4. Ukuran teks bacaan</h2>
                <p class="small muted" style="margin-bottom:8px">Teks Arab (Al-Qur'an, doa, dzikir)</p>
                <div class="segmented" role="radiogroup" aria-label="Ukuran teks Arab">
                    @foreach (TextSize::cases() as $s)
                        <label><input type="radio" name="reading_text_size" value="{{ $s->value }}" @checked($current['reading_text_size'] === $s->value)><span>{{ $s->label() }}</span></label>
                    @endforeach
                </div>
                <p class="small muted" style="margin:14px 0 8px">Terjemahan & transliterasi</p>
                <div class="segmented" role="radiogroup" aria-label="Ukuran teks terjemahan">
                    @foreach (TextSize::cases() as $s)
                        <label><input type="radio" name="translation_text_size" value="{{ $s->value }}" @checked($current['translation_text_size'] === $s->value)><span>{{ $s->label() }}</span></label>
                    @endforeach
                </div>
            </div>

            <div class="form-actions">
                <button class="btn" data-loading><x-icon name="check" />Simpan</button>
                <button type="button" class="btn btn--outline" data-appearance-reset><x-icon name="reset" />Reset ke awal</button>
            </div>
            <p class="small muted" style="margin:12px 0 0">{{ auth()->check() ? 'Tersimpan di akun Anda dan berlaku di semua perangkat.' : 'Tersimpan di perangkat ini. Masuk agar tersinkron antar perangkat.' }}</p>
        </div>

        <aside class="stack">
            <section class="card" aria-label="Pratinjau">
                <h2 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">5. Pratinjau</h2>
                <div class="preview">
                    <p class="arabic" lang="ar" style="margin:0">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</p>
                    <p class="tr" style="margin:6px 0 12px">Dengan nama Allah Yang Maha Pengasih, Maha Penyayang.</p>
                    <div class="form-actions"><span class="btn btn--sm">Tombol utama</span><span class="chip is-active">Terpilih</span><a href="#" onclick="return false">Tautan</a></div>
                </div>
            </section>
        </aside>
    </form>
</x-layouts.app>
