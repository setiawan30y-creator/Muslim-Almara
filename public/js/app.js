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

    // ---------- Ganti tampilan Mushaf / per ayat ----------
    $$('[data-reading-mode]').forEach(function (b) {
        b.addEventListener('click', function () {
            var mode = b.dataset.readingMode, go = function () { var u = new URL(location.href); u.searchParams.delete('tampilan'); location.href = u.pathname + u.search; };
            if (window.ALMARA) http('POST', ALMARA.routes.quranPrefs, { reading_mode: mode }).then(go).catch(go); else go();
        });
    });

    // ---------- Mode Mushaf: ketuk ayat untuk arti & aksi ----------
    var mushaf = $('[data-mushaf]'), md = $('[data-mushaf-detail]');
    if (mushaf && md) {
        var mdOpen = function (span) {
            $$('.mushaf__ayah.is-picked', mushaf).forEach(function (x) { x.classList.remove('is-picked'); });
            span.classList.add('is-picked');
            var n = span.dataset.ayah, rd = $('[data-reader]');
            $('[data-md-ref]', md).textContent = 'QS. ' + rd.dataset.surahName + ': ' + n;
            $('[data-md-tr]', md).textContent = span.dataset.tr || '';
            var play = $('[data-md-play]', md); if (play) play.dataset.playAyah = n;
            $('[data-md-verse]', md).href = location.pathname + '?tampilan=ayat#ayat-' + n;
            md.dataset.ayah = n;
            md.hidden = false;
        };
        mushaf.addEventListener('click', function (e) { var sp = e.target.closest('.mushaf__ayah'); if (sp) mdOpen(sp); });
        mushaf.addEventListener('keydown', function (e) { var sp = e.target.closest('.mushaf__ayah'); if (sp && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); mdOpen(sp); } });
        $('[data-md-close]', md).addEventListener('click', function () { md.hidden = true; $$('.mushaf__ayah.is-picked', mushaf).forEach(function (x) { x.classList.remove('is-picked'); }); });
        $('[data-md-copy]', md).addEventListener('click', function () {
            var sp = document.getElementById('ayat-' + md.dataset.ayah);
            var txt = sp.textContent.trim() + '\n' + (sp.dataset.tr || '') + ' (' + $('[data-md-ref]', md).textContent + ')';
            if (navigator.clipboard) navigator.clipboard.writeText(txt).then(function () { toast('Disalin'); });
        });
    }

    // ---------- Pemutar murattal (Qari ALMARA / Islamic Network / Quran.Foundation) ----------
    var player = $('[data-audio]');
    if (player) (function () {
        var audio = new Audio();
        audio.preload = 'none';
        var btn = $('[data-audio-play]', player), sel = $('[data-audio-reciter]', player);
        var seek = $('[data-audio-seek]', player), cur = $('[data-audio-cur]', player), dur = $('[data-audio-dur]', player);
        var note = $('[data-audio-note]', player), ayahLbl = $('[data-audio-ayah]', player);
        var modeBtn = $('[data-audio-mode]', player), repBtn = $('[data-audio-repeat]', player);
        window.MA.isBusy = function () { return !audio.paused; };
        var st = { data: null, key: null, loading: null, verseMode: player.dataset.mode === 'verse', repeat: false, range: null, active: null, seeking: false };

        function fmt(s) { s = Math.max(0, Math.floor(s || 0)); var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60); return (h ? h + ':' + pad(m) : m) + ':' + pad(s % 60); }
        function setNote(t) { note.textContent = t || ''; note.hidden = !t; }
        function busy(on) { player.classList.toggle('is-loading', on); btn.setAttribute('aria-busy', on ? 'true' : 'false'); }
        function segs() { return (st.data && st.data.segments) || []; }
        function segOf(ayah) { var s = segs(); for (var i = 0; i < s.length; i++) if (s[i].ayah === ayah) return s[i]; return null; }
        // Mode audio per ayat (berkas terpisah tiap ayat): diputar berurutan.
        function tracks() { return (st.data && st.data.tracks) || null; }
        var preloader = new Audio(); preloader.preload = 'auto';
        function setTrack(i) {
            var t = tracks(); if (!t || !t[i]) return false;
            st.ti = i; st.triedAlt = false;
            audio.src = t[i].url;
            highlight(t[i].ayah);
            if (t[i + 1]) preloader.src = t[i + 1].url;
            return true;
        }

        function load() {
            var key = sel ? sel.value : null;
            if (st.data && st.key === key) return Promise.resolve(st.data);
            if (st.loading) return st.loading;
            busy(true);
            st.loading = fetch(player.dataset.api + (key ? '?reciter=' + encodeURIComponent(key) : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Audio belum tersedia.'); return j.data; }); })
                .then(function (d) {
                    st.data = d; st.key = key;
                    if (d.tracks && d.tracks.length) { st.ti = 0; audio.src = d.tracks[0].url; } else audio.src = d.url;
                    var info = [];
                    if (d.simulated) info.push('Mode simulasi: nada uji, bukan bacaan.');
                    if (d.attribution) info.push(d.attribution + '.');
                    if (st.verseMode && !(d.segments || []).length && !d.tracks) info.push('Penanda ayat belum tersedia untuk qari ini — diputar satu surah.');
                    setNote(info.join(' '));
                    if (d.duration_ms) { dur.textContent = fmt(d.duration_ms / 1000); seek.max = d.duration_ms / 1000; }
                    if ('mediaSession' in navigator) {
                        try { navigator.mediaSession.metadata = new MediaMetadata({ title: 'QS. ' + player.dataset.surahName, artist: d.reciter.name, album: document.title }); } catch (e) {}
                    }
                    return d;
                })
                .finally(function () { st.loading = null; busy(false); });
            return st.loading;
        }

        function highlight(ayah) {
            if (st.active === ayah) return;
            if (st.active) { var old = document.getElementById('ayat-' + st.active); if (old) old.classList.remove('is-playing'); }
            st.active = ayah;
            ayahLbl.textContent = ayah ? '· ayat ' + ayah : '';
            if (!ayah) return;
            var el = document.getElementById('ayat-' + ayah);
            if (!el) return;
            el.classList.add('is-playing');
            var r = el.getBoundingClientRect();
            if (!audio.paused && (r.top < 80 || r.top > window.innerHeight * 0.6)) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Geser posisi setelah metadata siap (sebelum itu peramban mengabaikan currentTime).
        function seekTo(t) {
            if (audio.readyState >= 1) { audio.currentTime = t; return Promise.resolve(); }
            return new Promise(function (res) {
                audio.addEventListener('loadedmetadata', function h() { audio.removeEventListener('loadedmetadata', h); audio.currentTime = t; res(); });
                audio.load();
            });
        }

        function playFrom(ayah) {
            return load().then(function () {
                var t = tracks();
                if (t) {
                    var idx = 0;
                    if (ayah) for (var k = 0; k < t.length; k++) if (t[k].ayah === ayah) { idx = k; break; }
                    setTrack(idx);
                    return audio.play();
                }
                var s = ayah ? segOf(ayah) : null;
                st.range = null;
                if (ayah && !s) toast('Penanda ayat belum tersedia — diputar dari awal surah');
                if (s && st.verseMode) st.range = { start: s.start_ms / 1000, end: s.end_ms / 1000 };
                return (s ? seekTo(s.start_ms / 1000) : ayah ? seekTo(0) : Promise.resolve()).then(function () { return audio.play(); });
            }).catch(function (e) { if (e && e.name !== 'AbortError') { toast(e.message || 'Audio gagal diputar'); setNote(e.message); } });
        }

        btn.addEventListener('click', function () {
            if (!audio.paused) { audio.pause(); return; }
            if (st.data && (audio.currentTime > 0 || tracks())) { if (tracks() && st.active === null) setTrack(st.ti || 0); audio.play().catch(function () {}); return; }
            playFrom(null);
        });
        document.addEventListener('click', function (e) { var b = e.target.closest('[data-play-ayah]'); if (b && b.dataset.playAyah) playFrom(+b.dataset.playAyah); });

        audio.addEventListener('play', function () { player.classList.add('is-playing'); btn.setAttribute('aria-label', 'Jeda murattal'); });
        audio.addEventListener('pause', function () { player.classList.remove('is-playing'); btn.setAttribute('aria-label', 'Putar murattal'); });
        audio.addEventListener('loadedmetadata', function () { if (isFinite(audio.duration)) { seek.max = audio.duration; dur.textContent = fmt(audio.duration); } });
        audio.addEventListener('waiting', function () { busy(true); });
        audio.addEventListener('playing', function () { busy(false); });
        audio.addEventListener('error', function () {
            busy(false);
            var t = tracks();
            if (t && t[st.ti] && t[st.ti].alt && !st.triedAlt) { st.triedAlt = true; audio.src = t[st.ti].alt; audio.play().catch(function () {}); return; }
            if (st.data) setNote('Audio tidak dapat dimuat. Periksa koneksi lalu coba lagi.');
        });
        audio.addEventListener('timeupdate', function () {
            var t = audio.currentTime;
            if (!st.seeking) seek.value = t;
            cur.textContent = fmt(t);
            if (tracks()) return;
            var ms = t * 1000, s = segs(), found = null;
            for (var i = 0; i < s.length; i++) { if (ms >= s[i].start_ms && ms < s[i].end_ms) { found = s[i].ayah; break; } }
            if (s.length) highlight(found);
            if (st.range && t >= st.range.end) {
                if (st.repeat) audio.currentTime = st.range.start;
                else { audio.pause(); st.range = null; }
            }
        });
        audio.addEventListener('ended', function () {
            var tl = tracks();
            if (tl) {
                if (st.verseMode) { if (st.repeat) { audio.currentTime = 0; audio.play().catch(function () {}); } return; }
                if (setTrack(st.ti + 1)) { audio.play().catch(function () {}); return; }
                if (st.repeat) { setTrack(0); audio.play().catch(function () {}); return; }
                highlight(null); st.ti = 0;
                var nx = $('.pager .btn--tonal');
                if (nx) setNote('Selesai. Lanjut ke surah berikutnya: ' + nx.textContent.trim() + '.');
                return;
            }
            if (st.repeat) { audio.currentTime = st.range ? st.range.start : 0; audio.play().catch(function () {}); return; }
            highlight(null);
            var next = $('.pager .btn--tonal');
            if (next) setNote('Selesai. Lanjut ke surah berikutnya: ' + next.textContent.trim() + '.');
        });

        seek.addEventListener('input', function () { st.seeking = true; cur.textContent = fmt(+seek.value); });
        seek.addEventListener('change', function () {
            st.seeking = false;
            var go = function () { st.range = null; return seekTo(+seek.value); };
            if (!st.data) load().then(go).catch(function (e) { toast(e.message); }); else go();
        });

        if (sel) sel.addEventListener('change', function () {
            var wasPlaying = !audio.paused, at = st.active;
            audio.pause(); audio.removeAttribute('src'); audio.load();
            st.data = null; st.range = null; seek.value = 0; seek.max = 0; cur.textContent = '0:00'; dur.textContent = '--:--'; setNote(''); highlight(null);
            if (window.ALMARA) http('POST', ALMARA.routes.quranPrefs, { reciter: sel.value }).catch(function () {});
            if (wasPlaying) playFrom(at || null);
        });
        modeBtn.addEventListener('click', function () {
            st.verseMode = !st.verseMode; st.range = null;
            modeBtn.setAttribute('aria-pressed', st.verseMode ? 'true' : 'false');
            toast(st.verseMode ? 'Mode per ayat: berhenti di akhir ayat' : 'Mode satu surah penuh');
            if (window.ALMARA) http('POST', ALMARA.routes.quranPrefs, { audio_mode: st.verseMode ? 'verse' : 'surah' }).catch(function () {});
        });
        repBtn.addEventListener('click', function () {
            st.repeat = !st.repeat;
            repBtn.setAttribute('aria-pressed', st.repeat ? 'true' : 'false');
            toast(st.repeat ? (st.verseMode ? 'Ulang ayat aktif' : 'Ulang surah aktif') : 'Ulang nonaktif');
        });

        if ('mediaSession' in navigator) {
            try {
                navigator.mediaSession.setActionHandler('play', function () { audio.play(); });
                navigator.mediaSession.setActionHandler('pause', function () { audio.pause(); });
                navigator.mediaSession.setActionHandler('seekbackward', function () { audio.currentTime = Math.max(0, audio.currentTime - 10); });
                navigator.mediaSession.setActionHandler('seekforward', function () { audio.currentTime = audio.currentTime + 10; });
                navigator.mediaSession.setActionHandler('nexttrack', function () { if (tracks() && setTrack(st.ti + 1)) audio.play(); });
                navigator.mediaSession.setActionHandler('previoustrack', function () { if (tracks() && setTrack(Math.max(0, st.ti - 1))) audio.play(); });
            } catch (e) {}
        }
        // Spasi = putar/jeda (kecuali saat mengetik)
        document.addEventListener('keydown', function (e) {
            if (e.code !== 'Space' || /input|textarea|select|button/i.test((e.target.tagName || '')) || e.target.isContentEditable) return;
            e.preventDefault(); btn.click();
        });
    })();

    // ---------- Audio doa & dzikir dari ayat Al-Qur'an (qari per ayat) ----------
    if ($('[data-ayat-audio]')) (function () {
        var au = new Audio(), cur = null, list = [], idx = 0;
        function stop() { au.pause(); if (cur) cur.classList.remove('is-playing', 'is-loading'); cur = null; list = []; }
        function next() { if (idx >= list.length) { stop(); return; } au.src = list[idx].url; au.play().catch(function () { stop(); }); }
        au.addEventListener('ended', function () { idx++; next(); });
        au.addEventListener('error', function () {
            var t = list[idx];
            if (t && t.alt && au.src !== t.alt) { au.src = t.alt; au.play().catch(stop); return; }
            toast('Audio tidak dapat dimuat'); stop();
        });
        au.addEventListener('playing', function () { if (cur) cur.classList.remove('is-loading'); });
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-ayat-audio]');
            if (!b) return;
            if (cur === b) { stop(); return; }
            stop(); cur = b; b.classList.add('is-playing', 'is-loading');
            fetch('/api/v1/quran/audio/ayat?ref=' + encodeURIComponent(b.dataset.ayatAudio), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Audio belum tersedia'); return j.data; }); })
                .then(function (d) {
                    if (cur !== b) return;
                    list = d.tracks; idx = 0; next();
                    var note = $('[data-ayat-audio-note="' + b.dataset.ayatAudio + '"]', b.closest('article, section') || document);
                    if (note) note.lastChild.textContent = ' Audio: ayat lengkap QS ' + d.ref + ' · ' + d.reciter.name;
                })
                .catch(function (err) { toast(err.message); stop(); });
        });
    })();

    // ---------- Admin: isi durasi rekaman otomatis dari metadata berkas ----------
    $$('[data-audio-file]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var f = inp.files && inp.files[0], out = $('[data-audio-duration]', inp.form);
            if (!f || !out) return;
            var url = URL.createObjectURL(f), a = new Audio();
            a.preload = 'metadata';
            a.onloadedmetadata = function () { if (isFinite(a.duration) && a.duration > 0) out.value = Math.max(1, Math.round(a.duration)); URL.revokeObjectURL(url); };
            a.onerror = function () { URL.revokeObjectURL(url); toast('Durasi tidak terbaca — isi manual'); };
            a.src = url;
        });
    });

    // ---------- Latihan (kuis panduan) ----------
    $$('[data-quiz]').forEach(function (box) {
        var qs = $$('[data-quiz-q]', box), score = $('[data-quiz-score]', box), right = 0, done = 0;
        qs.forEach(function (q) {
            var answer = +q.dataset.answer, fb = $('[data-quiz-fb]', q);
            $$('[data-opt]', q).forEach(function (b) {
                b.addEventListener('click', function () {
                    if (q.dataset.done) return;
                    q.dataset.done = '1'; done++;
                    var ok = +b.dataset.opt === answer;
                    if (ok) right++;
                    b.classList.add(ok ? 'is-correct' : 'is-wrong');
                    $$('[data-opt]', q).forEach(function (o) { o.setAttribute('aria-disabled', 'true'); if (+o.dataset.opt === answer) o.classList.add('is-correct'); });
                    fb.textContent = ok ? 'Benar.' : 'Belum tepat — jawaban yang benar ditandai hijau.';
                    score.textContent = right + '/' + qs.length + ' benar' + (done === qs.length ? (right === qs.length ? ' · masya Allah!' : ' · ulangi materi lalu coba lagi') : '');
                });
            });
        });
    });

    // ---------- Hafalan: mulai latihan ----------
    var hStart = $('[data-hafalan-start]');
    if (hStart) {
        var hs = $('[data-hafalan-surah]', hStart), hf = $('[data-hafalan-from]', hStart), ht = $('[data-hafalan-to]', hStart);
        var syncMax = function () { var max = +hs.selectedOptions[0].dataset.ayat; hf.max = ht.max = max; ht.placeholder = 'akhir (' + max + ')'; };
        hs.addEventListener('change', syncMax); syncMax();
        hStart.addEventListener('submit', function (e) {
            e.preventDefault();
            var q = [], f = +hf.value || 1, t = +ht.value || 0;
            if (f > 1) q.push('dari=' + f);
            if (t) q.push('sampai=' + Math.max(f, t));
            location.href = '/hafalan/' + hs.value + (q.length ? '?' + q.join('&') : '');
        });
    }

    // ---------- Hafalan: latihan sembunyikan teks & penilaian ----------
    var mem = $('[data-hafalan]');
    if (mem) {
        var setMode = function (m) { mem.classList.remove('mode-hide', 'mode-hint', 'mode-show'); mem.classList.add('mode-' + m); store.set('almara-mem-mode', m); };
        var savedMode = store.get('almara-mem-mode', 'hide');
        $$('[data-mem-mode]', mem).forEach(function (r) {
            r.checked = r.value === savedMode;
            r.addEventListener('change', function () { setMode(r.value); $$('[data-mem-reveal]', mem).forEach(function (b) { b.setAttribute('aria-pressed', 'false'); }); });
        });
        setMode(savedMode);
        var trBtn = $('[data-mem-tr]', mem);
        trBtn.addEventListener('click', function () { var on = trBtn.getAttribute('aria-pressed') !== 'true'; trBtn.setAttribute('aria-pressed', on ? 'true' : 'false'); mem.classList.toggle('hide-tr', !on); });
        $$('[data-mem-reveal]', mem).forEach(function (b) { b.addEventListener('click', function () { b.setAttribute('aria-pressed', b.getAttribute('aria-pressed') === 'true' ? 'false' : 'true'); }); });
        var memLabels = { memorized: 'Hafal', learning: 'Dihafal' };
        $$('[data-grade]', mem).forEach(function (b) {
            b.addEventListener('click', function () {
                var card = b.closest('[data-verse-id]');
                $$('[data-grade]', card).forEach(function (x) { x.classList.remove('is-picked'); });
                b.classList.add('is-picked');
                $('[data-mem-reveal]', card).setAttribute('aria-pressed', 'true');
                if (mem.hasAttribute('data-guest')) { card.classList.add('is-done'); toast('Masuk untuk menyimpan progres hafalan'); return; }
                http('POST', '/api/v1/me/memorization', { verse_id: +card.dataset.verseId, grade: b.dataset.grade }).then(function (r) {
                    var d = r.data, badge = $('[data-mem-status]', card);
                    badge.textContent = memLabels[d.status] || 'Dihafal';
                    badge.className = 'badge ' + (d.status === 'memorized' ? 'badge--success' : 'badge--neutral');
                    $('[data-mem-next]', card).textContent = 'Ulang: ' + d.next_review_label;
                    card.classList.add('is-done');
                    var nextCard = card.nextElementSibling;
                    if (nextCard && nextCard.scrollIntoView) nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }).catch(failed);
            });
        });
    }

    // ---------- Pelafalan (suara bawaan perangkat) ----------
    function speak(text, lang) {
        if (!('speechSynthesis' in window)) { toast('Perangkat tidak mendukung pelafalan'); return; }
        var u = new SpeechSynthesisUtterance(text);
        u.lang = lang || 'ar-SA'; u.rate = 0.85;
        var pre = (lang || 'ar').slice(0, 2).toLowerCase(), voices = speechSynthesis.getVoices();
        var v = voices.filter(function (x) { return x.lang && x.lang.replace('_', '-').toLowerCase() === (lang || '').toLowerCase(); })[0]
            || voices.filter(function (x) { return x.lang && x.lang.toLowerCase().indexOf(pre) === 0; })[0];
        if (v) u.voice = v; else if (voices.length) toast('Suara bahasa ini belum terpasang di perangkat — pasang di pengaturan Teks-ke-ucapan HP');
        speechSynthesis.cancel(); speechSynthesis.speak(u);
    }
    document.addEventListener('click', function (e) { var b = e.target.closest('[data-speak]'); if (b) speak(b.getAttribute('data-speak'), 'ar-SA'); });

    // ---------- Kumpulan frasa: pencarian ----------
    var pSearch = $('[data-phrase-search]');
    if (pSearch) {
        pSearch.addEventListener('input', function () {
            var q = pSearch.value.trim().toLowerCase(), any = false;
            $$('[data-phrase-group]').forEach(function (g) {
                var vis = 0;
                $$('[data-phrase]', g).forEach(function (c) { var ok = !q || c.dataset.phrase.indexOf(q) !== -1; c.hidden = !ok; if (ok) vis++; });
                g.hidden = vis === 0; if (vis) any = true;
            });
            $('[data-phrase-empty]').hidden = any;
        });
    }

    // ---------- Terjemah: tab, teks (mirip Google Translate), percakapan suara ----------
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    function trRequest(q, src, tgt) { return http('POST', '/api/v1/translate', { q: q, source: src, target: tgt }).then(function (r) { return r.data.text; }); }
    var trTabs = $('[data-tr-tabs]');
    if (trTabs) {
        var showTab = function (name) {
            $$('[data-tab]', trTabs).forEach(function (b) { b.setAttribute('aria-selected', b.dataset.tab === name ? 'true' : 'false'); });
            $$('[data-tab-panel]').forEach(function (p) { p.hidden = p.dataset.tabPanel !== name; });
        };
        $$('[data-tab]', trTabs).forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.tab); history.replaceState(null, '', '#' + b.dataset.tab); }); });
        var h = location.hash.slice(1);
        if (h && $('[data-tab="' + h + '"]', trTabs)) showTab(h);
    }

    var trf = $('[data-translate]');
    if (trf) (function () {
        var speech = JSON.parse(trf.dataset.speech || '{}');
        var src = $('[data-tr-src]', trf), tgt = $('[data-tr-tgt]', trf), q = $('[data-tr-q]', trf), out = $('[data-tr-text]', trf);
        var status = $('[data-tr-status]', trf), count = $('[data-tr-count]', trf), mic = $('[data-tr-mic]', trf);
        var timer = null, seq = 0, lastKey = '';
        function setLangAttrs() { q.lang = src.value; q.dir = /^(ar|ur)$/.test(src.value) ? 'rtl' : 'ltr'; }
        function run() {
            var text = q.value.trim(), key = src.value + '|' + tgt.value + '|' + text;
            count.textContent = q.value.length + '/' + q.maxLength;
            if (!text) { out.innerHTML = '<span class="muted">Terjemahan</span>'; out.removeAttribute('lang'); status.textContent = ''; lastKey = ''; return; }
            if (key === lastKey) return;
            if (src.value === tgt.value) { status.textContent = 'Pilih bahasa berbeda'; return; }
            var my = ++seq; status.textContent = 'Menerjemahkan…';
            trRequest(text, src.value, tgt.value).then(function (t) {
                if (my !== seq) return;
                lastKey = key; out.textContent = t; out.lang = tgt.value; status.textContent = '';
            }).catch(function (e) { if (my === seq) status.textContent = e.message || 'Gagal menerjemahkan'; });
        }
        function later() { clearTimeout(timer); timer = setTimeout(run, 700); count.textContent = q.value.length + '/' + q.maxLength; }
        q.addEventListener('input', later);
        q.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); clearTimeout(timer); run(); } });
        src.addEventListener('change', function () { setLangAttrs(); lastKey = ''; run(); });
        tgt.addEventListener('change', function () { lastKey = ''; run(); });
        $('[data-tr-swap]', trf).addEventListener('click', function () {
            var a = src.value; src.value = tgt.value; tgt.value = a;
            if (out.lang && out.textContent) q.value = out.textContent;
            setLangAttrs(); lastKey = ''; run();
        });
        $('[data-tr-clear]', trf).addEventListener('click', function () { q.value = ''; run(); q.focus(); });
        $('[data-tr-speak]', trf).addEventListener('click', function () { if (out.lang) speak(out.textContent, speech[out.lang] || out.lang); });
        $('[data-tr-speak-src]', trf).addEventListener('click', function () { if (q.value.trim()) speak(q.value, speech[src.value] || src.value); });
        $('[data-tr-copy]', trf).addEventListener('click', function () { if (out.lang && navigator.clipboard) navigator.clipboard.writeText(out.textContent).then(function () { toast('Disalin'); }); });
        setLangAttrs();
        // Masukan suara
        var rec = null;
        if (!SR) { mic.hidden = true; return; }
        mic.addEventListener('click', function () {
            if (rec) { rec.stop(); return; }
            rec = new SR(); rec.lang = speech[src.value] || src.value; rec.interimResults = true; rec.continuous = false;
            var base = q.value ? q.value.replace(/\s*$/, ' ') : '';
            rec.onresult = function (ev) {
                var txt = ''; for (var i = 0; i < ev.results.length; i++) txt += ev.results[i][0].transcript;
                q.value = base + txt; later();
            };
            rec.onerror = function (ev) { toast(ev.error === 'not-allowed' ? 'Izinkan akses mikrofon untuk memakai suara' : 'Suara tidak tertangkap, coba lagi'); };
            rec.onend = function () { mic.classList.remove('is-listening'); rec = null; clearTimeout(timer); run(); };
            mic.classList.add('is-listening'); rec.start();
        });
    })();

    var cv = $('[data-convo]');
    if (cv) (function () {
        var speech = JSON.parse(cv.dataset.speech || '{}');
        var log = $('[data-cv-log]', cv), auto = $('[data-cv-autospeak]', cv), rec = null;
        function lang(side) { return $('[data-cv-lang="' + side + '"]', cv).value; }
        function other(side) { return side === 'a' ? 'b' : 'a'; }
        function syncLabels() { ['a', 'b'].forEach(function (s) { var sel = $('[data-cv-lang="' + s + '"]', cv); $('[data-cv-label="' + s + '"]', cv).textContent = sel.options[sel.selectedIndex].text; }); }
        $$('[data-cv-lang]', cv).forEach(function (s) { s.addEventListener('change', syncLabels); });
        syncLabels();
        function bubble(side, text) {
            var empty = $('.convo__empty', log); if (empty) empty.remove();
            var li = document.createElement('li'); li.className = 'convo__msg convo__msg--' + side;
            var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'icon-btn'; btn.setAttribute('aria-label', 'Dengarkan'); btn.innerHTML = $('[data-tr-speak]') ? $('[data-tr-speak]').innerHTML : '▶';
            var o = document.createElement('p'); o.className = 'convo__orig'; o.lang = lang(side); o.textContent = text;
            var t = document.createElement('p'); t.className = 'convo__tr'; t.lang = lang(other(side)); t.textContent = '…';
            li.appendChild(btn); li.appendChild(o); li.appendChild(t); log.appendChild(li); log.scrollTop = log.scrollHeight;
            btn.addEventListener('click', function () { speak(t.textContent, speech[t.lang] || t.lang); });
            trRequest(text, lang(side), lang(other(side))).then(function (tr) {
                t.textContent = tr; log.scrollTop = log.scrollHeight;
                if (auto.checked) speak(tr, speech[t.lang] || t.lang);
            }).catch(function (e) { t.removeAttribute('lang'); t.className = 'convo__tr small muted'; t.textContent = e.message || 'Gagal menerjemahkan'; });
        }
        $$('[data-cv-form]', cv).forEach(function (f) {
            f.addEventListener('submit', function (e) { e.preventDefault(); var i = $('input', f), v = i.value.trim(); if (v) { bubble(f.dataset.cvForm, v); i.value = ''; } });
        });
        $('[data-cv-clear]', cv).addEventListener('click', function () { log.innerHTML = '<li class="convo__empty muted small">Percakapan dibersihkan.</li>'; });
        $$('[data-cv-mic]', cv).forEach(function (b) {
            if (!SR) { b.disabled = true; b.title = 'Peramban ini belum mendukung masukan suara — gunakan kolom ketik'; return; }
            b.addEventListener('click', function () {
                if (rec) { rec.stop(); return; }
                if ('speechSynthesis' in window) speechSynthesis.cancel();
                var side = b.dataset.cvMic;
                rec = new SR(); rec.lang = speech[lang(side)] || lang(side); rec.interimResults = false; rec.continuous = false;
                rec.onresult = function (ev) { var txt = ev.results[0][0].transcript.trim(); if (txt) bubble(side, txt); };
                rec.onerror = function (ev) { toast(ev.error === 'not-allowed' ? 'Izinkan akses mikrofon untuk memakai suara' : 'Suara tidak tertangkap, coba lagi'); };
                rec.onend = function () { b.classList.remove('is-listening'); rec = null; };
                b.classList.add('is-listening'); rec.start();
            });
        });
        if (!SR) $('[data-cv-note]', cv).textContent = 'Peramban ini belum mendukung masukan suara. Gunakan kolom ketik; terjemahan tetap dapat dibacakan. Untuk suara, pakai Chrome di Android/desktop.';
    })();

    // ---------- Masjid terdekat (OpenStreetMap / Overpass) ----------
    var mq = $('[data-mosques]');
    if (mq) (function () {
        var statusEl = $('[data-mosque-status]', mq), list = $('[data-mosque-list]', mq), whereEl = $('[data-mosque-where]', mq), radiusEl = $('[data-mosque-radius]', mq);
        var pos = { lat: +mq.dataset.lat, lng: +mq.dataset.lng }, map = null, layer = null, ctl = null;
        function dist(a, b) {
            var R = 6371000, r = Math.PI / 180, dLat = (b.lat - a.lat) * r, dLng = (b.lng - a.lng) * r;
            var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 2 * R * Math.asin(Math.sqrt(h));
        }
        function fmtD(m) { return m < 1000 ? Math.round(m / 10) * 10 + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km'; }
        function pin(label, me) { return window.L.divIcon({ className: '', html: '<span class="mosque-pin' + (me ? ' mosque-pin--me' : '') + '">' + label + '</span>', iconSize: [26, 26], iconAnchor: [13, 13] }); }
        function ensureMap() {
            if (map || !window.L) return;
            map = window.L.map($('[data-mosque-map]', mq), { scrollWheelZoom: false }).setView([pos.lat, pos.lng], 14);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
            layer = window.L.layerGroup().addTo(map);
        }
        function render(items) {
            ensureMap();
            list.innerHTML = '';
            if (layer) layer.clearLayers();
            if (map) window.L.marker([pos.lat, pos.lng], { icon: pin('•', true), title: 'Lokasi Anda' }).addTo(layer);
            items.forEach(function (m, i) {
                var li = document.createElement('li');
                var name = m.name || (m.kind === 'musala' ? 'Musala' : 'Masjid') + ' (tanpa nama)';
                li.innerHTML = '<span class="num"></span><span class="info"><b></b><span class="small muted"></span></span><a class="btn btn--sm btn--outline" target="_blank" rel="noopener">Rute</a>';
                $('.num', li).textContent = i + 1;
                $('b', li).textContent = name;
                $('.small', li).textContent = fmtD(m.d) + (m.addr ? ' · ' + m.addr : '');
                $('a', li).href = 'https://www.google.com/maps/dir/?api=1&destination=' + m.lat + ',' + m.lng;
                list.appendChild(li);
                if (map) {
                    var pop = document.createElement('span'); pop.textContent = name + ' · ' + fmtD(m.d);
                    window.L.marker([m.lat, m.lng], { icon: pin(i + 1), title: name }).bindPopup(pop).addTo(layer);
                }
            });
            list.hidden = !items.length;
            if (map) {
                var pts = [[pos.lat, pos.lng]].concat(items.slice(0, 10).map(function (m) { return [m.lat, m.lng]; }));
                if (pts.length > 1) map.fitBounds(pts, { padding: [24, 24], maxZoom: 16 }); else map.setView([pos.lat, pos.lng], 14);
            }
        }
        function load() {
            var r = +radiusEl.value;
            statusEl.textContent = 'Mencari masjid dalam radius ' + fmtD(r) + '…';
            if (ctl) ctl.abort();
            ctl = 'AbortController' in window ? new AbortController() : null;
            var around = '(around:' + r + ',' + pos.lat + ',' + pos.lng + ')';
            var q = '[out:json][timeout:25];(nwr["amenity"="place_of_worship"]["religion"="muslim"]' + around + ';);out center 80;';
            fetch(mq.dataset.overpass, { method: 'POST', body: 'data=' + encodeURIComponent(q), headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, signal: ctl ? ctl.signal : undefined })
                .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(function (j) {
                    var items = (j.elements || []).map(function (el) {
                        var lat = el.lat || (el.center && el.center.lat), lng = el.lon || (el.center && el.center.lon), t = el.tags || {};
                        if (!lat || !lng) return null;
                        var bt = (t.building || '') + ' ' + (t.name || '');
                        return { lat: lat, lng: lng, name: t['name:id'] || t.name || '', kind: /mush?alla|musala|langgar|surau/i.test(bt) ? 'musala' : 'masjid',
                            addr: [t['addr:street'], t['addr:housenumber']].filter(Boolean).join(' '), d: dist(pos, { lat: lat, lng: lng }) };
                    }).filter(Boolean).sort(function (a, b) { return a.d - b.d; }).slice(0, 50);
                    statusEl.textContent = items.length ? items.length + ' tempat ditemukan dalam radius ' + fmtD(r) + '.' : 'Belum ada masjid tercatat di OpenStreetMap dalam radius ini. Coba perbesar radius.';
                    render(items);
                })
                .catch(function (e) { if (e.name === 'AbortError') return; statusEl.textContent = 'Data peta tidak dapat dimuat (periksa koneksi). Coba lagi sebentar.'; render([]); });
        }
        $('[data-mosque-locate]', mq).addEventListener('click', function () {
            if (!navigator.geolocation) { toast('Perangkat tidak mendukung lokasi'); return; }
            statusEl.textContent = 'Meminta izin lokasi…';
            navigator.geolocation.getCurrentPosition(function (p) {
                pos = { lat: +p.coords.latitude.toFixed(5), lng: +p.coords.longitude.toFixed(5) };
                whereEl.textContent = 'Sekitar lokasi perangkat';
                load();
            }, function () { statusEl.textContent = 'Izin lokasi ditolak. Menampilkan sekitar ' + mq.dataset.name + '.'; load(); }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 300000 });
        });
        radiusEl.addEventListener('change', load);
        if (document.readyState === 'complete') load(); else window.addEventListener('load', load);
    })();

    // ---------- Kartu cuaca (Open-Meteo lewat server) ----------
    var wx = $('[data-weather]');
    if (wx) (function () {
        var tpl = $('[data-wx-icons]', wx);
        function icon(name) { var src = tpl && tpl.content.querySelector('[data-i="' + name + '"] svg'); return src ? src.cloneNode(true) : document.createElement('span'); }
        function li(iconName, text) { var el = document.createElement('li'); el.appendChild(icon(iconName)); el.appendChild(document.createTextNode(text)); return el; }
        function show(d) {
            var c = d.current, t = d.today;
            var ico = $('[data-wx-ico]', wx); ico.innerHTML = ''; ico.appendChild(icon(c.icon));
            $('[data-wx-temp]', wx).textContent = c.temp + '°';
            $('[data-wx-label]', wx).textContent = c.label + (c.feels_like !== null ? ' · terasa ' + c.feels_like + '°' : '');
            var meta = $('[data-wx-meta]', wx); meta.innerHTML = '';
            if (t.max !== null) meta.appendChild(li('sun', t.min + '° / ' + t.max + '°'));
            if (c.humidity !== null) meta.appendChild(li('droplet', c.humidity + '%'));
            if (c.wind !== null) meta.appendChild(li('wind', c.wind + ' km/j'));
            var hours = $('[data-wx-hours]', wx); hours.innerHTML = '';
            (d.hours || []).forEach(function (h) {
                var el = document.createElement('li'), tm = document.createElement('span'), b = document.createElement('b'), r = document.createElement('span');
                tm.textContent = h.time; b.textContent = (h.temp === null ? '-' : h.temp) + '°';
                r.className = 'rain'; r.textContent = h.rain >= 30 ? h.rain + '%' : '';
                el.title = h.label; el.appendChild(tm); el.appendChild(icon(h.icon)); el.appendChild(b); el.appendChild(r);
                hours.appendChild(el);
            });
            var tip = t.rain >= 60 ? 'Peluang hujan ' + t.rain + '% hari ini — siapkan payung bila ke masjid. ' : (t.uv >= 8 ? 'Indeks UV tinggi (' + t.uv + ') — lindungi diri saat siang. ' : '');
            $('[data-wx-note]', wx).textContent = tip + d.attribution + '.';
            wx.setAttribute('aria-busy', 'false');
        }
        function fail(msg) { $('[data-wx-label]', wx).textContent = msg; $('[data-wx-temp]', wx).textContent = '--°'; wx.setAttribute('aria-busy', 'false'); }
        var cached = store.get('almara-weather', null);
        if (cached && cached.data && cached.loc === wx.dataset.loc && Date.now() - cached.at < 30 * 60000) show(cached.data); else cached = null;
        fetch(wx.dataset.api, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Cuaca tidak tersedia'); return j.data; }); })
            .then(function (d) { store.set('almara-weather', { at: Date.now(), loc: wx.dataset.loc, data: d }); show(d); })
            .catch(function (e) { if (!cached) fail(navigator.onLine === false ? 'Offline — cuaca tidak dapat dimuat' : (e.message || 'Cuaca tidak tersedia')); });
    })();

    // ---------- Pengingat adzan (Web Push) ----------
    var pushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext;
    function b64uToBytes(s) { s = s.replace(/-/g, '+').replace(/_/g, '/'); s += '==='.slice((s.length + 3) % 4); var raw = atob(s), out = new Uint8Array(raw.length); for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i); return out; }
    function pushSave(sub, prefs) {
        var j = sub.toJSON();
        return http('POST', '/api/v1/push/subscriptions', { endpoint: j.endpoint, keys: j.keys, prayers: prefs.prayers, minutes_before: prefs.before })
            .then(function (r) { store.set('almara-push', { prayers: prefs.prayers, before: prefs.before, at: Date.now() }); return r; });
    }
    // Segarkan langganan berkala agar lokasi & metode terbaru ikut terpakai.
    if (pushSupported) {
        var pushPrefs = store.get('almara-push', null);
        if (pushPrefs && Date.now() - (pushPrefs.at || 0) > 12 * 3600000 && Notification.permission === 'granted') {
            navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); })
                .then(function (sub) { if (sub) return pushSave(sub, pushPrefs); store.del('almara-push'); }).catch(function () {});
        }
    }
    var pushBox = $('[data-push]');
    if (pushBox && pushBox.dataset.key) (function () {
        var state = $('[data-push-state]', pushBox), msg = $('[data-push-msg]', pushBox);
        var onBtn = $('[data-push-on]', pushBox), testBtn = $('[data-push-test]', pushBox), offBtn = $('[data-push-off]', pushBox), beforeEl = $('[data-push-before]', pushBox);
        function setState(active, text) {
            state.textContent = active ? 'Aktif' : 'Nonaktif';
            state.className = 'badge ' + (active ? 'badge--success' : 'badge--neutral');
            testBtn.hidden = offBtn.hidden = !active;
            if (text !== undefined) msg.textContent = text;
        }
        function prefs() {
            return { prayers: $$('[data-push-prayer]', pushBox).filter(function (c) { return c.checked; }).map(function (c) { return c.value; }), before: +beforeEl.value };
        }
        if (!pushSupported) {
            onBtn.disabled = true;
            setState(false, !window.isSecureContext ? 'Pengingat membutuhkan HTTPS.' : (/iphone|ipad|ipod/i.test(navigator.userAgent) && !(window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches) ? 'Di iPhone: pasang aplikasi ke Layar Utama dulu (Bagikan → Tambahkan ke Layar Utama), lalu buka dari ikonnya.' : 'Peramban ini belum mendukung notifikasi push.'));
            return;
        }
        var saved = store.get('almara-push', null);
        if (saved) {
            $$('[data-push-prayer]', pushBox).forEach(function (c) { c.checked = saved.prayers.indexOf(c.value) !== -1; });
            beforeEl.value = String(saved.before || 0);
        }
        navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (sub) {
            setState(!!sub && Notification.permission === 'granted', Notification.permission === 'denied' ? 'Izin notifikasi diblokir. Buka pengaturan situs di peramban untuk mengizinkan.' : '');
        });
        onBtn.addEventListener('click', function () {
            var p = prefs();
            if (!p.prayers.length) { toast('Pilih minimal satu waktu sholat'); return; }
            onBtn.disabled = true; msg.textContent = 'Meminta izin notifikasi…';
            Notification.requestPermission().then(function (perm) {
                if (perm !== 'granted') throw new Error('Izin notifikasi tidak diberikan.');
                return navigator.serviceWorker.ready;
            }).then(function (reg) {
                return reg.pushManager.getSubscription().then(function (sub) {
                    return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64uToBytes(pushBox.dataset.key) });
                });
            }).then(function (sub) { return pushSave(sub, p); })
              .then(function (r) { setState(true, 'Aktif untuk ' + (r.data.location || 'lokasi Anda') + '. Jadwal mengikuti pengaturan di atas.'); toast('Pengingat adzan aktif'); })
              .catch(function (e) { setState(false, e.message || 'Gagal mengaktifkan pengingat.'); })
              .finally(function () { onBtn.disabled = false; });
        });
        testBtn.addEventListener('click', function () {
            navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (sub) {
                if (!sub) throw new Error('Belum berlangganan.');
                return http('POST', '/api/v1/push/subscriptions/test', { endpoint: sub.endpoint });
            }).then(function () { toast('Notifikasi uji dikirim'); }).catch(function (e) { toast(e.message || 'Gagal mengirim uji'); });
        });
        offBtn.addEventListener('click', function () {
            navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (sub) {
                if (!sub) return;
                return http('DELETE', '/api/v1/push/subscriptions', { endpoint: sub.endpoint }).catch(function () {}).then(function () { return sub.unsubscribe(); });
            }).then(function () { store.del('almara-push'); setState(false, 'Pengingat dimatikan di perangkat ini.'); });
        });
    })();

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
            // Muat ulang hanya saat PEMBARUAN (bukan pemasangan pertama) dan tidak sedang memutar audio.
            var refreshing = false, hadController = !!navigator.serviceWorker.controller;
            navigator.serviceWorker.addEventListener('controllerchange', function () {
                if (refreshing || !hadController || !navigator.serviceWorker.controller) return;
                if (window.MA.isBusy && window.MA.isBusy()) { toast('Versi baru siap — aktif saat halaman dibuka ulang'); return; }
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
