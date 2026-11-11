<?php
declare(strict_types=1);

/**
 * Unggah / hapus bunga sendiri dari editor (khusus admin).
 * PNG/WebP transparan dipakai apa adanya; gambar berlatar polos (mis. putih)
 * latarnya dihapus otomatis. Hasil disimpan di assets/img/up/{kunci}.png.
 */
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    out(405, ['ok' => false, 'error' => 'Metode tidak diizinkan.']);
}
if (empty($_SESSION['admin'])) {
    out(403, ['ok' => false, 'error' => 'Silakan login admin dulu.']);
}
if (!csrf_valid($_POST['csrf'] ?? null)) {
    out(403, ['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman.']);
}
if (!function_exists('imagecreatefromstring')) {
    out(500, ['ok' => false, 'error' => 'Server tidak mendukung pengolahan gambar (GD).']);
}

$dir = APP_ROOT . '/assets/img/up';

/* ---------- hapus ---------- */
if (($_POST['action'] ?? '') === 'delete') {
    $key = (string) ($_POST['key'] ?? '');
    if (!preg_match('/^up-[a-f0-9]{8}$/', $key)) {
        out(400, ['ok' => false, 'error' => 'Kunci tidak valid.']);
    }
    @unlink($dir . '/' . $key . '.png');
    flowers_write(function (array $d) use ($key) {
        unset($d['uploads'][$key]);
        return $d;
    });
    out(200, ['ok' => true]);
}

/* ---------- unggah ---------- */
$f = $_FILES['file'] ?? null;
if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    out(400, ['ok' => false, 'error' => 'File tidak terkirim (maks. 8 MB).']);
}
if ($f['size'] > 8 * 1024 * 1024) {
    out(400, ['ok' => false, 'error' => 'File terlalu besar (maks. 8 MB).']);
}
$info = @getimagesize($f['tmp_name']);
if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
    out(400, ['ok' => false, 'error' => 'Gunakan gambar PNG, JPG, atau WebP.']);
}
if ($info[0] * $info[1] > 40000000) {
    out(400, ['ok' => false, 'error' => 'Ukuran gambar terlalu besar.']);
}

$src = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
if (!$src) {
    out(400, ['ok' => false, 'error' => 'Gambar tidak bisa dibaca.']);
}

// Perkecil ke sisi terpanjang 700 px, pertahankan transparansi.
$w = imagesx($src); $h = imagesy($src);
$scale = min(1, 700 / max($w, $h));
$nw = max(1, (int) round($w * $scale)); $nh = max(1, (int) round($h * $scale));
$img = imagecreatetruecolor($nw, $nh);
imagealphablending($img, false);
imagesavealpha($img, true);
imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
imagedestroy($src);
$w = $nw; $h = $nh;

// Sudah transparan? (cek tepi gambar)
$px = function (int $x, int $y) use ($img): array {
    $c = imagecolorat($img, $x, $y);
    return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255, ($c >> 24) & 127];
};
$border = [];
for ($x = 0; $x < $w; $x += max(1, intdiv($w, 60))) { $border[] = $px($x, 0); $border[] = $px($x, $h - 1); }
for ($y = 0; $y < $h; $y += max(1, intdiv($h, 60))) { $border[] = $px(0, $y); $border[] = $px($w - 1, $y); }
$transparentEdges = count(array_filter($border, fn($p) => $p[3] > 100)) > count($border) * .6;

if (!$transparentEdges) {
    // Warna latar = median warna tepi; hapus area berwarna sama yang tersambung ke tepi.
    $med = function (int $i) use ($border): int { $v = array_column($border, $i); sort($v); return $v[intdiv(count($v), 2)]; };
    $bg = [$med(0), $med(1), $med(2)];
    $dist = function (int $x, int $y) use ($px, $bg): float {
        [$r, $g, $b] = $px($x, $y);
        return sqrt(($r - $bg[0]) ** 2 + ($g - $bg[1]) ** 2 + ($b - $bg[2]) ** 2);
    };
    $tol = 38.0;
    $seen = array_fill(0, $w * $h, false);
    $queue = new SplQueue();
    for ($x = 0; $x < $w; $x++) { $queue->enqueue([$x, 0]); $queue->enqueue([$x, $h - 1]); }
    for ($y = 0; $y < $h; $y++) { $queue->enqueue([0, $y]); $queue->enqueue([$w - 1, $y]); }
    $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);
    while (!$queue->isEmpty()) {
        [$x, $y] = $queue->dequeue();
        $i = $y * $w + $x;
        if ($seen[$i]) continue;
        $seen[$i] = true;
        $d = $dist($x, $y);
        if ($d > $tol * 1.7) continue;
        if ($d <= $tol) {
            imagesetpixel($img, $x, $y, $clear);
            if ($x > 0) $queue->enqueue([$x - 1, $y]);
            if ($x < $w - 1) $queue->enqueue([$x + 1, $y]);
            if ($y > 0) $queue->enqueue([$x, $y - 1]);
            if ($y < $h - 1) $queue->enqueue([$x, $y + 1]);
        } else {
            // tepi lembut: makin mirip latar, makin transparan
            [$r, $g, $b] = $px($x, $y);
            $alpha = (int) round(127 * (1 - ($d - $tol) / ($tol * .7)));
            imagesetpixel($img, $x, $y, imagecolorallocatealpha($img, $r, $g, $b, max(0, min(127, $alpha))));
        }
    }
}

// Potong ke area yang terisi.
$minX = $w; $minY = $h; $maxX = -1; $maxY = -1;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        if (((imagecolorat($img, $x, $y) >> 24) & 127) < 115) {
            if ($x < $minX) $minX = $x; if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y; if ($y > $maxY) $maxY = $y;
        }
    }
}
if ($maxX < 0) {
    out(400, ['ok' => false, 'error' => 'Gambar kosong setelah latar dihapus.']);
}
$cw = $maxX - $minX + 1; $ch = $maxY - $minY + 1;
$crop = imagecreatetruecolor($cw, $ch);
imagealphablending($crop, false);
imagesavealpha($crop, true);
imagecopy($crop, $img, 0, 0, $minX, $minY, $cw, $ch);
imagedestroy($img);

if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    out(500, ['ok' => false, 'error' => 'Folder assets/img/up tidak bisa dibuat.']);
}
$key = 'up-' . bin2hex(random_bytes(4));
if (!imagepng($crop, $dir . '/' . $key . '.png', 9)) {
    out(500, ['ok' => false, 'error' => 'Gagal menyimpan gambar.']);
}
imagedestroy($crop);

$label = trim(preg_replace('/[^\p{L}\p{N} ]+/u', ' ', pathinfo((string) $f['name'], PATHINFO_FILENAME)));
$label = mb_substr($label !== '' ? $label : 'Bunga', 0, 24);
$entry = ['w' => $cw, 'h' => $ch, 'label' => $label];
flowers_write(function (array $d) use ($key, $entry) {
    $d['uploads'][$key] = $entry;
    return $d;
});

out(200, ['ok' => true, 'key' => $key, 'label' => $label, 'thumb' => img_url('up/' . $key . '.png'), 'w' => $cw, 'h' => $ch]);
