{{-- Tasbih Digital — @2026-Alifiandra --}}
@php
    $dzikir = [
        ['ar' => 'سُبْحَانَ اللّٰهِ', 'lt' => 'Subhanallah', 'mn' => 'Mahasuci Allah'],
        ['ar' => 'اَلْحَمْدُ لِلّٰهِ', 'lt' => 'Alhamdulillah', 'mn' => 'Segala puji bagi Allah'],
        ['ar' => 'اَللّٰهُ أَكْبَرُ', 'lt' => 'Allahu Akbar', 'mn' => 'Allah Mahabesar'],
        ['ar' => 'لَا إِلٰهَ إِلَّا اللّٰهُ', 'lt' => 'La ilaha illallah', 'mn' => 'Tiada tuhan selain Allah'],
        ['ar' => 'أَسْتَغْفِرُ اللّٰهَ', 'lt' => 'Astaghfirullah', 'mn' => 'Aku memohon ampun kepada Allah'],
        ['ar' => 'اَللّٰهُمَّ صَلِّ عَلٰى مُحَمَّدٍ', 'lt' => "Allahumma shalli 'ala Muhammad", 'mn' => 'Ya Allah, limpahkan selawat kepada Muhammad'],
    ];
@endphp
<x-layouts.app title="Tasbih Digital" description="Penghitung dzikir (tasbih digital) dengan target 33, 99, 100, atau bebas.">
    <x-page-head title="Tasbih Digital" subtitle="Ketuk lingkaran untuk menghitung dzikir" />

    <div class="grid-2">
        <section class="card card--pad-lg tasbih" aria-label="Penghitung">
            <div>
                <div class="tasbih__dzikir" lang="ar" data-dz-ar>{{ $dzikir[0]['ar'] }}</div>
                <div style="font-weight:700" data-dz-lt>{{ $dzikir[0]['lt'] }}</div>
                <div class="small muted" data-dz-mn>{{ $dzikir[0]['mn'] }}</div>
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
                        <button type="button" class="chip" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}" data-dz="{{ $i }}" data-ar="{{ $d['ar'] }}" data-lt="{{ $d['lt'] }}" data-mn="{{ $d['mn'] }}">{{ $d['lt'] }}</button>
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
                <p class="small muted" style="margin:12px 0 0">Dzikir setelah sholat: Subhanallah 33×, Alhamdulillah 33×, Allahu Akbar 33× (HR. Muslim no. 597). Hitungan tersimpan di perangkat ini.</p>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var S = window.MA.store, KEY = 'ma-tasbih';
        var st = Object.assign({ count: 0, total: 0, rounds: 0, target: 33, dz: 0, vibrate: true }, S.get(KEY, {}));
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
                if (on) { q('[data-dz-ar]').textContent = b.dataset.ar; q('[data-dz-lt]').textContent = b.dataset.lt; q('[data-dz-mn]').textContent = b.dataset.mn; }
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
        qa('[data-dz]').forEach(function (b) { b.addEventListener('click', function () { st.dz = +b.dataset.dz; st.count = 0; render(); }); });
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
