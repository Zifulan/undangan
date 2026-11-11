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

const FLOWER_ASSETS = [
    'peach'    => [215, 314, 'Peach'],
    'pink'     => [130, 325, 'Pink'],
    'whitemix' => [173, 341, 'Putih'],
    'orchid'   => [193, 295, 'Anggrek'],
    'calla'    => [138, 351, 'Calla'],
    'tulip'    => [140, 305, 'Tulip'],
    'yellow'   => [172, 331, 'Kuning'],
    'burgundy' => [195, 307, 'Burgundy'],
    'spray-a'  => [300, 310, 'Rangkaian A'],
    'spray-b'  => [285, 288, 'Rangkaian B'],
];

const FLOWER_AREAS = ['envelope' => false, 'calendar' => true, 'event' => true]; // true = punya varian m/d

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
    ];
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
        if (!is_array($it) || !isset(FLOWER_ASSETS[$it['a'] ?? ''])) {
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
    $all = flowers_all(true);
    if (FLOWER_AREAS[$area]) {
        $all[$area][$variant] = $items;
    } else {
        $all[$area] = $items;
    }
    $dir = dirname(flowers_file());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $tmp = flowers_file() . '.tmp';
    if (@file_put_contents($tmp, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        return false;
    }
    return @rename($tmp, flowers_file());
}

/** HTML satu item bunga. */
function flower_item(array $it): string
{
    [$w, $h] = FLOWER_ASSETS[$it['a']];
    $style = sprintf('left:%s%%;top:%s%%;width:%s%%;--r:%sdeg;--f:%d', $it['x'], $it['y'], $it['w'], $it['r'], $it['f'] ? -1 : 1);
    return '<picture class="fx fx-' . ($it['z'] === 'back' ? 'back' : 'front') . '" data-a="' . e($it['a']) . '" style="' . $style . '" aria-hidden="true">'
        . '<source srcset="' . e(img_url('bq/' . $it['a'] . '.webp')) . '" type="image/webp">'
        . '<img src="' . e(img_url('bq/' . $it['a'] . '.png')) . '" alt="" width="' . $w . '" height="' . $h . '" decoding="async" draggable="false">'
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
