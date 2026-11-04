(function () {
    'use strict';

    var body = document.body;

    /* ---------- Buka undangan ---------- */
    var cover = document.getElementById('cover');
    var openBtn = document.getElementById('openInvitation');

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            cover.classList.add('is-open');
            body.classList.remove('is-locked');
            window.scrollTo(0, 0);
            setTimeout(function () { cover.setAttribute('hidden', ''); }, 1100);
        });
    }

    /* ---------- Animasi muncul saat di-scroll ---------- */
    var reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('is-visible'); });
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
        if (openBtn) openBtn.addEventListener('click', function () { setTimeout(fix, 1100); });
        window.addEventListener('resize', function () { map.invalidateSize(); });

        // Peta baru ditampilkan setelah tile pertama termuat. Bila tile diblokir
        // (jaringan / ad-blocker), kartu lokasi di bawahnya tetap terlihat & bisa diketuk.
        tiles.once('tileload', function () { mapEl.classList.add('is-ready'); });
    };

    if (mapEl && window.L) {
        try {
            initMap();
        } catch (err) {
            // Peta gagal dibuat: biarkan kartu lokasi (fallback) yang tampil.
            var broken = mapEl.querySelector('.map-leaflet');
            if (broken) broken.remove();
            if (window.console) console.warn('[peta]', err);
        }
    }

    /* ---------- RSVP ---------- */
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

                setStatus('Terima kasih! Konfirmasi Anda telah kami terima.', 'success');
                form.elements.message.value = '';

                if (data.wish) {
                    var li = document.createElement('li');
                    li.className = 'wish is-new';
                    li.innerHTML =
                        '<div class="wish-head"><strong>' + esc(data.wish.name) + '</strong>' +
                        '<span class="badge">' + esc(data.wish.attendance) + '</span></div>' +
                        '<p>' + esc(data.wish.message).replace(/\n/g, '<br>') + '</p>' +
                        '<time>' + esc(data.wish.when) + '</time>';
                    list.insertBefore(li, list.firstChild);
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
