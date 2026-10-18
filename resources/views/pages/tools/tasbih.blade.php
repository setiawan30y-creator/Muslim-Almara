{{-- Tasbih Digital — @2026-Alifiandra --}}
@php
    // Pilihan bacaan diambil dari dzikir terbit (lengkap dengan sumber). Tanpa konten terbit → hitungan bebas.
    $dzikir = $items->map(fn ($d) => ['slug' => $d->slug, 'ar' => $d->arabic_text, 'lt' => $d->transliteration, 'mn' => $d->translation, 'src' => $d->source_reference, 'n' => $d->count_target])->values()->all();
    if (! $dzikir) {
        $dzikir = [['slug' => 'bebas', 'ar' => '', 'lt' => 'Hitungan bebas', 'mn' => 'Pilih bacaan dzikir Anda sendiri.', 'src' => '', 'n' => 33]];
    }
    $sel = collect($dzikir)->search(fn ($d) => $d['slug'] === request('dzikir'));
    $sel = $sel === false ? 0 : $sel;
@endphp
<x-layouts.app title="Tasbih Digital" description="Penghitung dzikir (tasbih digital) dengan target 33, 99, 100, atau bebas.">
    <x-page-head title="Tasbih Digital" subtitle="Ketuk lingkaran untuk menghitung dzikir" />

    <div class="grid-2">
        <section class="card card--pad-lg tasbih" aria-label="Penghitung">
            <div>
                <div class="tasbih__dzikir" lang="ar" data-dz-ar>{{ $dzikir[$sel]['ar'] }}</div>
                <div style="font-weight:700" data-dz-lt>{{ $dzikir[$sel]['lt'] }}</div>
                <div class="small muted" data-dz-mn>{{ $dzikir[$sel]['mn'] }}</div>
                <div class="small muted" style="margin-top:4px" data-dz-src>{{ $dzikir[$sel]['src'] ? 'Sumber: '.$dzikir[$sel]['src'] : '' }}</div>
            </div>
            <div class="tasbih__ring" data-ring>
                <button type="button" class="tasbih__btn" data-tap aria-label="Tambah hitungan">
                    <span class="tasbih__count" data-count aria-live="polite">0</span>
                    <span class="tasbih__target" data-target-label>dari 33</span>
                </button>
            </div>
            <div class="small muted">Putaran selesai: <b data-rounds>0</b> · Total: <b data-total>0</b></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
                <button type="button" class="btn btn--outline btn--sm" data-reset><x-icon name="reset" />Ulangi</button>
                <button type="button" class="btn btn--ghost btn--sm" data-vibrate aria-pressed="true">Getar: aktif</button>
            </div>
        </section>

        <div class="stack">
            <section class="card">
                <h2>Bacaan dzikir</h2>
                <div class="chips" style="flex-wrap:wrap" role="group" aria-label="Pilih dzikir">
                    @foreach ($dzikir as $i => $d)
                        <button type="button" class="chip" aria-pressed="{{ $i === $sel ? 'true' : 'false' }}" data-dz="{{ $i }}" data-ar="{{ $d['ar'] }}" data-lt="{{ $d['lt'] }}" data-mn="{{ $d['mn'] }}" data-src="{{ $d['src'] }}" data-n="{{ $d['n'] }}">{{ \Illuminate\Support\Str::limit($d['lt'], 28) }}</button>
                    @endforeach
                </div>
            </section>
            <section class="card">
                <h2>Target</h2>
                <div class="chips" role="group" aria-label="Pilih target">
                    @foreach ([33, 99, 100, 0] as $t)
                        <button type="button" class="chip" aria-pressed="{{ $t === 33 ? 'true' : 'false' }}" data-target="{{ $t }}">{{ $t ?: 'Bebas' }}</button>
                    @endforeach
                </div>
                <p class="small muted" style="margin:12px 0 0">Target otomatis mengikuti jumlah anjuran bacaan bila tersedia. Hitungan hanya tersimpan di perangkat ini.</p>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var S = window.MA.store, KEY = 'almara-tasbih';
        var st = Object.assign({ count: 0, total: 0, rounds: 0, target: 33, dz: 0, vibrate: true }, S.get(KEY, {}));
        var pre = {{ request()->filled('dzikir') ? $sel : -1 }};
        if (pre >= 0) { st.dz = pre; st.count = 0; var pb = document.querySelector('[data-dz="' + pre + '"]'); if (pb && +pb.dataset.n > 1) st.target = +pb.dataset.n; }
        if (!document.querySelector('[data-dz="' + st.dz + '"]')) st.dz = 0;
        var q = function (s) { return document.querySelector(s); };
        var qa = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };

        function render() {
            q('[data-count]').textContent = st.count;
            q('[data-total]').textContent = st.total;
            q('[data-rounds]').textContent = st.rounds;
            q('[data-target-label]').textContent = st.target ? 'dari ' + st.target : 'tanpa target';
            q('[data-ring]').style.setProperty('--p', st.target ? Math.min(100, st.count / st.target * 100) : 0);
            qa('[data-target]').forEach(function (b) { b.setAttribute('aria-pressed', String(+b.dataset.target === st.target)); });
            qa('[data-dz]').forEach(function (b) {
                var on = +b.dataset.dz === st.dz;
                b.setAttribute('aria-pressed', String(on));
                if (on) { q('[data-dz-ar]').textContent = b.dataset.ar; q('[data-dz-lt]').textContent = b.dataset.lt; q('[data-dz-mn]').textContent = b.dataset.mn; q('[data-dz-src]').textContent = b.dataset.src ? 'Sumber: ' + b.dataset.src : ''; }
            });
            var v = q('[data-vibrate]'); v.setAttribute('aria-pressed', String(st.vibrate)); v.textContent = 'Getar: ' + (st.vibrate ? 'aktif' : 'nonaktif');
            S.set(KEY, st);
        }
        function buzz(ms) { if (st.vibrate && navigator.vibrate) navigator.vibrate(ms); }

        q('[data-tap]').addEventListener('click', function () {
            st.count++; st.total++;
            if (st.target && st.count >= st.target) { st.rounds++; buzz([60, 60, 120]); window.MA.toast('Target ' + st.target + ' tercapai. Masya Allah!'); st.count = 0; }
            else buzz(15);
            render();
        });
        document.addEventListener('keydown', function (e) { if ((e.code === 'Space' || e.code === 'Enter') && e.target === document.body) { e.preventDefault(); q('[data-tap]').click(); } });
        qa('[data-target]').forEach(function (b) { b.addEventListener('click', function () { st.target = +b.dataset.target; st.count = 0; render(); }); });
        qa('[data-dz]').forEach(function (b) { b.addEventListener('click', function () { st.dz = +b.dataset.dz; st.count = 0; if (+b.dataset.n > 1 && [33, 99, 100].indexOf(+b.dataset.n) > -1) st.target = +b.dataset.n; render(); }); });
        q('[data-vibrate]').addEventListener('click', function () { st.vibrate = !st.vibrate; render(); });
        q('[data-reset]').addEventListener('click', function () {
            window.MA.confirm('Ulangi hitungan?', 'Hitungan, putaran, dan total akan dikembalikan ke nol.').then(function (ok) {
                if (ok) { st.count = 0; st.total = 0; st.rounds = 0; render(); window.MA.toast('Hitungan diulang'); }
            });
        });
        render();
    });
    </script>
    @endpush
</x-layouts.app>
