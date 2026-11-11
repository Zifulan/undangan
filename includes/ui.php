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
 * Segel lilin dari foto lilin asli (assets/img/seal-{tone}.png) dengan monogram
 * yang "tertekan" ke dalam lilin. $tone: 'wine' | 'olive'.
 */
function wax_seal_img(string $monogram, string $tone = 'wine', string $class = ''): string
{
    $tone = $tone === 'olive' ? 'olive' : 'wine';
    return '<span class="seal-img seal-' . $tone . ' ' . e($class) . '" aria-hidden="true">'
        . '<picture><source srcset="' . e(img_url('seal-' . $tone . '.webp')) . '" type="image/webp">'
        . '<img src="' . e(img_url('seal-' . $tone . '.png')) . '" alt="" width="320" height="320" decoding="async"></picture>'
        . '<span class="seal-txt">' . e($monogram) . '</span>'
        . '</span>';
}
