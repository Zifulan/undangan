/* Editor bunga — aktif di index.php?atur=1 untuk admin yang sudah login.
   Ketuk bunga untuk memilih, geser untuk memindah, tarik bulatan di sudut untuk
   memperbesar/memutar. Simpan menulis ke data/flowers.json lewat api/flowers.php. */
(function () {
    'use strict';
    var CFG = window.FLOWER_EDITOR;
    if (!CFG) return;

    var body = document.body;
    var bar = document.getElementById('fxBar');
    var strip = document.getElementById('fxStrip');
    var chips = document.getElementById('fxChips');
    var areaSel = document.getElementById('fxArea');
    var statusEl = document.getElementById('fxStatus');
    var handle = document.createElement('div');
    handle.className = 'fx-handle';
    handle.innerHTML = '<span class="fx-knob" title="Tarik untuk memperbesar / memutar"></span>';
    document.body.appendChild(handle);

    var selected = null;
    var dirty = {};          // key set -> elemen set
    var history = [];        // [{set, html}]

    var isDesktop = function () { return window.matchMedia('(min-width: 760px)').matches; };
    var setFor = function (area) {
        var sets = document.querySelectorAll('.fx-set[data-area="' + area + '"]');
        if (sets.length === 1) return sets[0];
        var v = isDesktop() ? 'd' : 'm';
        return document.querySelector('.fx-set[data-area="' + area + '"][data-variant="' + v + '"]');
    };
    var setKey = function (set) { return set.dataset.area + (set.dataset.variant ? ':' + set.dataset.variant : ''); };
    var activeSet = function () { return setFor(areaSel.value); };

    var status = function (msg, cls) {
        statusEl.textContent = msg;
        statusEl.className = 'fx-status' + (cls ? ' is-' + cls : '');
    };
    var updateDeviceNote = function () {
        var hasVariants = document.querySelectorAll('.fx-set[data-area="' + areaSel.value + '"]').length > 1;
        status(hasVariants ? (isDesktop() ? 'Mengatur tampilan desktop' : 'Mengatur tampilan HP') : 'Berlaku untuk HP & desktop');
    };

    /* ---------- baca / tulis properti item ---------- */
    var num = function (v, d) { var n = parseFloat(v); return isNaN(n) ? d : n; };
    var get = function (el) {
        return {
            x: num(el.style.left, 50), y: num(el.style.top, 50), w: num(el.style.width, 30),
            r: num(el.style.getPropertyValue('--r'), 0), f: el.style.getPropertyValue('--f').trim() === '-1',
            z: el.classList.contains('fx-back') ? 'back' : 'front', a: el.dataset.a
        };
    };
    var put = function (el, p) {
        el.style.left = p.x.toFixed(2) + '%';
        el.style.top = p.y.toFixed(2) + '%';
        el.style.width = p.w.toFixed(2) + '%';
        el.style.setProperty('--r', p.r.toFixed(1) + 'deg');
        el.style.setProperty('--f', p.f ? '-1' : '1');
        el.classList.toggle('fx-back', p.z === 'back');
        el.classList.toggle('fx-front', p.z !== 'back');
    };

    /* ---------- riwayat (urungkan) ---------- */
    var snapshot = function (set) {
        history.push({ set: set, html: set.innerHTML });
        if (history.length > 60) history.shift();
        dirty[setKey(set)] = set;
        status('Belum disimpan', 'warn');
    };
    var undo = function () {
        var h = history.pop();
        if (!h) return;
        h.set.innerHTML = h.html;
        select(null);
        renderChips();
    };

    /* ---------- pilihan ---------- */
    var positionHandle = function () {
        if (!selected) { handle.style.display = 'none'; return; }
        var r = selected.getBoundingClientRect();
        handle.style.display = 'block';
        handle.style.left = (r.left + window.scrollX) + 'px';
        handle.style.top = (r.top + window.scrollY) + 'px';
        handle.style.width = r.width + 'px';
        handle.style.height = r.height + 'px';
    };
    var select = function (el) {
        if (selected) selected.classList.remove('is-selected');
        selected = el;
        if (el) {
            el.classList.add('is-selected');
            var set = el.closest('.fx-set');
            if (areaSel.value !== set.dataset.area) { areaSel.value = set.dataset.area; updateDeviceNote(); renderChips(); }
        }
        bar.classList.toggle('has-selection', !!el);
        positionHandle();
        renderChips();
    };

    /* ---------- daftar item di area aktif (bisa memilih bunga di belakang amplop) ---------- */
    var renderChips = function () {
        var set = activeSet();
        chips.innerHTML = '';
        if (!set) return;
        Array.prototype.forEach.call(set.querySelectorAll('.fx'), function (el, i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'fx-chip' + (el === selected ? ' is-on' : '') + (el.classList.contains('fx-back') ? ' is-back' : '');
            b.textContent = (i + 1) + '. ' + (CFG.assets[el.dataset.a] || el.dataset.a);
            b.addEventListener('click', function () { select(el); });
            chips.appendChild(b);
        });
    };

    /* ---------- seret untuk memindah ---------- */
    var drag = null;
    document.addEventListener('pointerdown', function (e) {
        var el = e.target.closest('.fx');
        if (!el || e.target.closest('.fx-handle')) return;
        e.preventDefault();
        select(el);
        var set = el.closest('.fx-set');
        snapshot(set);
        var rect = set.getBoundingClientRect();
        drag = { el: el, sx: e.clientX, sy: e.clientY, p: get(el), rw: rect.width, rh: rect.height };
        el.setPointerCapture && el.setPointerCapture(e.pointerId);
    });
    document.addEventListener('pointermove', function (e) {
        if (drag) {
            var p = drag.p;
            put(drag.el, Object.assign({}, p, {
                x: p.x + (e.clientX - drag.sx) / drag.rw * 100,
                y: p.y + (e.clientY - drag.sy) / drag.rh * 100
            }));
            positionHandle();
        } else if (knob) {
            var dx = e.clientX - knob.cx, dy = e.clientY - knob.cy;
            var dist = Math.max(10, Math.hypot(dx, dy));
            var ang = Math.atan2(dy, dx) * 180 / Math.PI;
            put(selected, Object.assign({}, knob.p, {
                w: Math.max(4, knob.p.w * dist / knob.d0),
                r: knob.p.r + (ang - knob.a0)
            }));
            positionHandle();
        }
    });
    var stop = function () { drag = null; knob = null; };
    document.addEventListener('pointerup', stop);
    document.addEventListener('pointercancel', stop);

    /* ---------- bulatan sudut: perbesar + putar ---------- */
    var knob = null;
    handle.querySelector('.fx-knob').addEventListener('pointerdown', function (e) {
        if (!selected) return;
        e.preventDefault(); e.stopPropagation();
        snapshot(selected.closest('.fx-set'));
        var r = selected.getBoundingClientRect();
        var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        knob = { cx: cx, cy: cy, p: get(selected),
                 d0: Math.max(10, Math.hypot(e.clientX - cx, e.clientY - cy)),
                 a0: Math.atan2(e.clientY - cy, e.clientX - cx) * 180 / Math.PI };
    });

    /* ---------- tombol ---------- */
    var act = function (fn) {
        return function () {
            if (!selected) return;
            snapshot(selected.closest('.fx-set'));
            fn(get(selected));
            positionHandle(); renderChips();
        };
    };
    var btn = function (id, fn) { var b = document.getElementById(id); if (b) b.addEventListener('click', fn); };
    btn('fxFlip',   act(function (p) { p.f = !p.f; put(selected, p); }));
    btn('fxLayer',  act(function (p) { p.z = p.z === 'back' ? 'front' : 'back'; put(selected, p); }));
    btn('fxUp',     act(function () { selected.parentNode.appendChild(selected); }));
    btn('fxBigger', act(function (p) { p.w *= 1.08; put(selected, p); }));
    btn('fxSmaller',act(function (p) { p.w /= 1.08; put(selected, p); }));
    btn('fxRotL',   act(function (p) { p.r -= 5; put(selected, p); }));
    btn('fxRotR',   act(function (p) { p.r += 5; put(selected, p); }));
    btn('fxDel', function () {
        if (!selected) return;
        snapshot(selected.closest('.fx-set'));
        selected.remove(); select(null);
    });
    btn('fxUndo', undo);
    btn('fxDone', function () { select(null); });

    /* tambah bunga dari daftar */
    Object.keys(CFG.assets).forEach(function (key) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'fx-add';
        b.innerHTML = '<img src="' + CFG.thumbs[key] + '" alt=""><span>' + CFG.assets[key] + '</span>';
        b.addEventListener('click', function () {
            var set = activeSet();
            if (!set) return;
            snapshot(set);
            var pic = document.createElement('picture');
            pic.className = 'fx fx-front';
            pic.dataset.a = key;
            pic.setAttribute('aria-hidden', 'true');
            pic.innerHTML = '<img src="' + CFG.thumbs[key] + '" alt="" draggable="false">';
            put(pic, { x: 50, y: 50, w: set.dataset.area === 'envelope' ? 35 : 40, r: 0, f: false, z: 'front' });
            set.appendChild(pic);
            select(pic);
            set.closest('.envelope, .calendar-wrap, .event-meta').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        strip.appendChild(b);
    });

    areaSel.addEventListener('change', function () {
        select(null);
        updateDeviceNote();
        renderChips();
        var set = activeSet();
        if (set) set.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    /* ---------- simpan ---------- */
    btn('fxSave', function () {
        var sets = Object.keys(dirty).map(function (k) {
            var set = dirty[k];
            return {
                area: set.dataset.area,
                variant: set.dataset.variant || null,
                items: Array.prototype.map.call(set.querySelectorAll('.fx'), get)
            };
        });
        if (!sets.length) { status('Tidak ada perubahan'); return; }
        status('Menyimpan…');
        fetch(CFG.api, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ csrf: CFG.csrf, sets: sets })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.ok, d: d }; }); })
          .then(function (res) {
              if (res.ok) { dirty = {}; status('Tersimpan ✓', 'ok'); }
              else status(res.d.error || 'Gagal menyimpan', 'err');
          })
          .catch(function () { status('Gagal menyimpan (koneksi)', 'err'); });
    });

    window.addEventListener('beforeunload', function (e) {
        if (Object.keys(dirty).length) { e.preventDefault(); e.returnValue = ''; }
    });
    window.addEventListener('resize', function () { positionHandle(); updateDeviceNote(); renderChips(); });
    window.addEventListener('scroll', positionHandle, { passive: true });

    body.classList.add('fx-editing');
    updateDeviceNote();
    renderChips();
})();
