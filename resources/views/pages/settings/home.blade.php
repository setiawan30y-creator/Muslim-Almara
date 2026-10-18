{{-- Pengaturan beranda (blueprint §8.3: susun & sembunyikan kartu) — @2026-Alifiandra --}}
@php $cards = \App\Services\Preferences\PreferenceService::HOME_CARDS; @endphp
<x-layouts.app title="Pengaturan Beranda">
    <x-page-head title="Kartu beranda" subtitle="Urutkan dan pilih kartu yang ditampilkan" :back="route('settings.index')" />
    <form method="post" action="{{ route('settings.home.save') }}" class="card form" style="max-width:640px" data-sortable-form>
        @csrf
        <ol class="list" data-sortable style="padding:0">
            @foreach ($current['order'] as $key)
                <li class="setting-row" data-key="{{ $key }}">
                    <input type="hidden" name="order[]" value="{{ $key }}">
                    <label class="check" style="flex:1"><input type="checkbox" name="visible[]" value="{{ $key }}" @checked(! in_array($key, $current['hidden'], true))> <span>{{ $cards[$key] }}</span></label>
                    <span style="display:flex;gap:2px">
                        <button type="button" class="icon-btn" data-move="up" aria-label="Naikkan {{ $cards[$key] }}"><x-icon name="chevron-left" style="transform:rotate(90deg)" /></button>
                        <button type="button" class="icon-btn" data-move="down" aria-label="Turunkan {{ $cards[$key] }}"><x-icon name="chevron-right" style="transform:rotate(90deg)" /></button>
                    </span>
                </li>
            @endforeach
        </ol>
        <div class="form-actions"><button class="btn" data-loading>Simpan</button><a class="btn btn--ghost" href="{{ route('home') }}">Lihat beranda</a></div>
    </form>
</x-layouts.app>
