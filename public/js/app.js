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

    // ---------- Pemutar murattal (Qari ALMARA / Quran.Foundation) ----------
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

        function load() {
            var key = sel ? sel.value : null;
            if (st.data && st.key === key) return Promise.resolve(st.data);
            if (st.loading) return st.loading;
            busy(true);
            st.loading = fetch(player.dataset.api + (key ? '?reciter=' + encodeURIComponent(key) : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Audio belum tersedia.'); return j.data; }); })
                .then(function (d) {
                    st.data = d; st.key = key; audio.src = d.url;
                    var info = [];
                    if (d.simulated) info.push('Mode simulasi: nada uji, bukan bacaan.');
                    if (d.attribution) info.push(d.attribution + '.');
                    if (st.verseMode && !(d.segments || []).length) info.push('Penanda ayat belum tersedia untuk qari ini — diputar satu surah.');
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
                var s = ayah ? segOf(ayah) : null;
                st.range = null;
                if (ayah && !s) toast('Penanda ayat belum tersedia — diputar dari awal surah');
                if (s && st.verseMode) st.range = { start: s.start_ms / 1000, end: s.end_ms / 1000 };
                return (s ? seekTo(s.start_ms / 1000) : ayah ? seekTo(0) : Promise.resolve()).then(function () { return audio.play(); });
            }).catch(function (e) { if (e && e.name !== 'AbortError') { toast(e.message || 'Audio gagal diputar'); setNote(e.message); } });
        }

        btn.addEventListener('click', function () {
            if (!audio.paused) { audio.pause(); return; }
            if (st.data && audio.currentTime > 0) { audio.play().catch(function () {}); return; }
            playFrom(null);
        });
        $$('[data-play-ayah]').forEach(function (b) { b.addEventListener('click', function () { playFrom(+b.dataset.playAyah); }); });

        audio.addEventListener('play', function () { player.classList.add('is-playing'); btn.setAttribute('aria-label', 'Jeda murattal'); });
        audio.addEventListener('pause', function () { player.classList.remove('is-playing'); btn.setAttribute('aria-label', 'Putar murattal'); });
        audio.addEventListener('loadedmetadata', function () { if (isFinite(audio.duration)) { seek.max = audio.duration; dur.textContent = fmt(audio.duration); } });
        audio.addEventListener('waiting', function () { busy(true); });
        audio.addEventListener('playing', function () { busy(false); });
        audio.addEventListener('error', function () { busy(false); if (st.data) setNote('Audio tidak dapat dimuat. Periksa koneksi lalu coba lagi.'); });
        audio.addEventListener('timeupdate', function () {
            var t = audio.currentTime;
            if (!st.seeking) seek.value = t;
            cur.textContent = fmt(t);
            var ms = t * 1000, s = segs(), found = null;
            for (var i = 0; i < s.length; i++) { if (ms >= s[i].start_ms && ms < s[i].end_ms) { found = s[i].ayah; break; } }
            if (s.length) highlight(found);
            if (st.range && t >= st.range.end) {
                if (st.repeat) audio.currentTime = st.range.start;
                else { audio.pause(); st.range = null; }
            }
        });
        audio.addEventListener('ended', function () {
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
            } catch (e) {}
        }
        // Spasi = putar/jeda (kecuali saat mengetik)
        document.addEventListener('keydown', function (e) {
            if (e.code !== 'Space' || /input|textarea|select|button/i.test((e.target.tagName || '')) || e.target.isContentEditable) return;
            e.preventDefault(); btn.click();
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
        var pre = (lang || 'ar').slice(0, 2);
        var v = speechSynthesis.getVoices().filter(function (x) { return x.lang && x.lang.toLowerCase().indexOf(pre) === 0; })[0];
        if (v) u.voice = v; else if (pre === 'ar') toast('Suara Arab belum terpasang di perangkat ini');
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

    // ---------- Terjemah teks ----------
    var trf = $('[data-translate]');
    if (trf) {
        var trSrc = $('[data-tr-src]', trf), trTgt = $('[data-tr-tgt]', trf), trOut = $('[data-tr-out]', trf), trText = $('[data-tr-text]', trf);
        $('[data-tr-swap]', trf).addEventListener('click', function () { var a = trSrc.value; trSrc.value = trTgt.value; trTgt.value = a; });
        trf.addEventListener('submit', function (e) {
            e.preventDefault();
            if (trSrc.value === trTgt.value) { toast('Pilih bahasa yang berbeda'); return; }
            var btn = $('[data-tr-go]', trf); btn.disabled = true;
            http('POST', '/api/v1/translate', { q: $('[data-tr-q]', trf).value, source: trSrc.value, target: trTgt.value })
                .then(function (r) { trText.textContent = r.data.text; trText.lang = trTgt.value; trOut.hidden = false; })
                .catch(function (err) { toast(err.message || 'Terjemah gagal'); })
                .finally(function () { btn.disabled = false; });
        });
        $('[data-tr-speak]', trf).addEventListener('click', function () { speak(trText.textContent, { ar: 'ar-SA', en: 'en-US', id: 'id-ID' }[trText.lang] || 'id-ID'); });
        $('[data-tr-copy]', trf).addEventListener('click', function () { if (navigator.clipboard) navigator.clipboard.writeText(trText.textContent).then(function () { toast('Disalin'); }); });
    }

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
