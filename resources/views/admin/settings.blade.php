{{-- Admin: konfigurasi aplikasi — @2026-Alifiandra --}}
<x-layouts.admin title="Konfigurasi">
    <x-page-head title="Konfigurasi aplikasi" subtitle="Nilai bawaan untuk pengguna yang belum memilih sendiri" />
    <form method="post" action="{{ route('admin.settings.update') }}" class="card form" style="max-width:640px">
        @csrf @method('put')
        <div class="field"><label for="default_method">Metode jadwal sholat bawaan</label>
            <select id="default_method" name="default_method" class="select">@foreach ($methods as $m)<option value="{{ $m['key'] }}" @selected($values['prayer.default_method'] === $m['key'])>{{ $m['name'] }}</option>@endforeach</select></div>
        <div class="field"><label for="default_city">Kota bawaan</label>
            <select id="default_city" name="default_city" class="select">@foreach ($cities as $c)<option value="{{ $c['slug'] }}" @selected($values['location.default_city'] === $c['slug'])>{{ $c['nama'] }}</option>@endforeach</select></div>
        <div class="field"><label for="hijri_adjustment">Koreksi tanggal Hijriah (hari)</label>
            <input id="hijri_adjustment" name="hijri_adjustment" type="number" min="-2" max="2" class="input" value="{{ $values['hijri.adjustment'] }}">
            <span class="hint">Sesuaikan bila penetapan resmi (sidang isbat) berbeda dengan perhitungan.</span></div>
        <div class="form-actions"><button class="btn" data-loading>Simpan</button></div>
    </form>
</x-layouts.admin>
