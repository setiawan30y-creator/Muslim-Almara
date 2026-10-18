{{-- Kalkulator Zakat — @2026-Alifiandra --}}
<x-layouts.app title="Kalkulator Zakat" description="Hitung zakat maal, zakat penghasilan, dan zakat fitrah berdasarkan nisab 85 gram emas.">
    <x-page-head title="Kalkulator Zakat" subtitle="Zakat maal, penghasilan, dan fitrah" />

    <div class="layout-side" data-zakat>
        <div class="stack">
            <div class="tabs" role="tablist" aria-label="Jenis zakat">
                <button type="button" role="tab" id="tab-maal" aria-controls="panel-maal" aria-selected="true">Maal</button>
                <button type="button" role="tab" id="tab-penghasilan" aria-controls="panel-penghasilan" aria-selected="false" tabindex="-1">Penghasilan</button>
                <button type="button" role="tab" id="tab-fitrah" aria-controls="panel-fitrah" aria-selected="false" tabindex="-1">Fitrah</button>
            </div>

            <div class="card card--pad-lg">
                <div class="field" style="margin-bottom:var(--space-4)" data-gold-field>
                    <label for="harga-emas">Harga emas per gram</label>
                    <div class="input-prefix"><span>Rp</span><input id="harga-emas" class="input" inputmode="numeric" data-money data-k="emas" placeholder="Masukkan harga emas hari ini" autocomplete="off"></div>
                    <span class="hint">Dipakai untuk menghitung nisab (85 gram emas). Gunakan harga emas murni terkini.</span>
                </div>

                <section id="panel-maal" role="tabpanel" aria-labelledby="tab-maal" class="form-grid form-grid--2">
                    <div class="field"><label for="m-kas">Tabungan, deposito &amp; uang tunai</label><div class="input-prefix"><span>Rp</span><input id="m-kas" class="input" inputmode="numeric" data-money data-k="kas" placeholder="0"></div></div>
                    <div class="field"><label for="m-emas">Nilai emas, perak &amp; logam mulia</label><div class="input-prefix"><span>Rp</span><input id="m-emas" class="input" inputmode="numeric" data-money data-k="logam" placeholder="0"></div></div>
                    <div class="field"><label for="m-inv">Saham, reksa dana &amp; investasi</label><div class="input-prefix"><span>Rp</span><input id="m-inv" class="input" inputmode="numeric" data-money data-k="investasi" placeholder="0"></div></div>
                    <div class="field"><label for="m-piutang">Piutang lancar &amp; harta dagang</label><div class="input-prefix"><span>Rp</span><input id="m-piutang" class="input" inputmode="numeric" data-money data-k="piutang" placeholder="0"></div></div>
                    <div class="field" style="grid-column:1/-1"><label for="m-utang">Utang jatuh tempo</label><div class="input-prefix"><span>Rp</span><input id="m-utang" class="input" inputmode="numeric" data-money data-k="utang" placeholder="0"></div><span class="hint">Dikurangkan dari total harta.</span></div>
                </section>

                <section id="panel-penghasilan" role="tabpanel" aria-labelledby="tab-penghasilan" class="form-grid form-grid--2" hidden>
                    <div class="field"><label for="p-gaji">Penghasilan per bulan</label><div class="input-prefix"><span>Rp</span><input id="p-gaji" class="input" inputmode="numeric" data-money data-k="gaji" placeholder="0"></div></div>
                    <div class="field"><label for="p-lain">Pendapatan lain per bulan</label><div class="input-prefix"><span>Rp</span><input id="p-lain" class="input" inputmode="numeric" data-money data-k="lain" placeholder="0"></div><span class="hint">Bonus, honor, dan sejenisnya.</span></div>
                </section>

                <section id="panel-fitrah" role="tabpanel" aria-labelledby="tab-fitrah" class="form-grid form-grid--2" hidden>
                    <div class="field"><label for="f-jiwa">Jumlah jiwa</label><input id="f-jiwa" class="input" inputmode="numeric" data-num data-k="jiwa" value="1"></div>
                    <div class="field"><label for="f-nilai">Nilai per jiwa (uang)</label><div class="input-prefix"><span>Rp</span><input id="f-nilai" class="input" inputmode="numeric" data-money data-k="nilai" placeholder="Sesuai ketetapan daerah"></div><span class="hint">Besaran uang mengikuti ketetapan BAZNAS/pemerintah daerah setempat.</span></div>
                </section>
            </div>
        </div>

        <aside class="stack">
            <section class="result" aria-live="polite">
                <div class="small" style="font-weight:700" data-r-title>Zakat maal yang wajib dibayar</div>
                <div class="result__big" data-r-main>Rp0</div>
                <div class="small" data-r-note>Masukkan harga emas dan nilai harta.</div>
            </section>
            <section class="card card--flat">
                <ul class="small muted" style="padding-left:1.1rem;margin:0" data-r-detail></ul>
            </section>
            <p class="small muted">Kalkulator ini alat bantu perkiraan. Untuk kasus khusus (harta usaha, pertanian, warisan, dsb.), konsultasikan dengan amil zakat resmi.</p>
        </aside>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('[data-zakat]');
        var S = window.MA.store, KEY = 'ma-zakat';
        var saved = S.get(KEY, {});
        var fmt = new Intl.NumberFormat('id-ID');
        var rp = function (n) { return 'Rp' + fmt.format(Math.round(n)); };
        var tab = saved.tab || 'maal';
        var num = function (k) { var el = root.querySelector('[data-k="' + k + '"]'); return el ? (parseInt(String(el.value).replace(/\D/g, ''), 10) || 0) : 0; };

        root.querySelectorAll('[data-money],[data-num]').forEach(function (el) {
            if (saved[el.dataset.k] !== undefined) el.value = el.hasAttribute('data-money') && saved[el.dataset.k] ? fmt.format(saved[el.dataset.k]) : saved[el.dataset.k];
            el.addEventListener('input', function () {
                var v = parseInt(el.value.replace(/\D/g, ''), 10);
                if (el.hasAttribute('data-money')) el.value = isNaN(v) ? '' : fmt.format(v);
                calc();
            });
        });

        var tabs = root.querySelectorAll('[role="tab"]');
        function setTab(name) {
            tab = name;
            tabs.forEach(function (t) {
                var on = t.id === 'tab-' + name;
                t.setAttribute('aria-selected', String(on)); t.tabIndex = on ? 0 : -1;
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
            root.querySelector('[data-gold-field]').hidden = name === 'fitrah';
            calc();
        }
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { setTab(t.id.replace('tab-', '')); });
            t.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                var n = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length]; n.focus(); n.click();
            });
        });

        function calc() {
            var gold = num('emas'), title, main = 0, note, detail = [];
            if (tab === 'maal') {
                var harta = num('kas') + num('logam') + num('investasi') + num('piutang') - num('utang');
                var nisab = 85 * gold;
                title = 'Zakat maal yang wajib dibayar';
                detail = ['Total harta bersih: ' + rp(Math.max(0, harta)), 'Nisab (85 g emas): ' + (gold ? rp(nisab) : '—'), 'Tarif: 2,5% bila sudah mencapai nisab dan dimiliki 1 tahun (haul).'];
                if (!gold) note = 'Masukkan harga emas untuk menghitung nisab.';
                else if (harta >= nisab) { main = harta * 0.025; note = 'Harta Anda telah mencapai nisab.'; }
                else note = 'Harta belum mencapai nisab, belum wajib zakat maal.';
            } else if (tab === 'penghasilan') {
                var bulan = num('gaji') + num('lain');
                var nisabB = 85 * gold / 12;
                title = 'Zakat penghasilan per bulan';
                detail = ['Penghasilan per bulan: ' + rp(bulan), 'Nisab per bulan (85 g emas ÷ 12): ' + (gold ? rp(nisabB) : '—'), 'Tarif: 2,5% dari penghasilan bila mencapai nisab.'];
                if (!gold) note = 'Masukkan harga emas untuk menghitung nisab.';
                else if (bulan >= nisabB) { main = bulan * 0.025; note = 'Setara ' + rp(main * 12) + ' per tahun.'; }
                else note = 'Penghasilan belum mencapai nisab. Sedekah tetap dianjurkan.';
            } else {
                var jiwa = Math.max(0, num('jiwa')), nilai = num('nilai');
                title = 'Zakat fitrah';
                main = jiwa * nilai;
                note = 'Atau dalam bentuk makanan pokok: ' + fmt.format(jiwa * 2.5) + ' kg beras (2,5 kg per jiwa).';
                detail = ['Jumlah jiwa: ' + jiwa, 'Nilai per jiwa: ' + (nilai ? rp(nilai) : '—'), 'Dibayarkan sebelum sholat Idulfitri.'];
                if (!nilai) note = fmt.format(jiwa * 2.5) + ' kg beras (2,5 kg per jiwa). Isi nilai per jiwa untuk versi uang.';
            }
            root.querySelector('[data-r-title]').textContent = title;
            root.querySelector('[data-r-main]').textContent = rp(main);
            root.querySelector('[data-r-note]').textContent = note;
            root.querySelector('[data-r-detail]').innerHTML = detail.map(function (d) { var li = document.createElement('li'); li.textContent = d; return li.outerHTML; }).join('');
            var data = { tab: tab };
            root.querySelectorAll('[data-k]').forEach(function (el) { data[el.dataset.k] = num(el.dataset.k); });
            S.set(KEY, data);
        }
        setTab(tab);
    });
    </script>
    @endpush
</x-layouts.app>
