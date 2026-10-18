/*!
 * ALMARA MUSLIM — interaksi ringan tanpa framework (progressive enhancement:
 * semua fitur inti tetap berjalan tanpa JavaScript lewat formulir biasa).
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

    // ---------- HTTP helper (CSRF untuk rute web, XSRF untuk API Sanctum) ----------
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    function xsrf() { var m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }
    function http(method, url, body) {
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': xsrf() };
        if (body !== undefined) headers['Content-Type'] = 'application/json';
        return fetch(url, { method: method, headers: headers, credentials: 'same-origin', body: body !== undefined ? JSON.stringify(body) : undefined })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (j) {
                    if (!r.ok) { var e = new Error(j.message || 'Permintaan gagal'); e.status = r.status; e.body = j; throw e; }
                    return j;
                });
            });
    }
    window.MA.http = http;
    function needLogin() {
        toast('Masuk untuk memakai fitur ini — data tersimpan privat di akun Anda.');
        setTimeout(function () { if (window.ALMARA && ALMARA.routes) location.href = ALMARA.routes.login; }, 1600);
    }
    function failed(e) { if (e && e.status === 401) return needLogin(); toast(e && e.status === 422 ? (e.message || 'Isian tidak valid') : 'Gagal menyimpan. Periksa koneksi Anda.'); }

    // ---------- Tampilan & tema (pratinjau langsung, simpan tanpa muat ulang) ----------
    var root = document.documentElement;
    function currentAppearance() {
        return {
            theme_mode: root.dataset.themeMode || 'system',
            accent_color: root.dataset.accent || 'hijau',
            interface_text_size: root.dataset.uiSize || 'normal',
            reading_text_size: root.dataset.readingSize || 'normal',
            translation_text_size: root.dataset.translationSize || 'normal'
        };
    }
    function applyAppearance(a) {
        root.dataset.themeMode = a.theme_mode;
        if (a.theme_mode === 'system') root.removeAttribute('data-theme'); else root.dataset.theme = a.theme_mode;
        root.dataset.accent = a.accent_color;
        root.dataset.uiSize = a.interface_text_size;
        root.dataset.readingSize = a.reading_text_size;
        root.dataset.translationSize = a.translation_text_size;
    }
    function saveAppearance(a, quiet) {
        if (!window.ALMARA) return Promise.resolve();
        return http('POST', ALMARA.routes.appearance, a).then(function (r) {
            if (!quiet) toast(r.meta && r.meta.stored === 'account' ? 'Tersimpan di akun Anda' : 'Tersimpan di perangkat ini');
        }).catch(failed);
    }
    window.MA.appearance = { current: currentAppearance, apply: applyAppearance, save: saveAppearance };

    $$('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var a = currentAppearance();
            var dark = root.dataset.theme ? root.dataset.theme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            a.theme_mode = dark ? 'light' : 'dark';
            applyAppearance(a);
            saveAppearance(a, true);
            toast(a.theme_mode === 'dark' ? 'Tema gelap aktif' : 'Tema terang aktif');
        });
    });

    var apForm = $('[data-appearance-form]');
    if (apForm) {
        var readForm = function () {
            var a = currentAppearance();
            ['theme_mode', 'accent_color', 'interface_text_size', 'reading_text_size', 'translation_text_size'].forEach(function (k) {
                var el = $('input[name="' + k + '"]:checked', apForm); if (el) a[k] = el.value;
            });
            return a;
        };
        apForm.addEventListener('change', function () { applyAppearance(readForm()); });
        apForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var a = readForm(); applyAppearance(a); saveAppearance(a);
            setTimeout(function () { $$('[data-loading]', apForm).forEach(function (b) { b.removeAttribute('aria-disabled'); if (b.dataset.label) b.innerHTML = b.dataset.label; }); }, 0);
        });
        var reset = $('[data-appearance-reset]', apForm);
        if (reset) reset.addEventListener('click', function () {
            var d = JSON.parse(apForm.getAttribute('data-defaults'));
            Object.keys(d).forEach(function (k) { var el = $('input[name="' + k + '"][value="' + d[k] + '"]', apForm); if (el) el.checked = true; });
            applyAppearance(d); saveAppearance(d);
        });
    }

    // ---------- Formulir: cegah kirim ganda & konfirmasi aksi destruktif ----------
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var msg = form.getAttribute('data-confirm-submit');
        if (msg && !form.dataset.confirmed) {
            e.preventDefault();
            confirmDialog('Konfirmasi', msg).then(function (ok) { if (ok) { form.dataset.confirmed = '1'; form.requestSubmit ? form.requestSubmit() : form.submit(); } });
            return;
        }
        if (e.defaultPrevented) return;
        $$('[data-loading]', form).forEach(function (b) { b.setAttribute('aria-disabled', 'true'); b.dataset.label = b.innerHTML; b.innerHTML = 'Memproses…'; });
    }, true);
    window.addEventListener('pageshow', function () { $$('[data-loading][aria-disabled]').forEach(function (b) { b.removeAttribute('aria-disabled'); if (b.dataset.label) b.innerHTML = b.dataset.label; }); });

    $$('[data-check-all]').forEach(function (all) {
        all.addEventListener('change', function () { $$('input[name="ids[]"]', all.closest('form')).forEach(function (c) { c.checked = all.checked; }); });
    });

    // ---------- Urutkan kartu beranda ----------
    var sortable = $('[data-sortable]');
    if (sortable) sortable.addEventListener('click', function (e) {
        var b = e.target.closest('[data-move]'); if (!b) return;
        var li = b.closest('li');
        if (b.dataset.move === 'up' && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
        if (b.dataset.move === 'down' && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
        b.focus();
    });

    // ---------- Tampilkan/sembunyikan Latin (doa & dzikir) ----------
    var latin = $('[data-toggle-latin]');
    if (latin) {
        var hideLatin = store.get('almara-hide-latin', false);
        latin.checked = !hideLatin; document.body.classList.toggle('hide-latin', hideLatin);
        latin.addEventListener('change', function () { document.body.classList.toggle('hide-latin', !latin.checked); store.set('almara-hide-latin', !latin.checked); });
    }

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
        var authed = !!(window.ALMARA && ALMARA.auth);
        var ayahOf = function (el) { var a = el.closest('.ayah'); return { el: a, id: +a.dataset.verseId, ayah: +a.dataset.ayah }; };

        var markLocal = function () {
            var last = store.get('almara-last-read', null);
            $$('.ayah.is-marked', reader).forEach(function (a) { a.classList.remove('is-marked'); });
            if (last && last.surah === surahNo) { var el = document.getElementById('ayat-' + last.ayat); if (el) el.classList.add('is-marked'); }
        };
        markLocal();

        reader.addEventListener('click', function (e) {
            var bm = e.target.closest('[data-bookmark]');
            if (bm) {
                if (!authed) return needLogin();
                var v = ayahOf(bm), on = bm.getAttribute('aria-pressed') === 'true';
                if (on) {
                    http('GET', '/api/v1/me/bookmarks?per_page=100').then(function (r) {
                        var hit = (r.data || []).filter(function (b) { return b.verse_id === v.id; })[0];
                        return hit ? http('DELETE', '/api/v1/me/bookmarks/' + hit.id) : null;
                    }).then(function () { bm.setAttribute('aria-pressed', 'false'); bm.classList.remove('is-on'); toast('Bookmark dihapus'); }).catch(failed);
                } else {
                    http('POST', '/api/v1/me/bookmarks', { verse_id: v.id }).then(function () {
                        bm.setAttribute('aria-pressed', 'true'); bm.classList.add('is-on'); toast('Ayat ' + v.ayah + ' disimpan ke bookmark');
                    }).catch(failed);
                }
                return;
            }
            var mr = e.target.closest('[data-mark-read]');
            if (mr) {
                var w = ayahOf(mr);
                var last = store.get('almara-last-read', null);
                store.set('almara-last-read', { surah: surahNo, nama: surahName, ayat: w.ayah, at: Date.now() });
                markLocal();
                if (!authed) { toast('Ditandai: ' + surahName + ' ayat ' + w.ayah + ' (di perangkat ini)'); return; }
                var from = last && last.surah === surahNo && last.ayat < w.ayah ? last.ayat + 1 : 1;
                http('PUT', '/api/v1/me/reading-progress', { verse_id: w.id, mark_read: true, from_ayah: from }).then(function (r) {
                    var s = r.data && r.data.stats;
                    toast(s && s.target ? 'Tercatat. Hari ini ' + s.today + '/' + s.target + ' ayat' : 'Tercatat dibaca sampai ayat ' + w.ayah);
                }).catch(failed);
                return;
            }
            var nt = e.target.closest('[data-note]');
            if (nt) {
                if (!authed) return needLogin();
                var x = ayahOf(nt), dlg = $('[data-note-dialog]');
                if (!dlg || typeof dlg.showModal !== 'function') return;
                $('[data-note-ref]', dlg).textContent = 'QS. ' + surahName + ': ' + x.ayah;
                $('[data-note-body]', dlg).value = '';
                dlg.dataset.verseId = x.id; dlg.showModal();
                $('[data-note-body]', dlg).focus();
            }
        });

        var noteDlg = $('[data-note-dialog]');
        if (noteDlg) {
            $('[data-note-cancel]', noteDlg).addEventListener('click', function () { noteDlg.close(); });
            $('[data-note-form]', noteDlg).addEventListener('submit', function (e) {
                e.preventDefault();
                var body = $('[data-note-body]', noteDlg).value.trim();
                if (!body) return;
                http('POST', '/api/v1/me/notes', { verse_id: +noteDlg.dataset.verseId, body: body }).then(function () { noteDlg.close(); toast('Catatan tersimpan (privat)'); }).catch(failed);
            });
        }

        // Ukuran huruf Arab → preferensi tampilan (tersimpan)
        var sizes = ['kecil', 'normal', 'besar', 'sangat-besar'];
        $$('[data-reading-size]').forEach(function (b) {
            b.addEventListener('click', function () {
                var a = currentAppearance(), i = sizes.indexOf(a.reading_text_size);
                i = Math.max(0, Math.min(sizes.length - 1, (i < 0 ? 1 : i) + (b.dataset.readingSize === 'up' ? 1 : -1)));
                a.reading_text_size = sizes[i]; applyAppearance(a); saveAppearance(a, true);
            });
        });

        // Terjemahan & tafsir → preferensi Al-Qur'an (tersimpan)
        $$('[data-pref-toggle]').forEach(function (b) {
            b.addEventListener('click', function () {
                var key = b.dataset.prefToggle, on = b.getAttribute('aria-pressed') !== 'true';
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                var sel = key === 'show_translation' ? '.ayah__tr' : '.tafsir';
                $$(sel, reader).forEach(function (el) { el.hidden = !on; });
                var payload = {}; payload[key] = on;
                if (window.ALMARA) http('POST', ALMARA.routes.quranPrefs, payload).catch(failed);
            });
        });

        var jump = $('[data-jump]');
        if (jump) jump.addEventListener('change', function () { var el = document.getElementById('ayat-' + jump.value); if (el) el.scrollIntoView({ behavior: 'smooth' }); });
    }

    // ---------- Hapus bookmark/catatan dari halaman profil ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-bookmark-delete], [data-note-delete]');
        if (!b) return;
        var isNote = b.hasAttribute('data-note-delete'), id = b.getAttribute(isNote ? 'data-note-delete' : 'data-bookmark-delete');
        confirmDialog('Hapus?', isNote ? 'Catatan ini akan dihapus permanen.' : 'Bookmark ini akan dihapus.').then(function (ok) {
            if (!ok) return;
            http('DELETE', '/api/v1/me/' + (isNote ? 'notes/' : 'bookmarks/') + id).then(function () {
                var row = b.closest('[data-note-row], [data-bookmark-row]'); if (row) row.remove(); toast('Dihapus');
            }).catch(failed);
        });
    });

    // ---------- Lanjutkan membaca (beranda & daftar surah) ----------
    $$('[data-last-read]').forEach(function (box) {
        var last = store.get('almara-last-read', null) || store.get('ma-last-read', null);
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
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).then(function (reg) {
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
            if (window.ALMARA && ALMARA.clearPrivateCache && navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage('CLEAR_PRIVATE');
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
    var dismissed = store.get('almara-install-dismissed', 0);
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
    if (dismissBtn) dismissBtn.addEventListener('click', function () { store.set('almara-install-dismissed', Date.now()); if (installCard) installCard.hidden = true; });

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
        caches.open('almara-pages-v2').then(function (c) { return Promise.all(urls.map(function (u) { return c.match(u); })); }).then(function (hits) {
            var n = hits.filter(Boolean).length;
            if (n === urls.length) { text.textContent = 'Seluruh 114 surah sudah tersimpan di perangkat ini.'; btn.innerHTML = 'Perbarui'; }
            else if (n > 0) text.textContent = n + ' dari 114 surah sudah tersimpan. Simpan sisanya untuk dibaca tanpa internet.';
        });
        var clearBtn = $('[data-qo-clear]', qo);
        caches.open('almara-pages-v2').then(function (c) { return c.match(urls[0]); }).then(function (hit) { if (hit && clearBtn) clearBtn.hidden = false; });
        if (clearBtn) clearBtn.addEventListener('click', function () {
            confirmDialog('Hapus unduhan?', 'Surah yang tersimpan untuk offline akan dihapus dari perangkat ini.').then(function (ok) {
                if (!ok) return;
                caches.open('almara-pages-v2').then(function (c) { return Promise.all(urls.map(function (u) { return c.delete(u); })); })
                    .then(function () { clearBtn.hidden = true; text.textContent = 'Unduhan dihapus.'; btn.innerHTML = 'Simpan semua'; toast('Unduhan offline dihapus'); });
            });
        });
        btn.addEventListener('click', function () {
            if (!navigator.onLine) { toast('Sambungkan ke internet terlebih dahulu.'); return; }
            btn.setAttribute('aria-disabled', 'true');
            bar.hidden = false;
            var done = 0, failed = 0, i = 0;
            caches.open('almara-pages-v2').then(function (cache) {
                function worker() {
                    if (i >= urls.length) return Promise.resolve();
                    var u = urls[i++];
                    return fetch(u, { credentials: 'omit' }).then(function (r) { if (!r.ok) throw 0; return cache.put(u, r); })
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
                if (clearBtn) clearBtn.hidden = false;
            });
        });
    }
})();
