<?php
declare(strict_types=1);

/**
 * Koneksi PDO (lazy). Tabel dibuat otomatis bila belum ada —
 * tidak perlu seeder, migrasi, maupun import SQL.
 *
 * @throws RuntimeException bila config.php belum ada / koneksi gagal.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $CONFIG;
    if (!is_array($CONFIG) || empty($CONFIG['db'])) {
        throw new RuntimeException('config.php belum dibuat.');
    }

    $c   = $CONFIG['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset'] ?? 'utf8mb4');

    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
        ensure_schema($pdo);
    } catch (PDOException $ex) {
        $pdo = null;
        error_log('[undangan] DB error: ' . $ex->getMessage());
        throw new RuntimeException('Tidak dapat terhubung ke database.');
    }

    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS rsvp (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            attendance  ENUM('hadir','tidak_hadir','ragu') NOT NULL,
            guests      TINYINT UNSIGNED NOT NULL DEFAULT 0,
            message     TEXT NULL,
            invited_as  VARCHAR(100) NULL,
            ip          VARCHAR(45) NULL,
            user_agent  VARCHAR(255) NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    // Kolom tambahan (ditambahkan otomatis bila belum ada; aman untuk MySQL & MariaDB).
    ensure_column($pdo, 'rsvp', 'hidden', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'rsvp', 'pinned', 'TINYINT(1) NOT NULL DEFAULT 0');

    // Daftar tamu untuk link pribadi: nama disimpan di sini, link hanya memuat kode acak.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS guests (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code        CHAR(6) NOT NULL,
            name        VARCHAR(100) NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensure_column($pdo, 'guests', 'group_name', 'VARCHAR(50) NULL');
    // Status link: kapan dikirim (dicatat saat admin menekan Salin/Kirim), dibuka, & jumlah dibuka.
    ensure_column($pdo, 'guests', 'sent_at', 'DATETIME NULL');
    ensure_column($pdo, 'guests', 'opened_at', 'DATETIME NULL');
    ensure_column($pdo, 'guests', 'last_open_at', 'DATETIME NULL');
    ensure_column($pdo, 'guests', 'open_count', 'INT UNSIGNED NOT NULL DEFAULT 0');
    // RSVP yang dikirim lewat link pribadi menyimpan kodenya.
    ensure_column($pdo, 'rsvp', 'guest_code', 'CHAR(6) NULL');
}

/** Tambah kolom bila belum ada (tanpa migrasi manual). */
function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute([':t' => $table, ':c' => $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

/* ---------- Tamu & link pribadi ---------- */

/** Kode link valid: 6 karakter tanpa huruf/angka yang mudah tertukar (0/O, 1/I/L). */
function guest_code_valid(string $code): bool
{
    return (bool) preg_match('/^[2-9A-HJKMNP-Z]{6}$/', $code);
}

function guest_by_code(string $code): ?string
{
    $code = strtoupper($code);
    if (!guest_code_valid($code)) {
        return null;
    }
    $stmt = db()->prepare('SELECT name FROM guests WHERE code = :c');
    $stmt->execute([':c' => $code]);
    $name = $stmt->fetchColumn();
    return $name === false ? null : (string) $name;
}

/** Tambah tamu; mengembalikan kode barunya. */
function guest_create(string $name, ?string $group = null): string
{
    $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $stmt = db()->prepare('INSERT INTO guests (code, name, group_name) VALUES (:c, :n, :g)');
    for ($try = 0; $try < 8; $try++) {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        try {
            $stmt->execute([':c' => $code, ':n' => $name, ':g' => $group]);
            return $code;
        } catch (PDOException $ex) {
            if (($ex->errorInfo[1] ?? 0) !== 1062) { // selain kode kembar
                throw $ex;
            }
        }
    }
    throw new RuntimeException('Gagal membuat kode unik.');
}

function guests_all(): array
{
    return db()->query(
        'SELECT g.id, g.code, g.name, g.group_name, g.created_at, g.sent_at, g.opened_at, g.last_open_at, g.open_count,
                (SELECT r.attendance FROM rsvp r WHERE r.guest_code = g.code ORDER BY r.created_at DESC, r.id DESC LIMIT 1) AS rsvp_attendance,
                (SELECT r.guests FROM rsvp r WHERE r.guest_code = g.code ORDER BY r.created_at DESC, r.id DESC LIMIT 1) AS rsvp_guests
           FROM guests g ORDER BY g.created_at DESC, g.id DESC'
    )->fetchAll();
}

/** Catat bahwa tamu membuka linknya. */
function guest_mark_opened(string $code): void
{
    db()->prepare(
        'UPDATE guests SET open_count = open_count + 1, last_open_at = NOW(),
                opened_at = COALESCE(opened_at, NOW()) WHERE code = :c'
    )->execute([':c' => strtoupper($code)]);
}

/** Catat bahwa link sudah dikirim (pertama kali saja). */
function guest_mark_sent(int $id): void
{
    db()->prepare('UPDATE guests SET sent_at = COALESCE(sent_at, NOW()) WHERE id = :id')->execute([':id' => $id]);
}

/** Batalkan tanda terkirim (bila salah tekan). */
function guest_unmark_sent(int $id): void
{
    db()->prepare('UPDATE guests SET sent_at = NULL WHERE id = :id')->execute([':id' => $id]);
}

function guest_delete(int $id): void
{
    db()->prepare('DELETE FROM guests WHERE id = :id')->execute([':id' => $id]);
}

/** Ucapan terbaru yang berisi pesan, untuk ditampilkan di halaman. */
function latest_wishes(int $limit = 50): array
{
    $stmt = db()->prepare(
        "SELECT name, attendance, message, created_at, pinned
           FROM rsvp
          WHERE message IS NOT NULL AND message <> '' AND hidden = 0
          ORDER BY pinned DESC, created_at DESC, id DESC
          LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function guest_set_group(int $id, ?string $group): void
{
    db()->prepare('UPDATE guests SET group_name = :g WHERE id = :id')->execute([':g' => $group, ':id' => $id]);
}

/** Pengaturan kecil admin (gaya pesan per grup) di data/settings.json. */
function admin_settings(): array
{
    $file = APP_ROOT . '/data/settings.json';
    $d = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($d) ? $d : [];
}

function admin_settings_save(array $settings): bool
{
    $dir = APP_ROOT . '/data';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    return @file_put_contents($dir . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}
