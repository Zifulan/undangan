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
function guest_create(string $name): string
{
    $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $stmt = db()->prepare('INSERT INTO guests (code, name) VALUES (:c, :n)');
    for ($try = 0; $try < 8; $try++) {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        try {
            $stmt->execute([':c' => $code, ':n' => $name]);
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
    return db()->query('SELECT id, code, name, created_at FROM guests ORDER BY created_at DESC, id DESC')->fetchAll();
}

function guest_delete(int $id): void
{
    db()->prepare('DELETE FROM guests WHERE id = :id')->execute([':id' => $id]);
}

/** Ucapan terbaru yang berisi pesan, untuk ditampilkan di halaman. */
function latest_wishes(int $limit = 50): array
{
    $stmt = db()->prepare(
        "SELECT name, attendance, message, created_at
           FROM rsvp
          WHERE message IS NOT NULL AND message <> ''
          ORDER BY created_at DESC, id DESC
          LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
