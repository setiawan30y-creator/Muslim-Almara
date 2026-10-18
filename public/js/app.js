/*!
 * Muslim Almara — interaksi ringan tanpa framework.
 * @2026-Alifiandra
 */
(function () {
    'use strict';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var store = {
        get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
        del: function (k) { try { localStorage.removeItem(k); } catch (e) {} }
    };
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };

    // ---------- Toast ----------
    var toastTimer;
    function toast(msg) {
        var el = $('[data-toast]');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('is-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('is-show'); }, 2400);
    }
    window.MA = { toast: toast, store: store };

    // ---------- Confirm dialog ----------
    function confirmDialog(title, text) {
        var dlg = $('[data-confirm]');
        if (!dlg || typeof dlg.showModal !== 'function') return Promise.resolve(window.confirm(text));
        $('[data-confirm-title]', dlg).textContent = title;
        $('[data-confirm-text]', dlg).textContent = text;
        dlg.showModal();
        return new Promise(function (resolve) {
            var done = function (v) { dlg.close(); ok.removeEventListener('click', y); cancel.removeEventListener('click', n); resolve(v); };
            var ok = $('[data-confirm-ok]', dlg), cancel = $('[data-confirm-cancel]', dlg);
            var y = function () { done(true); }, n = function () { done(false); };
            ok.addEventListener('click', y);
            cancel.addEventListener('click', n);
            dlg.addEventListener('cancel', function c() { dlg.removeEventListener('cancel', c); resolve(false); });
        });
    }
    window.MA.confirm = confirmDialog;

    // ---------- Tema ----------
    $$('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var root = document.documentElement;
            var current = root.dataset.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            var next = current === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            try { localStorage.setItem('ma-theme', next); } catch (e) {}
            toast(next === 'dark' ? 'Tema gelap aktif' : 'Tema terang aktif');
        });
    });

    // ---------- Hitung mundur sholat ----------
    var countdowns = $$('[data-countdown]');
    if (countdowns.length) {
        var tick = function () {
            var now = Date.now() / 1000;
            countdowns.forEach(function (el) {
                var target = parseInt(el.getAttribute('data-countdown'), 10);
                var s = Math.max(0, Math.floor(target - now));
                if (s === 0 && !el.dataset.reloaded) { el.dataset.reloaded = '1'; setTimeout(function () { location.reload(); }, 60000); }
                var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
                el.textContent = (h > 0 ? h + ' jam ' : '') + pad(m) + ' mnt ' + pad(sec) + ' dtk';
            });
        };
        tick();
        setInterval(tick, 1000);
    }

    // ---------- Jam lokasi ----------
    $$('[data-clock]').forEach(function (el) {
        var offset = parseFloat(el.getAttribute('data-clock')); // jam dari UTC
        var upd = function () {
            var d = new Date(Date.now() + offset * 3600000);
            el.textContent = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes());
        };
        upd();
        setInterval(upd, 15000);
    });

    // ---------- Geolokasi ----------
    $$('[data-geolocate]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!('geolocation' in navigator)) { toast('Peramban tidak mendukung deteksi lokasi.'); return; }
            var label = btn.innerHTML;
            btn.setAttribute('aria-disabled', 'true');
            btn.innerHTML = 'Mendeteksi lokasi…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                var target = btn.getAttribute('data-geolocate') || location.pathname;
                var tz = -new Date().getTimezoneOffset() / 60;
                var url = target + (target.indexOf('?') > -1 ? '&' : '?') + 'lat=' + pos.coords.latitude.toFixed(4) + '&lng=' + pos.coords.longitude.toFixed(4) + '&tz=' + tz;
                location.href = url;
            }, function (err) {
                btn.removeAttribute('aria-disabled');
                btn.innerHTML = label;
                toast(err.code === 1 ? 'Izin lokasi ditolak. Pilih kota secara manual.' : 'Lokasi tidak dapat dideteksi. Coba lagi atau pilih kota.');
            }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 600000 });
        });
    });

    // ---------- Filter daftar (kota, surah, doa, asmaul husna) ----------
    $$('[data-filter-input]').forEach(function (input) {
        var scope = document.getElementById(input.getAttribute('data-filter-input'));
        if (!scope) return;
        var items = $$('[data-filter-item]', scope);
        var empty = $('[data-filter-empty]', scope.parentNode) || $('[data-filter-empty]');
        var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9 ]/g, ''); };
        var apply = function () {
            var q = norm(input.value).trim();
            var cat = scope.getAttribute('data-filter-cat') || '';
            var shown = 0;
            items.forEach(function (it) {
                var okText = !q || norm(it.getAttribute('data-search')).indexOf(q) > -1;
                var okCat = !cat || it.getAttribute('data-cat') === cat;
                it.hidden = !(okText && okCat);
                if (!it.hidden) shown++;
            });
            if (empty) empty.hidden = shown > 0;
        };
        input.addEventListener('input', apply);
        scope.addEventListener('filter:change', apply);
    });
    $$('[data-filter-chip]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var scope = document.getElementById(chip.getAttribute('data-filter-target'));
            $$('[data-filter-chip][data-filter-target="' + scope.id + '"]').forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
            chip.setAttribute('aria-pressed', 'true');
            scope.setAttribute('data-filter-cat', chip.getAttribute('data-filter-chip'));
            scope.dispatchEvent(new Event('filter:change'));
        });
    });

    // ---------- Salin teks ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var src = document.getElementById(btn.getAttribute('data-copy'));
        var text = src ? src.innerText.trim() : btn.getAttribute('data-copy-text');
        if (!text) return;
        var done = function () { toast('Disalin ke papan klip'); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
        } else { fallbackCopy(text); done(); }
    });
    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }

    // ---------- Bagikan ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-share]');
        if (!btn) return;
        var data = { title: btn.getAttribute('data-share-title') || document.title, text: btn.getAttribute('data-share-text') || '', url: btn.getAttribute('data-share') || location.href };
        if (navigator.share) { navigator.share(data).catch(function () {}); }
        else { fallbackCopy(data.text + (data.text ? '\n' : '') + data.url); toast('Tautan disalin'); }
    });

    // ---------- Pembaca Al-Qur'an ----------
    var reader = $('[data-reader]');
    if (reader) {
        var surahNo = parseInt(reader.getAttribute('data-surah'), 10);
        var surahName = reader.getAttribute('data-surah-name');
        var markCurrent = function () {
            var last = store.get('ma-last-read', null);
            $$('.ayah.is-marked', reader).forEach(function (a) { a.classList.remove('is-marked'); });
            if (last && last.surah === surahNo) {
                var el = document.getElementById('ayat-' + last.ayat);
                if (el) el.classList.add('is-marked');
            }
        };
        markCurrent();
        reader.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mark]');
            if (!b) return;
            var ayat = parseInt(b.getAttribute('data-mark'), 10);
            store.set('ma-last-read', { surah: surahNo, nama: surahName, ayat: ayat, at: Date.now() });
            markCurrent();
            toast('Ditandai: ' + surahName + ' ayat ' + ayat);
        });

        // Ukuran huruf Arab
        var sizes = [1.5, 1.7, 1.9, 2.2, 2.5, 2.9];
        var currentSize = function () { var v = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--arabic-size')); return isNaN(v) ? 1.9 : v; };
        $$('[data-font]').forEach(function (b) {
            b.addEventListener('click', function () {
                var cur = currentSize(), dir = b.getAttribute('data-font') === 'up' ? 1 : -1;
                var idx = sizes.reduce(function (best, s, i) { return Math.abs(s - cur) < Math.abs(sizes[best] - cur) ? i : best; }, 0);
                var next = sizes[Math.max(0, Math.min(sizes.length - 1, idx + dir))];
                document.documentElement.style.setProperty('--arabic-size', next + 'rem');
                try { localStorage.setItem('ma-arabic-size', next); } catch (e) {}
            });
        });

        // Tampilkan / sembunyikan terjemahan
        var trBtn = $('[data-toggle-tr]');
        var applyTr = function (hide) { document.body.classList.toggle('hide-tr', hide); if (trBtn) trBtn.setAttribute('aria-pressed', hide ? 'false' : 'true'); };
        applyTr(store.get('ma-hide-tr', false));
        if (trBtn) trBtn.addEventListener('click', function () { var hide = !document.body.classList.contains('hide-tr'); store.set('ma-hide-tr', hide); applyTr(hide); });

        // Lompat ke ayat
        var jump = $('[data-jump]');
        if (jump) jump.addEventListener('change', function () { var el = document.getElementById('ayat-' + jump.value); if (el) el.scrollIntoView({ behavior: 'smooth' }); });
    }

    // ---------- Lanjutkan membaca (beranda & daftar surah) ----------
    $$('[data-last-read]').forEach(function (box) {
        var last = store.get('ma-last-read', null);
        if (!last || !last.surah) return;
        var link = $('a', box);
        link.href = box.getAttribute('data-last-read').replace('__N__', last.surah) + '#ayat-' + last.ayat;
        $('[data-last-read-text]', box).textContent = 'QS. ' + last.nama + ' · ayat ' + last.ayat;
        box.hidden = false;
    });

    // =====================================================================
    // PWA — service worker, pemasangan, status jaringan, simpan offline
    // =====================================================================
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var swSupported = 'serviceWorker' in navigator;
    var secure = window.isSecureContext;

    if (swSupported && secure) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function (reg) {
                // Versi baru tersedia: aktifkan lalu muat ulang sekali.
                reg.addEventListener('updatefound', function () {
                    var nw = reg.installing;
                    if (!nw) return;
                    nw.addEventListener('statechange', function () {
                        if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                            toast('Versi baru tersedia, memperbarui…');
                            nw.postMessage('SKIP_WAITING');
                        }
                    });
                });
            }).catch(function () {});
            var refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', function () {
                if (refreshing || !navigator.serviceWorker.controller) return;
                refreshing = true;
                location.reload();
            });
        });
    }

    // Tombol & kartu "Pasang"
    var deferredPrompt = null;
    var installBtns = $$('[data-install], [data-install-action]');
    var installCard = $('[data-install-card]');
    var dismissed = store.get('ma-install-dismissed', 0);
    var cardAllowed = !isStandalone && (Date.now() - dismissed > 14 * 86400000);

    function showInstall(show) {
        installBtns.forEach(function (b) { b.hidden = !show; });
        if (installCard) installCard.hidden = !(show && cardAllowed);
    }
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        showInstall(true);
        updatePwaState();
    });
    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        showInstall(false);
        toast('Aplikasi berhasil dipasang');
        updatePwaState();
    });
    installBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function () { deferredPrompt = null; showInstall(false); });
        });
    });
    var dismissBtn = $('[data-install-dismiss]');
    if (dismissBtn) dismissBtn.addEventListener('click', function () { store.set('ma-install-dismissed', Date.now()); if (installCard) installCard.hidden = true; });

    // iOS Safari tidak punya prompt otomatis: tampilkan petunjuk manual.
    if (isIOS && !isStandalone && installCard && cardAllowed) {
        var hint = $('[data-install-hint]', installCard);
        if (hint) hint.textContent = 'Ketuk tombol Bagikan di Safari, lalu pilih “Tambahkan ke Layar Utama”.';
        var act = $('[data-install-action]', installCard);
        if (act) act.remove();
        installCard.hidden = false;
    }

    function updatePwaState() {
        var el = $('[data-pwa-state]');
        if (!el) return;
        if (isStandalone) el.textContent = 'Aplikasi sudah terpasang dan berjalan dalam mode aplikasi.';
        else if (!secure) el.textContent = 'Pemasangan & mode offline membutuhkan HTTPS (atau localhost). Buka situs melalui alamat https agar bisa dipasang.';
        else if (deferredPrompt) el.textContent = 'Perangkat ini mendukung pemasangan. Pasang agar bisa dibuka dari layar utama dan tetap berjalan saat offline.';
        else if (isIOS) el.textContent = 'Di iPhone/iPad: ketuk Bagikan di Safari, lalu “Tambahkan ke Layar Utama”.';
        else el.textContent = 'Gunakan menu peramban (⋮) lalu pilih “Instal aplikasi” atau “Tambahkan ke layar utama”. Halaman yang pernah dibuka tersimpan untuk dipakai offline.';
    }
    updatePwaState();

    // Status jaringan
    var net = $('[data-net-status]');
    function netUpdate() { if (net) net.hidden = navigator.onLine; }
    window.addEventListener('online', function () { netUpdate(); toast('Kembali online'); });
    window.addEventListener('offline', netUpdate);
    netUpdate();

    // Simpan seluruh surah untuk dibaca offline
    var qo = $('[data-quran-offline]');
    if (qo && swSupported && secure && 'caches' in window) {
        qo.hidden = false;
        var urls = qo.getAttribute('data-urls').split(',');
        var btn = $('[data-qo-start]', qo), bar = $('[data-qo-bar]', qo), text = $('[data-qo-text]', qo);
        caches.open('ma-pages-v1').then(function (c) { return Promise.all(urls.map(function (u) { return c.match(u); })); }).then(function (hits) {
            var n = hits.filter(Boolean).length;
            if (n === urls.length) { text.textContent = 'Seluruh 114 surah sudah tersimpan di perangkat ini.'; btn.innerHTML = 'Perbarui'; }
            else if (n > 0) text.textContent = n + ' dari 114 surah sudah tersimpan. Simpan sisanya untuk dibaca tanpa internet.';
        });
        btn.addEventListener('click', function () {
            if (!navigator.onLine) { toast('Sambungkan ke internet terlebih dahulu.'); return; }
            btn.setAttribute('aria-disabled', 'true');
            bar.hidden = false;
            var done = 0, failed = 0, i = 0;
            caches.open('ma-pages-v1').then(function (cache) {
                function worker() {
                    if (i >= urls.length) return Promise.resolve();
                    var u = urls[i++];
                    return fetch(u, { credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw 0; return cache.put(u, r); })
                        .catch(function () { failed++; })
                        .then(function () {
                            done++;
                            $('span', bar).style.width = (done / urls.length * 100) + '%';
                            text.textContent = 'Menyimpan… ' + done + '/' + urls.length;
                            return worker();
                        });
                }
                return Promise.all([worker(), worker(), worker(), worker()]);
            }).then(function () {
                btn.removeAttribute('aria-disabled');
                text.textContent = failed ? (urls.length - failed) + ' surah tersimpan, ' + failed + ' gagal. Coba lagi.' : 'Selesai! Seluruh 114 surah bisa dibaca tanpa internet.';
                toast(failed ? 'Sebagian surah gagal disimpan' : 'Al-Qur\'an tersimpan untuk offline');
                if (!failed) btn.innerHTML = 'Perbarui';
            });
        });
    }
})();
