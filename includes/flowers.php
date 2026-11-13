<?php
declare(strict_types=1);

/**
 * Bunga yang bisa diatur sendiri lewat editor (index.php?atur=1, perlu login admin).
 *
 * Susunan disimpan di data/flowers.json. Bila file belum ada, dipakai susunan bawaan.
 * Setiap item: aset, x & y (titik tengah, % lebar/tinggi wadah), w (lebar, % lebar wadah),
 * r (putaran, derajat), f (cermin, bool), z ('back' di belakang amplop | 'front').
 *
 * Area & varian:
 *   envelope  — satu susunan (amplop berskala sama di HP & desktop)
 *   calendar, event — varian 'm' (HP) dan 'd' (layar lebar), karena tata letaknya berbeda
 */

/** Aset bawaan: kunci => [lebar, tinggi, label, grup]. File: assets/img/bq/{kunci}.webp|.png */
const FLOWER_BUILTIN = [
    'peach'    => [215, 314, 'Peach', 'Buket'],
    'pink'     => [130, 325, 'Pink', 'Buket'],
    'whitemix' => [173, 341, 'Putih', 'Buket'],
    'orchid'   => [193, 295, 'Anggrek', 'Buket'],
    'calla'    => [138, 351, 'Calla', 'Buket'],
    'tulip'    => [140, 305, 'Tulip', 'Buket'],
    'yellow'   => [172, 331, 'Kuning', 'Buket'],
    'burgundy' => [195, 307, 'Burgundy', 'Buket'],
    'blue'     => [198, 337, 'Biru', 'Buket'],
    'purple'   => [168, 351, 'Ungu', 'Buket'],
    'spray-a'  => [300, 310, 'Rangkaian A', 'Rangkaian'],
    'spray-b'  => [285, 288, 'Rangkaian B', 'Rangkaian'],
    'anthurium-pink'  => [156, 174, 'Anthurium pink', 'Satuan'],
    'anthurium-green' => [136, 134, 'Anthurium hijau', 'Satuan'],
    'orchid-wine'     => [228, 288, 'Anggrek burgundy', 'Satuan'],
    'orchid-spray'    => [174, 256, 'Anggrek putih', 'Satuan'],
    'mum'             => [152, 104, 'Krisan', 'Satuan'],
    'tassel'          => [126, 250, 'Untaian', 'Satuan'],
    // Bunga lukisan (gaya ilustrasi cat air)
    'lk-anthurium-cluster' => [526, 620, 'Anthurium (rumpun)', 'Lukisan'],
    'lk-anthurium'         => [366, 391, 'Anthurium', 'Lukisan'],
    'lk-hibiscus'          => [440, 331, 'Kembang sepatu', 'Lukisan'],
    'lk-hibiscus-sm'       => [383, 283, 'Kembang sepatu kecil', 'Lukisan'],
    'lk-lily'              => [558, 618, 'Lili pink', 'Lukisan'],
    'lk-orchid'            => [498, 451, 'Anggrek putih', 'Lukisan'],
    'lk-orchid-sm'         => [342, 348, 'Anggrek putih kecil', 'Lukisan'],
    'lk-spray'             => [620, 336, 'Rangkaian lukisan', 'Lukisan'],
];

/**
 * Semua aset bunga: bawaan + unggahan (data/flowers.json → uploads).
 * kunci => ['src' => path tanpa ekstensi di assets/img, 'webp' => bool, 'w', 'h', 'label', 'group']
 */
function flower_assets(): array
{
    static $assets = null;
    if ($assets !== null) {
        return $assets;
    }
    $assets = [];
    foreach (FLOWER_BUILTIN as $k => [$w, $h, $label, $group]) {
        $assets[$k] = ['src' => 'bq/' . $k, 'webp' => true, 'w' => $w, 'h' => $h, 'label' => $label, 'group' => $group];
    }
    foreach (flowers_uploads() as $k => $u) {
        if (has_img('up/' . $k . '.png')) {
            $assets[$k] = ['src' => 'up/' . $k, 'webp' => false, 'w' => $u['w'], 'h' => $u['h'], 'label' => $u['label'], 'group' => 'Unggahan'];
        }
    }
    return $assets;
}

function flowers_uploads(): array
{
    $file = flowers_file();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data['uploads'] ?? null) ? $data['uploads'] : [];
}

/** Tulis ulang data/flowers.json dengan perubahan dari $fn(array $data): array. */
function flowers_write(callable $fn): bool
{
    $file = flowers_file();
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode((string) $raw, true);
    $data = $fn(is_array($data) ? $data : []);
    ftruncate($fh, 0);
    rewind($fh);
    $ok = fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false;
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

const FLOWER_AREAS = [ // true = punya varian HP (m) & desktop (d)
    'envelope' => false, 'calendar' => true, 'event' => true,
    'pembuka' => true, 'mempelai' => true, 'acara' => true, 'lokasi' => true, 'rsvp' => true, 'penutup' => true,
];

/** Label area untuk editor. */
const FLOWER_AREA_LABELS = [
    'envelope' => 'Amplop', 'pembuka' => 'Ikatan Suci Kami', 'mempelai' => 'Mempelai',
    'acara' => 'Akad Nikah (latar)', 'calendar' => 'Akad Nikah · Kalender', 'event' => 'Akad Nikah · Kartu acara',
    'lokasi' => 'Lokasi', 'rsvp' => 'Konfirmasi & Ucapan', 'penutup' => 'Penutup',
];

function flower_defaults(): array
{
    $i = fn($a, $x, $y, $w, $r, $f = false, $z = 'front') => ['a' => $a, 'x' => $x, 'y' => $y, 'w' => $w, 'r' => $r, 'f' => $f, 'z' => $z];
    return [
        'envelope' => [
            $i('orchid',   -5,  47.3, 39.3, -20, false, 'back'),
            $i('burgundy', 105, 40.2, 38.1, 18,  false, 'back'),
            $i('peach',    0,   75.7, 45.6, -16),
            $i('whitemix', 100, 78.0, 32.1, 18, true),
        ],
        'calendar' => [
            'm' => [$i('spray-a', 98.1, 16, 43.9, 8, true)],
            'd' => [$i('spray-a', -8.9, 8.4, 54.1, -8)],
        ],
        'event' => [
            'm' => [$i('spray-b', 5, 115.1, 42.1, 180, true)],
            'd' => [$i('spray-b', 110, 101.1, 52.1, 172)],
        ],
    ] + array_fill_keys(['pembuka', 'mempelai', 'acara', 'lokasi', 'rsvp', 'penutup'], ['m' => [], 'd' => []]);
}

function flowers_file(): string
{
    return APP_ROOT . '/data/flowers.json';
}

/** Susunan lengkap (tersimpan bila ada, selain itu bawaan). */
function flowers_all(bool $fresh = false): array
{
    static $all = null;
    if ($all !== null && !$fresh) {
        return $all;
    }
    $all = flower_defaults();
    $file = flowers_file();
    if (is_file($file)) {
        $saved = json_decode((string) file_get_contents($file), true);
        if (is_array($saved)) {
            foreach (FLOWER_AREAS as $area => $hasVariants) {
                if (!isset($saved[$area]) || !is_array($saved[$area])) {
                    continue;
                }
                if ($hasVariants) {
                    foreach (['m', 'd'] as $v) {
                        if (isset($saved[$area][$v]) && is_array($saved[$area][$v])) {
                            $all[$area][$v] = $saved[$area][$v];
                        }
                    }
                } else {
                    $all[$area] = $saved[$area];
                }
            }
        }
    }
    return $all;
}

/** Bersihkan satu daftar item dari editor; null bila tidak valid. */
function flowers_sanitize($items): ?array
{
    if (!is_array($items) || count($items) > 40) {
        return null;
    }
    $clean = [];
    foreach ($items as $it) {
        if (!is_array($it) || !isset(flower_assets()[$it['a'] ?? ''])) {
            return null;
        }
        $num = fn($v, $lo, $hi) => round(max($lo, min($hi, (float) $v)), 2);
        $clean[] = [
            'a' => (string) $it['a'],
            'x' => $num($it['x'] ?? 50, -150, 250),
            'y' => $num($it['y'] ?? 50, -150, 250),
            'w' => $num($it['w'] ?? 30, 3, 200),
            'r' => $num($it['r'] ?? 0, -360, 360),
            'f' => !empty($it['f']),
            'z' => ($it['z'] ?? 'front') === 'back' ? 'back' : 'front',
        ];
    }
    return $clean;
}

function flowers_save(string $area, ?string $variant, array $items): bool
{
    return flowers_write(function (array $data) use ($area, $variant, $items) {
        if (FLOWER_AREAS[$area]) {
            $data[$area] = (isset($data[$area]) && is_array($data[$area]) ? $data[$area] : []);
            $data[$area][$variant] = $items;
        } else {
            $data[$area] = $items;
        }
        return $data;
    });
}

/** HTML satu item bunga. */
function flower_item(array $it): string
{
    $as = flower_assets()[$it['a']] ?? null;
    if ($as === null) {
        return '';
    }
    $style = sprintf('left:%s%%;top:%s%%;width:%s%%;--r:%sdeg;--f:%d', $it['x'], $it['y'], $it['w'], $it['r'], $it['f'] ? -1 : 1);
    return '<picture class="fx fx-' . ($it['z'] === 'back' ? 'back' : 'front') . '" data-a="' . e($it['a']) . '" style="' . $style . '" aria-hidden="true">'
        . ($as['webp'] ? '<source srcset="' . e(img_url($as['src'] . '.webp')) . '" type="image/webp">' : '')
        . '<img src="' . e(img_url($as['src'] . '.png')) . '" alt="" width="' . (int) $as['w'] . '" height="' . (int) $as['h'] . '" loading="lazy" decoding="async" draggable="false">'
        . '</picture>';
}

/** Lapisan bunga untuk satu area (dengan varian HP & desktop bila ada). */
function flowers_layer(string $area): string
{
    $all = flowers_all();
    if (FLOWER_AREAS[$area]) {
        $html = '';
        foreach (['m', 'd'] as $v) {
            $html .= '<div class="fx-set fx-set-' . $v . '" data-area="' . $area . '" data-variant="' . $v . '">'
                . implode('', array_map('flower_item', $all[$area][$v])) . '</div>';
        }
        return $html;
    }
    return '<div class="fx-set" data-area="' . $area . '">' . implode('', array_map('flower_item', $all[$area])) . '</div>';
}
