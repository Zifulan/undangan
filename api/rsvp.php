<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Metode tidak diizinkan.']);
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    respond(403, ['ok' => false, 'error' => 'Sesi kedaluwarsa. Silakan muat ulang halaman.']);
}

// Honeypot: field tersembunyi yang hanya diisi bot.
if (!empty($_POST['website'])) {
    respond(200, ['ok' => true, 'wish' => null]);
}

// Batasi pengiriman beruntun dari sesi yang sama.
$last = $_SESSION['rsvp_last'] ?? 0;
if (time() - $last < 20) {
    respond(429, ['ok' => false, 'error' => 'Mohon tunggu sebentar sebelum mengirim lagi.']);
}

$name       = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')));
$attendance = (string) ($_POST['attendance'] ?? '');
$guests     = (int) ($_POST['guests'] ?? 0);
$message    = trim((string) ($_POST['message'] ?? ''));
$invitedAs  = trim((string) ($_POST['invited_as'] ?? ''));

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Nama wajib diisi (2–100 karakter).';
}
if (!in_array($attendance, ['hadir', 'tidak_hadir', 'ragu'], true)) {
    $errors[] = 'Silakan pilih konfirmasi kehadiran.';
}
if ($attendance === 'hadir') {
    if ($guests < 1 || $guests > 5) {
        $errors[] = 'Jumlah tamu 1–5 orang.';
    }
} else {
    $guests = 0;
}
if (mb_strlen($message) > 500) {
    $errors[] = 'Ucapan maksimal 500 karakter.';
}
if ($errors) {
    respond(422, ['ok' => false, 'error' => implode(' ', $errors)]);
}

try {
    $stmt = db()->prepare(
        'INSERT INTO rsvp (name, attendance, guests, message, invited_as, ip, user_agent)
         VALUES (:name, :attendance, :guests, :message, :invited_as, :ip, :ua)'
    );
    $stmt->execute([
        ':name'       => $name,
        ':attendance' => $attendance,
        ':guests'     => $guests,
        ':message'    => $message !== '' ? $message : null,
        ':invited_as' => $invitedAs !== '' ? mb_substr($invitedAs, 0, 100) : null,
        ':ip'         => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':ua'         => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
} catch (Throwable $ex) {
    error_log('[undangan] RSVP insert failed: ' . $ex->getMessage());
    respond(500, ['ok' => false, 'error' => 'Maaf, terjadi kendala. Silakan coba lagi nanti.']);
}

$_SESSION['rsvp_last'] = time();

respond(200, [
    'ok'   => true,
    'wish' => $message === '' ? null : [
        'name'       => $name,
        'attendance' => attendance_label($attendance),
        'message'    => $message,
        'when'       => 'baru saja',
    ],
]);
