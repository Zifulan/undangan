<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config.php';
$CONFIG     = is_file($configFile) ? require $configFile : null;
$CONTENT    = require __DIR__ . '/content.php';

require_once __DIR__ . '/db.php';

/** HTML-escape. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/** Format tanggal Indonesia, mis. "Sabtu, 12 Desember 2026". */
function tanggal_id(string $datetime, bool $withDay = true): string
{
    $days   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($datetime);
    $s  = date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withDay ? $days[(int) date('w', $ts)] . ', ' . $s : $s;
}

/** Waktu relatif singkat, mis. "5 menit lalu". */
function waktu_lalu(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'baru saja';
    if ($diff < 3600)    return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)   return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800)  return floor($diff / 86400) . ' hari lalu';
    return tanggal_id($datetime, false);
}

function attendance_label(string $a): string
{
    $map = ['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'ragu' => 'Masih ragu'];
    return $map[$a] ?? $a;
}

/** Base URL absolut, untuk meta og:image dsb. */
function base_url(): string
{
    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'zif.my.id';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($dir, -4) === '/api') {
        $dir = substr($dir, 0, -4);
    }
    return $scheme . '://' . $host . $dir;
}

require_once __DIR__ . '/ui.php';
