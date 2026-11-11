<?php
declare(strict_types=1);

/**
 * Elemen visual yang dipakai berulang: segel lilin, bingkai kartu, slot gambar.
 *
 * SLOT GAMBAR (opsional) — cukup unggah file dengan nama berikut ke assets/img/
 * dan gambar akan tampil otomatis. Bila file tidak ada, slot disembunyikan.
 *
 *   floral-hero.png     bunga di pojok kartu nama (PNG transparan, ±600px)
 *   floral-date.png     bunga di pojok kartu tanggal (PNG transparan, ±600px)
 *   floral-rsvp.png     bunga di pojok kartu RSVP (PNG transparan, ±600px)
 *   floral-envelope.png bunga di pojok amplop sampul (PNG transparan, ±600px)
 *   bg-cover.jpg        lukisan latar sampul (potret, ±1080×1920)
 */

function has_img(string $file): bool
{
    return is_file(APP_ROOT . '/assets/img/' . $file);
}

/** URL gambar dengan penanda versi agar cache browser diperbarui saat file diganti. */
function img_url(string $file): string
{
    $path = APP_ROOT . '/assets/img/' . $file;
    return 'assets/img/' . rawurlencode($file) . (is_file($path) ? '?v=' . filemtime($path) : '');
}

/** <img> dekoratif bila file ada; string kosong bila tidak. */
function slot_img(string $file, string $class): string
{
    if (!has_img($file)) {
        return '';
    }
    return '<img class="slot ' . e($class) . '" src="' . e(img_url($file)) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">';
}

/** Gaya background-image inline bila file ada. */
function slot_bg(string $file): string
{
    return has_img($file) ? ' style="--slot-bg:url(\'' . e(img_url($file)) . '\')" data-has-bg' : '';
}

/**
 * Segel lilin (SVG) dengan tepi bergelombang & monogram.
 * $tone: 'olive' | 'plum'
 */
function wax_seal(string $monogram, string $tone = 'olive', string $class = ''): string
{
    static $n = 0;
    $n++;
    $id = 'seal' . $n;

    // [warna utama, bayangan, gelap, kilau, monogram]
    $colors = $tone === 'plum'
        ? ['#6B3646', '#4A2230', '#2F121C', '#8A5464', '#D7A9B4']
        : ['#7D7A48', '#5E5C30', '#3D3C1C', '#9C9966', '#E2DDAA'];

    // Tepi lilin yang tidak beraturan
    $pts = [];
    $steps = 72;
    for ($i = 0; $i < $steps; $i++) {
        $a = 2 * M_PI * $i / $steps;
        $r = 46 + 2.2 * sin($a * 7) + 1.4 * sin($a * 13 + 1.3) + 0.8 * sin($a * 23 + .4);
        $pts[] = round(50 + $r * cos($a), 2) . ' ' . round(50 + $r * sin($a), 2);
    }
    $blob = 'M' . implode(' L', $pts) . 'Z';

    // Untaian titik kecil seperti cap segel
    $dots = '';
    for ($i = 0; $i < 28; $i++) {
        $a = 2 * M_PI * $i / 28;
        $dots .= '<circle cx="' . round(50 + 31.5 * cos($a), 2) . '" cy="' . round(50 + 31.5 * sin($a), 2) . '" r=".9"/>';
    }

    return '<svg class="wax-seal ' . e($class) . '" viewBox="0 0 100 100" aria-hidden="true">'
        . '<defs>'
        . '<radialGradient id="' . $id . 'g" cx="38%" cy="32%" r="75%">'
        . '<stop offset="0" stop-color="' . $colors[3] . '"/><stop offset=".45" stop-color="' . $colors[0] . '"/>'
        . '<stop offset="1" stop-color="' . $colors[2] . '"/></radialGradient>'
        . '<radialGradient id="' . $id . 'i" cx="60%" cy="65%" r="70%">'
        . '<stop offset="0" stop-color="' . $colors[0] . '"/><stop offset="1" stop-color="' . $colors[1] . '"/></radialGradient>'
        . '</defs>'
        . '<path d="' . $blob . '" fill="url(#' . $id . 'g)"/>'
        . '<circle cx="50" cy="50" r="36" fill="url(#' . $id . 'i)" stroke="' . $colors[2] . '" stroke-opacity=".55" stroke-width="1.2"/>'
        . '<circle cx="50" cy="50.8" r="36" fill="none" stroke="' . $colors[3] . '" stroke-opacity=".5" stroke-width=".7"/>'
        . '<g fill="' . $colors[3] . '" fill-opacity=".75">' . $dots . '</g>'
        . '<text x="50" y="50" class="seal-mono" text-anchor="middle" dominant-baseline="central" fill="' . $colors[2] . '" fill-opacity=".8" transform="translate(.6 .9)">' . e($monogram) . '</text>'
        . '<text x="50" y="50" class="seal-mono" text-anchor="middle" dominant-baseline="central" fill="' . $colors[4] . '" fill-opacity=".9">' . e($monogram) . '</text>'
        . '</svg>';
}

/**
 * Bingkai kartu berpinggiran klasik (sudut cekung, puncak melengkung, pinggang samping).
 * Dipakai sebagai latar absolut di dalam .cartouche.
 */
function cartouche_frame(): string
{
    $outer = 'M40 14 L118 14 Q134 14 141 7 Q150 0 159 7 Q166 14 182 14 L260 14 '
        . 'A26 26 0 0 0 286 40 L286 140 Q276 200 286 260 L286 360 A26 26 0 0 0 260 386 '
        . 'L182 386 Q166 386 159 393 Q150 400 141 393 Q134 386 118 386 L40 386 '
        . 'A26 26 0 0 0 14 360 L14 260 Q24 200 14 140 L14 40 A26 26 0 0 0 40 14Z';

    return '<svg class="cartouche-frame" viewBox="0 0 300 400" preserveAspectRatio="none" aria-hidden="true">'
        . '<path class="cf-fill" d="' . $outer . '"/>'
        . '<path class="cf-line" filter="url(#doodle-rough)" d="' . $outer . '" vector-effect="non-scaling-stroke"/>'
        . '<path class="cf-line cf-inner" filter="url(#doodle-rough)" d="' . $outer . '" transform="translate(150 200) scale(.94 .955) translate(-150 -200)" vector-effect="non-scaling-stroke"/>'
        . '</svg>';
}


/**
 * Liontin hati yang terbuka (dua sisi), masing-masing berisi satu foto.
 * Foto dipotong mengikuti bentuk hati; bingkai perak digambar dengan gradien.
 */
function heart_locket(string $left, string $right, string $label): string
{
    $heart = 'M50 92 C20 70 4 52 7 32 C10 13 34 6 50 25 C66 6 90 13 93 32 C96 52 80 70 50 92Z';
    $side = function (string $img, string $id, float $x, float $rot) use ($heart): string {
        return '<g transform="translate(' . $x . ' 8) rotate(' . $rot . ' 50 50)">'
            . '<clipPath id="' . $id . '"><path d="' . $heart . '" transform="translate(50 50) scale(.8) translate(-50 -50)"/></clipPath>'
            . '<path d="' . $heart . '" fill="url(#lkRim)" stroke="#6F6A66" stroke-width="1.2"/>'
            . '<path d="' . $heart . '" fill="none" stroke="#F4F2EE" stroke-opacity=".7" stroke-width="1" transform="translate(50 50) scale(.9) translate(-50 -50)"/>'
            . '<path d="' . $heart . '" fill="#2A2224" transform="translate(50 50) scale(.82) translate(-50 -50)"/>'
            . '<image href="' . e($img) . '" x="14" y="12" width="72" height="77" preserveAspectRatio="xMidYMid slice" clip-path="url(#' . $id . ')"/>'
            . '<path d="' . $heart . '" fill="url(#lkGlass)" transform="translate(50 50) scale(.8) translate(-50 -50)"/>'
            . '</g>';
    };

    return '<svg class="locket" viewBox="0 0 214 112" role="img" aria-label="' . e($label) . '">'
        . '<defs>'
        . '<linearGradient id="lkRim" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="#F3F1EC"/><stop offset=".35" stop-color="#B9B5AE"/>'
        . '<stop offset=".6" stop-color="#E9E6E0"/><stop offset="1" stop-color="#8C8781"/></linearGradient>'
        . '<linearGradient id="lkGlass" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="#fff" stop-opacity=".28"/><stop offset=".4" stop-color="#fff" stop-opacity="0"/></linearGradient>'
        . '</defs>'
        // engsel di tengah
        . '<rect x="101" y="44" width="12" height="20" rx="3" fill="url(#lkRim)" stroke="#6F6A66" stroke-width="1"/>'
        . '<path d="M101 50 h12 M101 58 h12" stroke="#6F6A66" stroke-width=".8"/>'
        . $side($left, 'lkL', 4, -6)
        . $side($right, 'lkR', 110, 6)
        . '</svg>';
}

/**
 * Rangkaian bunga di sekitar amplop: [nama file tanpa ekstensi, kelas posisi].
 * Rangkaian bertangkai dipasang dua kali (fl-stems di belakang amplop,
 * fl-heads di depan) agar ujung tangkai tersembunyi.
 */
const ENVELOPE_FLOWERS = [
    // kiri
    ['fl-anthurium-cluster', 'f1 fl-stems'], ['fl-anthurium-cluster', 'f1 fl-heads'],
    ['fl-orchid',            'f2'],
    ['fl-anthurium',         'f3'],
    ['fl-hibiscus',          'f4'],
    ['fl-hibiscus-sm',       'f5'],
    ['fl-bouquet-peach',     'f6'],
    // kanan
    ['fl-lily',              'f7 fl-stems'], ['fl-lily', 'f7 fl-heads'],
    ['fl-anthurium',         'f8'],
    ['fl-lily',              'f9 fl-stems'], ['fl-lily', 'f9 fl-heads'],
    ['fl-orchid',            'f10'],
    ['fl-hibiscus',          'f11'],
    // bawah, di sekitar liontin
    ['fl-anthurium',         'f12'],
    ['fl-orchid-sm',         'f13'],
    ['fl-hibiscus-sm',       'f14'],
    ['fl-orchid-sm',         'f15'],
];

/**
 * Segel lilin "mewah" untuk amplop pembuka: tepi lilin meleleh tak beraturan,
 * cincin cap yang tertekan, monogram timbul, kilau basah, dan tekstur halus.
 */
function wax_seal_deluxe(string $monogram): string
{
    // tepi lilin: beberapa gelombang + tonjolan lelehan
    $pts = [];
    $steps = 120;
    for ($i = 0; $i < $steps; $i++) {
        $a = 2 * M_PI * $i / $steps;
        $r = 44 + 2.4 * sin($a * 5 + .7) + 1.6 * sin($a * 11 + 2.1) + .9 * sin($a * 19);
        // lelehan di beberapa titik
        foreach ([[.9, 4.5], [2.6, 3.2], [4.3, 5.2], [5.6, 2.6]] as [$c, $h]) {
            $d = atan2(sin($a - $c), cos($a - $c));
            $r += $h * exp(-($d * $d) / .03);
        }
        $pts[] = round(50 + $r * cos($a), 2) . ' ' . round(50 + $r * sin($a), 2);
    }
    $blob = 'M' . implode(' L', $pts) . 'Z';

    // cincin titik pada cap
    $dots = '';
    for ($i = 0; $i < 32; $i++) {
        $a = 2 * M_PI * $i / 32;
        $dots .= '<circle cx="' . round(50 + 28.5 * cos($a), 2) . '" cy="' . round(50 + 28.5 * sin($a), 2) . '" r=".75"/>';
    }

    // ranting daun kecil di bawah monogram
    $sprig = '<path d="M38 66 Q50 71 62 66" fill="none" stroke-width=".9"/>'
        . '<path d="M42 67.2 q-2 -3.2 1 -4.6 q1.4 2.6 -1 4.6z M46 68.4 q-1.4 -3.4 1.6 -4.4 q1 2.8 -1.6 4.4z'
        . ' M58 67.2 q2 -3.2 -1 -4.6 q-1.4 2.6 1 4.6z M54 68.4 q1.4 -3.4 -1.6 -4.4 q-1 2.8 1.6 4.4z"/>';

    $mono = e($monogram);

    return '<svg class="wax-seal seal-deluxe" viewBox="0 0 100 100" aria-hidden="true">'
        . '<defs>'
        . '<radialGradient id="sdWax" cx="36%" cy="30%" r="80%">'
        . '<stop offset="0" stop-color="#9A4A5C"/><stop offset=".35" stop-color="#7A2E40"/>'
        . '<stop offset=".75" stop-color="#5A1E2C"/><stop offset="1" stop-color="#3A111B"/></radialGradient>'
        . '<radialGradient id="sdPress" cx="60%" cy="66%" r="72%">'
        . '<stop offset="0" stop-color="#7C3042"/><stop offset="1" stop-color="#521A28"/></radialGradient>'
        . '<linearGradient id="sdRim" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="#2E0C15" stop-opacity=".9"/><stop offset=".5" stop-color="#2E0C15" stop-opacity=".2"/>'
        . '<stop offset="1" stop-color="#C98493" stop-opacity=".75"/></linearGradient>'
        . '<filter id="sdTex" x="-5%" y="-5%" width="110%" height="110%">'
        . '<feTurbulence type="fractalNoise" baseFrequency="1.1" numOctaves="2" seed="4" result="n"/>'
        . '<feColorMatrix in="n" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 .22 0" result="g"/>'
        . '<feComposite in="g" in2="SourceGraphic" operator="in"/></filter>'
        . '</defs>'
        // bayangan lilin di kertas
        . '<path d="' . $blob . '" fill="#1E0910" opacity=".35" transform="translate(1.2 2)"/>'
        // badan lilin + tekstur
        . '<path d="' . $blob . '" fill="url(#sdWax)"/>'
        . '<path d="' . $blob . '" fill="#000" filter="url(#sdTex)"/>'
        // bibir lilin yang terangkat di sekeliling cap
        . '<circle cx="50" cy="50" r="35.5" fill="none" stroke="#B0607A" stroke-opacity=".35" stroke-width="2.4" transform="translate(-.6 -.8)"/>'
        // cap yang tertekan
        . '<circle cx="50" cy="50" r="33" fill="url(#sdPress)"/>'
        . '<circle cx="50" cy="50" r="33" fill="none" stroke="url(#sdRim)" stroke-width="1.6"/>'
        . '<circle cx="50" cy="50" r="30.6" fill="none" stroke="#C98493" stroke-opacity=".45" stroke-width=".5"/>'
        . '<g fill="#C98493" fill-opacity=".7">' . $dots . '</g>'
        // monogram timbul: bayangan gelap, lalu sorot terang, lalu warna
        . '<text x="50" y="47" class="seal-mono sd-mono" text-anchor="middle" dominant-baseline="central" fill="#2A0A12" fill-opacity=".75" transform="translate(.7 .9)">' . $mono . '</text>'
        . '<text x="50" y="47" class="seal-mono sd-mono" text-anchor="middle" dominant-baseline="central" fill="#E9B9C3" fill-opacity=".55" transform="translate(-.45 -.55)">' . $mono . '</text>'
        . '<text x="50" y="47" class="seal-mono sd-mono" text-anchor="middle" dominant-baseline="central" fill="#8E3A4E">' . $mono . '</text>'
        . '<g fill="#8E3A4E" stroke="#8E3A4E">' . $sprig . '</g>'
        . '<g fill="none" stroke="#E9B9C3" stroke-opacity=".45" transform="translate(-.4 -.5)">' . str_replace(['<path d="M42', '"/>'], ['<path fill="none" d="M42', '"/>'], $sprig) . '</g>'
        // kilau basah
        . '<path d="M24 30 Q30 18 44 15" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.6" stroke-linecap="round"/>'
        . '<path d="M20 40 Q20.5 36 22 33" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width="1.6" stroke-linecap="round"/>'
        . '<ellipse cx="70" cy="78" rx="6" ry="2.2" fill="#fff" fill-opacity=".12" transform="rotate(-30 70 78)"/>'
        . '</svg>';
}
