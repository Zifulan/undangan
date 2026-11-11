<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$adminPass = is_array($CONFIG) ? (string) ($CONFIG['admin_password'] ?? '') : '';
$error     = '';

// ---------- Login / logout ----------
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['password'])) {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, silakan coba lagi.';
    } elseif ($adminPass === '' || $adminPass === 'GANTI_PASSWORD_ADMIN') {
        $error = 'Password admin belum diatur di config.php.';
    } elseif (hash_equals($adminPass, (string) $_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        usleep(600000);
        $error = 'Password salah.';
    }
}

$authed = !empty($_SESSION['admin']);

// ---------- Aksi (hanya admin) ----------
if ($authed) {
    try {
        // Hapus entri
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete'])) {
            if (csrf_valid($_POST['csrf'] ?? null)) {
                db()->prepare('DELETE FROM rsvp WHERE id = :id')->execute([':id' => (int) $_POST['delete']]);
            }
            header('Location: admin.php');
            exit;
        }

        // Tambah tamu (satu nama per baris)
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['add_guests'])) {
            if (csrf_valid($_POST['csrf'] ?? null)) {
                foreach (preg_split('/\R/u', (string) $_POST['add_guests']) as $line) {
                    $name = trim(preg_replace('/\s+/u', ' ', $line));
                    if ($name !== '') {
                        guest_create(mb_substr($name, 0, 100));
                    }
                }
            }
            header('Location: admin.php#tamu');
            exit;
        }

        // Hapus tamu
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_guest'])) {
            if (csrf_valid($_POST['csrf'] ?? null)) {
                guest_delete((int) $_POST['delete_guest']);
            }
            header('Location: admin.php#tamu');
            exit;
        }

        // Ekspor CSV
        if (isset($_GET['export'])) {
            $rows = db()->query('SELECT id, name, attendance, guests, message, invited_as, created_at FROM rsvp ORDER BY created_at ASC')->fetchAll();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="rsvp-' . date('Ymd-His') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['No', 'Nama', 'Kehadiran', 'Jumlah Tamu', 'Ucapan', 'Link Undangan (to=)', 'Waktu']);
            foreach ($rows as $i => $r) {
                fputcsv($out, [$i + 1, $r['name'], attendance_label($r['attendance']), $r['guests'], $r['message'], $r['invited_as'], $r['created_at']]);
            }
            fclose($out);
            exit;
        }

        $rows  = db()->query('SELECT * FROM rsvp ORDER BY created_at DESC, id DESC')->fetchAll();
        $guestList = guests_all();
        $ev = $CONTENT['event'];
        $waVars = [
            '{tanggal}' => tanggal_id($ev['start']),
            '{jam}'     => date('H.i', strtotime($ev['start'])) . ' WIB',
            '{tempat}'  => $ev['venue'],
            '{alamat}'  => $ev['address'] ?? '',
        ];
        $waTemplates = $CONTENT['wa_messages'] ?? ['hangat' => $CONTENT['wa_message'] ?? '{link}'];
        $waLabels = array_intersect_key(['hangat' => 'Hangat', 'formal' => 'Formal', 'santai' => 'Santai'], $waTemplates);
        $siteUrl   = rtrim((string) ($CONTENT['site_url'] ?? base_url()), '/');
        $replied   = [];
        foreach ($rows as $r) {
            if ($r['invited_as']) {
                $replied[mb_strtolower($r['invited_as'])] = true;
            }
        }
        $stats = ['hadir' => 0, 'tidak_hadir' => 0, 'ragu' => 0, 'guests' => 0];
        foreach ($rows as $r) {
            $stats[$r['attendance']]++;
            $stats['guests'] += (int) $r['guests'];
        }
    } catch (Throwable $ex) {
        $dbError = $ex->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin RSVP · <?= e($CONTENT['title']) ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <style>
        :root { --ivory:#FAF6EE; --ivory-2:#F4EFE4; --espresso:#4A1C24; --brass:#A3AA87; --ink:#6C5A5C; --line:#DDD2C0; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--ivory); color:var(--espresso); font:15px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif; }
        .wrap { max-width:1100px; margin:0 auto; padding:24px 16px 48px; }
        header { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-bottom:20px; }
        h1 { margin:0; font:500 1.5rem/1.2 Georgia,serif; }
        a.btn, button.btn { display:inline-block; padding:.55rem 1rem; border:1px solid var(--espresso); border-radius:999px; background:var(--espresso); color:var(--ivory); text-decoration:none; font-size:.85rem; cursor:pointer; }
        a.btn.ghost { background:transparent; color:var(--espresso); }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:24px; }
        .stat { background:var(--ivory-2); border:1px solid var(--line); padding:14px 16px; }
        .stat b { display:block; font:500 1.8rem/1 Georgia,serif; }
        .stat span { font-size:.75rem; text-transform:uppercase; letter-spacing:.12em; color:var(--ink); }
        .table-wrap { overflow-x:auto; border:1px solid var(--line); background:#fff; }
        table { width:100%; border-collapse:collapse; min-width:720px; }
        th, td { padding:10px 12px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; }
        th { background:var(--ivory-2); font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:var(--ink); }
        td.msg { max-width:360px; white-space:pre-wrap; overflow-wrap:anywhere; }
        td small { color:var(--ink); }
        .del { background:none; border:0; color:#9B2C1F; cursor:pointer; font-size:.8rem; padding:0; }
        .login { max-width:340px; margin:12vh auto; padding:28px 24px; background:var(--ivory-2); border:1px solid var(--line); }
        .login input { width:100%; padding:.7rem .8rem; border:1px solid var(--line); margin:8px 0 14px; font-size:1rem; }
        .err { color:#9B2C1F; }
        .empty { padding:24px; text-align:center; color:var(--ink); }
        .guests { margin:0 0 28px; padding:18px; background:var(--ivory-2); border:1px solid var(--line); }
        .guests h2 { margin:0 0 4px; font:500 1.2rem/1.2 Georgia,serif; }
        .guests p.hint { margin:0 0 12px; color:var(--ink); font-size:.85rem; }
        .guests textarea { width:100%; min-height:90px; padding:.6rem .7rem; border:1px solid var(--line); font:inherit; }
        .glist td { vertical-align:middle; }
        .glist code { font-size:.85rem; background:var(--ivory-2); padding:.1rem .35rem; }
        .mini { padding:.3rem .65rem; border:1px solid var(--espresso); border-radius:999px; background:#fff; color:var(--espresso); font-size:.78rem; cursor:pointer; text-decoration:none; white-space:nowrap; }
        .mini.wa { background:#1F7A4D; border-color:#1F7A4D; color:#fff; }
        .tag-ok { color:#1F7A4D; font-size:.78rem; }
        .toast { position:fixed; left:50%; bottom:20px; transform:translateX(-50%); background:var(--espresso); color:var(--ivory); padding:.5rem 1rem; border-radius:999px; font-size:.85rem; opacity:0; transition:opacity .2s; pointer-events:none; }
        .toast.on { opacity:1; }
        .wa-style { display:flex; flex-wrap:wrap; gap:.4rem 1rem; align-items:center; margin:0 0 .8rem; font-size:.9rem; }
        .wa-style label { cursor:pointer; }
        .wa-preview { white-space:pre-wrap; background:#E7F6EC; border:1px solid #BFE3CC; border-radius:10px; padding:.8rem 1rem; font:14px/1.45 system-ui,sans-serif; max-width:460px; margin:0 0 1rem; }
    </style>
</head>
<body>
<?php if (!$authed): ?>
    <form class="login" method="post">
        <h1>Admin RSVP</h1>
        <p style="color:var(--ink);margin:.25rem 0 1rem"><?= e($CONTENT['title']) ?></p>
        <?php if ($CONFIG === null): ?>
            <p class="err">File <code>config.php</code> belum dibuat. Salin dari <code>config.example.php</code>.</p>
        <?php endif; ?>
        <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="pw">Password</label>
        <input id="pw" type="password" name="password" required autofocus autocomplete="current-password">
        <button class="btn" type="submit">Masuk</button>
    </form>
<?php else: ?>
    <div class="wrap">
        <header>
            <h1>RSVP · <?= e($CONTENT['title']) ?></h1>
            <div>
                <a class="btn" href="index.php?atur=1">Atur Bunga</a>
                <a class="btn" href="admin.php?export=1">Ekspor CSV</a>
                <a class="btn ghost" href="admin.php?logout=1">Keluar</a>
            </div>
        </header>

        <?php if (!empty($dbError)): ?>
            <p class="err">Database error: <?= e($dbError) ?> — periksa pengaturan di <code>config.php</code>.</p>
        <?php else: ?>
            <section class="guests" id="tamu">
                <h2>Link Undangan</h2>
                <p class="hint">Tulis nama tamu, satu per baris. Setiap tamu mendapat link pribadi berkode acak, jadi nama tidak terlihat di link.</p>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <textarea name="add_guests" placeholder="Prabowo Teddy&#10;Keluarga Prabowo Teddy" required></textarea>
                    <p style="margin:.6rem 0 1rem"><button class="btn" type="submit">Buat link</button></p>
                </form>
                <?php if ($guestList): ?>
                <p class="wa-style">Gaya pesan:
                    <?php foreach ($waLabels as $k => $label): ?>
                        <label><input type="radio" name="wa_style" value="<?= e($k) ?>"<?= $k === 'hangat' ? ' checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                    <button class="mini" type="button" id="waPreviewBtn">Lihat contoh</button>
                </p>
                <pre class="wa-preview" id="waPreview" hidden></pre>
                <div class="table-wrap">
                    <table class="glist">
                        <thead><tr><th>Nama</th><th>Link</th><th>Kirim</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($guestList as $g):
                            $link = $siteUrl . '/?u=' . $g['code'];
                            $vars = ['{nama}' => $g['name'], '{link}' => $link] + $waVars;
                            $msgs = array_map(fn($t) => strtr((string) $t, $vars), $waTemplates);
                        ?>
                            <tr>
                                <td><?= e($g['name']) ?><?php if (!empty($replied[mb_strtolower($g['name'])])): ?><br><span class="tag-ok">✓ sudah konfirmasi</span><?php endif; ?></td>
                                <td><code><?= e($link) ?></code></td>
                                <td style="white-space:nowrap">
                                    <button class="mini" type="button" data-copy="<?= e($link) ?>">Salin link</button>
                                    <button class="mini wa-copy" type="button"<?php foreach ($msgs as $k => $m): ?> data-msg-<?= e($k) ?>="<?= e($m) ?>"<?php endforeach; ?>>Salin pesan</button>
                                    <a class="mini wa" target="_blank" rel="noopener"<?php foreach ($msgs as $k => $m): ?> data-href-<?= e($k) ?>="https://wa.me/?text=<?= e(rawurlencode($m)) ?>"<?php endforeach; ?>>Kirim WA</a>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Hapus link untuk tamu ini? Link lama tidak akan menampilkan namanya lagi.')">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <button class="del" name="delete_guest" value="<?= (int) $g['id'] ?>">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>

            <div class="stats">
                <div class="stat"><b><?= count($rows) ?></b><span>Total respons</span></div>
                <div class="stat"><b><?= $stats['hadir'] ?></b><span>Hadir</span></div>
                <div class="stat"><b><?= $stats['guests'] ?></b><span>Total tamu hadir</span></div>
                <div class="stat"><b><?= $stats['tidak_hadir'] ?></b><span>Tidak hadir</span></div>
                <div class="stat"><b><?= $stats['ragu'] ?></b><span>Masih ragu</span></div>
            </div>

            <div class="table-wrap">
                <?php if (!$rows): ?>
                    <p class="empty">Belum ada respons.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Nama</th><th>Kehadiran</th><th>Tamu</th><th>Ucapan</th><th>Waktu</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <?= e($r['name']) ?>
                                <?php if ($r['invited_as'] && $r['invited_as'] !== $r['name']): ?>
                                    <br><small>link: <?= e($r['invited_as']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e(attendance_label($r['attendance'])) ?></td>
                            <td><?= (int) $r['guests'] ?: '–' ?></td>
                            <td class="msg"><?= e($r['message'] ?? '') ?></td>
                            <td><small><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></small></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Hapus entri dari <?= e(addslashes($r['name'])) ?>?')">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <button class="del" name="delete" value="<?= (int) $r['id'] ?>">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<div class="toast" id="toast">Tersalin ✓</div>
<script>
var styleOf = function () { var r = document.querySelector('input[name=wa_style]:checked'); return r ? r.value : 'hangat'; };
var applyStyle = function () {
    var st = styleOf();
    try { localStorage.setItem('waStyle', st); } catch (e) {}
    document.querySelectorAll('.wa-copy').forEach(function (b) { b.setAttribute('data-copy', b.getAttribute('data-msg-' + st) || ''); });
    document.querySelectorAll('a.wa').forEach(function (a) { a.href = a.getAttribute('data-href-' + st) || '#'; });
    var pv = document.getElementById('waPreview'), first = document.querySelector('.wa-copy');
    if (pv && first) pv.textContent = first.getAttribute('data-copy');
};
try { var saved = localStorage.getItem('waStyle'); var r = saved && document.querySelector('input[name=wa_style][value="' + saved + '"]'); if (r) r.checked = true; } catch (e) {}
document.querySelectorAll('input[name=wa_style]').forEach(function (r) { r.addEventListener('change', applyStyle); });
var pvBtn = document.getElementById('waPreviewBtn');
if (pvBtn) pvBtn.addEventListener('click', function () { var pv = document.getElementById('waPreview'); pv.hidden = !pv.hidden; });
applyStyle();
document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
        var t = b.getAttribute('data-copy'), toast = document.getElementById('toast');
        var done = function () { toast.classList.add('on'); setTimeout(function () { toast.classList.remove('on'); }, 1400); };
        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(done); }
        else { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); document.execCommand('copy'); a.remove(); done(); }
    });
});
</script>
</body>
</html>
