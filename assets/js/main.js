(function () {
    'use strict';

    var body = document.body;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Pecah judul menjadi kata (animasi muncul per kata) ---------- */
    document.querySelectorAll('.words').forEach(function (el) {
        var parts = el.textContent.trim().split(/\s+/);
        el.setAttribute('aria-label', el.textContent.trim());
        el.innerHTML = parts.map(function (word, i) {
            var span = document.createElement('span');
            span.className = 'w';
            span.setAttribute('aria-hidden', 'true');
            span.style.setProperty('--i', i);
            span.textContent = word;
            return span.outerHTML;
        }).join(' ');
    });

    /* ---------- Buka undangan: segel → tutup amplop → kartu keluar ---------- */
    var cover = document.getElementById('cover');
    var openBtn = document.getElementById('openInvitation');
    var OPEN_MS = reduceMotion ? 0 : 1600;
    var REVEAL_AT = OPEN_MS;

    // Amplop dibuka di tempat dan tetap menjadi bagian atas halaman.
    if (openBtn) {
        openBtn.addEventListener('click', function () {
            if (cover.classList.contains('is-opening')) return;
            cover.classList.add('is-opening');
            openBtn.setAttribute('aria-disabled', 'true');
            setTimeout(function () {
                cover.classList.add('is-open');
                body.classList.remove('is-locked');
            }, OPEN_MS);
        });
    }

    /* ---------- Doodle menggambar dirinya sendiri ---------- */
    // Setiap garis diberi pathLength=1 agar bisa "digambar" dengan stroke-dashoffset.
    var drawables = document.querySelectorAll('.doodle, .dcal');
    if (!reduceMotion) {
        drawables.forEach(function (svgWrap) {
            var i = 0;
            svgWrap.querySelectorAll('path, circle').forEach(function (el) {
                if (el.closest('.dcal-heart')) return;         // hati tanggal: muncul dengan "pop"
                el.setAttribute('pathLength', '1');
                el.style.setProperty('--d', (i++ * 110) + 'ms');
            });
            svgWrap.classList.add('draw');
        });
    }

    // Doodle di sampul langsung digambar saat halaman dibuka.
    setTimeout(function () {
        document.querySelectorAll('#cover .doodle').forEach(function (el) { el.classList.add('is-drawn'); });
    }, 700);

    /* ---------- Animasi muncul saat di-scroll ---------- */
    // Dimulai setelah amplop dibuka, agar animasi tidak terjadi di balik sampul.
    var startReveals = function () {
        var reveals = document.querySelectorAll('.reveal, .words:not(.reveal), .doodle, .dcal');
        if (!('IntersectionObserver' in window)) {
            reveals.forEach(function (el) { el.classList.add('is-visible', 'is-drawn'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible', 'is-drawn');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });
        reveals.forEach(function (el) { io.observe(el); });
    };

    if (openBtn) {
        openBtn.addEventListener('click', function () { setTimeout(startReveals, REVEAL_AT); }, { once: true });
    } else {
        startReveals();
    }

    /* ---------- Simpan ke kalender: iPhone/iPad/Mac pakai file .ics ---------- */
    var calBtn = document.getElementById('calBtn');
    if (calBtn && /iPhone|iPad|iPod|Macintosh/.test(navigator.userAgent)) {
        calBtn.href = calBtn.getAttribute('data-ics');
        calBtn.removeAttribute('target');
        var calAlt = document.getElementById('calAlt');
        if (calAlt) {
            calAlt.href = calBtn.getAttribute('data-gcal') || calAlt.href;
            calAlt.hidden = true;
        }
    }

    /* ---------- Hitung mundur ---------- */
    var cd = document.getElementById('countdown');
    if (cd) {
        var target = new Date(cd.getAttribute('data-target')).getTime();
        var units = {
            days: cd.querySelector('[data-unit="days"]'),
            hours: cd.querySelector('[data-unit="hours"]'),
            minutes: cd.querySelector('[data-unit="minutes"]'),
            seconds: cd.querySelector('[data-unit="seconds"]')
        };
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        var timer;
        var tick = function () {
            var diff = target - Date.now();
            if (diff <= 0) {
                clearInterval(timer);
                cd.hidden = true;
                document.getElementById('countdownDone').hidden = false;
                return;
            }
            var s = Math.floor(diff / 1000);
            units.days.textContent = Math.floor(s / 86400);
            units.hours.textContent = pad(Math.floor((s % 86400) / 3600));
            units.minutes.textContent = pad(Math.floor((s % 3600) / 60));
            units.seconds.textContent = pad(s % 60);
        };
        tick();
        timer = setInterval(tick, 1000);
    }

    /* ---------- Peta (Leaflet + OpenStreetMap) ---------- */
    var mapEl = document.getElementById('map');
    var initMap = function () {
        var lat = parseFloat(mapEl.getAttribute('data-lat'));
        var lng = parseFloat(mapEl.getAttribute('data-lng'));
        if (!isFinite(lat) || !isFinite(lng)) throw new Error('Koordinat peta tidak valid');

        var holder = document.createElement('div');
        holder.className = 'map-leaflet';
        mapEl.appendChild(holder);

        var map = L.map(holder, {
            center: [lat, lng],
            zoom: 16,
            scrollWheelZoom: false,          // jangan "membajak" scroll halaman
            dragging: !L.Browser.mobile,      // di HP, satu jari tetap untuk scroll halaman
            tap: false,
            attributionControl: true
        });

        // Tile standar OpenStreetMap: gratis, tanpa API key (wajib mencantumkan atribusi).
        var tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        map.attributionControl.setPrefix(false);

        L.marker([lat, lng], {
            icon: L.divIcon({ className: '', html: '<div class="map-pin"></div>', iconSize: [34, 44], iconAnchor: [17, 40] }),
            keyboard: false
        })
            .addTo(map)
            .bindTooltip(mapEl.getAttribute('data-label'), {
                permanent: true, direction: 'bottom', offset: [0, 6], className: 'map-label'
            });

        // Ukuran peta dihitung ulang setelah undangan dibuka / tampil.
        var fix = function () { map.invalidateSize(); map.setView([lat, lng], 16); };
        if (openBtn) openBtn.addEventListener('click', function () { setTimeout(fix, OPEN_MS + 300); });
        window.addEventListener('resize', function () { map.invalidateSize(); });

        // Peta baru ditampilkan setelah tile pertama termuat. Bila tile diblokir
        // (jaringan / ad-blocker), kartu lokasi di bawahnya tetap terlihat & bisa diketuk.
        tiles.once('tileload', function () { mapEl.classList.add('is-ready'); });
    };

    var startMap = function () {
        try {
            initMap();
        } catch (err) {
            // Peta gagal dibuat: biarkan kartu lokasi (fallback) yang tampil.
            var broken = mapEl.querySelector('.map-leaflet');
            if (broken) broken.remove();
            if (window.console) console.warn('[peta]', err);
        }
    };

    // Pustaka peta (Leaflet) baru dimuat saat tamu menggulir mendekati peta.
    var loadMap = function () {
        if (window.L) { startMap(); return; }
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'assets/vendor/leaflet/leaflet.css';
        document.head.appendChild(css);
        var js = document.createElement('script');
        js.src = 'assets/vendor/leaflet/leaflet.js';
        js.onload = startMap;          // bila gagal dimuat, kartu lokasi tetap tampil
        document.head.appendChild(js);
    };

    if (mapEl) {
        if ('IntersectionObserver' in window) {
            var mapIo = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) { mapIo.disconnect(); loadMap(); }
            }, { rootMargin: '800px 0px' });
            mapIo.observe(mapEl);
        } else {
            loadMap();
        }
    }

    /* ---------- RSVP ---------- */
    var more = document.getElementById('wishMore');
    if (more) {
        more.addEventListener('click', function () {
            more.closest('.wishes').classList.add('is-all');
            more.hidden = true;
        });
    }

    var form = document.getElementById('rsvpForm');
    if (!form) return;

    var guestsField = document.getElementById('guestsField');
    var status = document.getElementById('formStatus');
    var list = document.getElementById('wishList');
    var empty = document.getElementById('wishEmpty');
    var count = document.getElementById('wishCount');

    form.addEventListener('change', function (e) {
        if (e.target.name === 'attendance') {
            guestsField.hidden = e.target.value !== 'hadir';
        }
    });

    var setStatus = function (msg, type) {
        status.textContent = msg;
        status.className = 'form-status' + (type ? ' is-' + type : '');
    };

    var esc = function (s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    /* Pesawat kertas: formulir terlipat, pesawat terbang, lalu ucapan terima kasih. */
    var thanks = document.getElementById('rsvpThanks');
    var again = document.getElementById('rsvpAgain');
    var planeSvg = '<svg viewBox="0 0 64 56" aria-hidden="true"><g filter="url(#doodle-rough)" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">' +
        '<path d="M6 28 L58 6 L42 50 L30 34 Z" fill="#FAF6EE"/><path d="M30 34 L58 6"/><path d="M30 34 L27 47 L35 40"/></g></svg>';

    var sendOff = function (btn) {
        var done = function () {
            form.hidden = true;
            form.classList.remove('is-folding');
            thanks.hidden = false;
            thanks.classList.add('is-in');
            form.closest('.note').classList.add('is-sent');
        };
        if (reduceMotion) { done(); return; }

        var r = btn.getBoundingClientRect();
        var plane = document.createElement('div');
        plane.className = 'plane';
        plane.innerHTML = planeSvg;
        plane.style.left = (r.left + r.width / 2 - 32) + 'px';
        plane.style.top = (r.top - 28) + 'px';
        document.body.appendChild(plane);
        form.classList.add('is-folding');
        setTimeout(function () { plane.remove(); }, 2000);
        setTimeout(done, 700);
    };

    if (again) {
        again.addEventListener('click', function () {
            thanks.hidden = true;
            thanks.classList.remove('is-in');
            form.closest('.note').classList.remove('is-sent');
            form.hidden = false;
            form.elements.name.focus();
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var name = form.elements.name.value.trim();
        var att = form.querySelector('input[name="attendance"]:checked');
        if (name.length < 2) { setStatus('Mohon isi nama Anda.', 'error'); form.elements.name.focus(); return; }
        if (!att) { setStatus('Mohon pilih konfirmasi kehadiran.', 'error'); return; }

        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        setStatus('Mengirim…');

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                return res.json().catch(function () { return { ok: false, error: 'Respons tidak valid.' }; });
            })
            .then(function (data) {
                if (!data.ok) { setStatus(data.error || 'Gagal mengirim.', 'error'); return; }

                setStatus('', '');
                form.elements.message.value = '';
                sendOff(btn);

                if (data.wish) {
                    var li = document.createElement('li');
                    li.className = 'wish is-new';
                    li.style.setProperty('--tilt', (Math.random() * 5 - 2.5).toFixed(1) + 'deg');
                    li.innerHTML =
                        '<div class="wish-head"><strong>' + esc(data.wish.name) + '</strong>' +
                        '<span class="badge">' + esc(data.wish.attendance) + '</span></div>' +
                        '<p>' + esc(data.wish.message).replace(/\n/g, '<br>') + '</p>' +
                        '<time>' + esc(data.wish.when) + '</time>';
                    list.insertBefore(li, list.firstChild);
                    if (more && list.children.length > 6 && !list.closest('.wishes').classList.contains('is-all')) more.hidden = false;
                    list.scrollTop = 0;
                    empty.hidden = true;
                    count.textContent = list.children.length;
                }
            })
            .catch(function () {
                setStatus('Koneksi bermasalah. Silakan coba lagi.', 'error');
            })
            .then(function () { btn.disabled = false; });
    });
})();
