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
