<?php
declare(strict_types=1);

/** Simpan susunan bunga dari editor (khusus admin). */
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'Metode tidak diizinkan.']);
}
if (empty($_SESSION['admin'])) {
    reply(403, ['ok' => false, 'error' => 'Silakan login admin dulu.']);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body) || !csrf_valid($body['csrf'] ?? null)) {
    reply(403, ['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman.']);
}

$saved = 0;
foreach ((array) ($body['sets'] ?? []) as $set) {
    $area    = (string) ($set['area'] ?? '');
    $variant = isset($set['variant']) ? (string) $set['variant'] : null;
    if (!array_key_exists($area, FLOWER_AREAS)) {
        reply(400, ['ok' => false, 'error' => 'Area tidak dikenal.']);
    }
    if (FLOWER_AREAS[$area] && !in_array($variant, ['m', 'd'], true)) {
        reply(400, ['ok' => false, 'error' => 'Varian tidak dikenal.']);
    }
    $items = flowers_sanitize($set['items'] ?? null);
    if ($items === null) {
        reply(400, ['ok' => false, 'error' => 'Data bunga tidak valid.']);
    }
    if (!flowers_save($area, FLOWER_AREAS[$area] ? $variant : null, $items)) {
        reply(500, ['ok' => false, 'error' => 'Gagal menyimpan. Pastikan folder data/ bisa ditulis.']);
    }
    $saved++;
}

reply(200, ['ok' => true, 'saved' => $saved]);
