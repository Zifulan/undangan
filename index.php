<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$c     = $CONTENT;
$ev    = $c['event'] + [
    // Cadangan bila includes/content.php di server masih versi lama.
    'lat'      => -6.2104465,
    'lng'      => 106.7371328,
    'maps_url' => 'https://maps.google.com/?cid=11657600879784974533',
    'waze_url' => 'https://waze.com/ul?ll=-6.2104465,106.7371328&navigate=yes',
    'address'  => '',
    'city'     => 'Jakarta',
];
// Nama tamu hanya dari kode link pribadi (?u=KODE) yang dibuat di admin.
$guest = '';
$guestCode = '';
if (isset($_GET['u']) && is_string($_GET['u'])) {
    try {
        $guest = (string) guest_by_code(trim($_GET['u']));
        if ($guest !== '') {
            $guestCode = strtoupper(trim($_GET['u']));
            // Catat "dibuka" — kecuali admin sendiri yang sedang mengecek link.
            // Abaikan juga robot pratinjau link (WhatsApp, Telegram, dll.) yang membuka link saat ditempel.
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $isBot = $ua === '' || preg_match('/whatsapp|facebookexternalhit|facebot|telegram|twitterbot|slackbot|discordbot|linkedinbot|skypeuripreview|googlebot|bingbot|bot\b|crawler|spider|preview|curl|wget|python|headless/i', $ua);
            if (empty($_SESSION['admin']) && !$isBot) {
                guest_mark_opened($guestCode);
            }
        }
    } catch (Throwable $ex) {
        // database belum siap: tampilkan sebagai tamu umum
    }
}

// Mode atur bunga: hanya untuk admin yang sudah login (admin.php).
$editing = !empty($_GET['atur']) && !empty($_SESSION['admin']);
if ($editing) {
    header('Cache-Control: no-store');
}

$wishes  = [];
$dbReady = false;
try {
    $wishes  = latest_wishes(50);
    $dbReady = true;
} catch (Throwable $ex) {
    // Halaman tetap tampil walau database belum disiapkan.
}

$startTs  = strtotime($ev['start']);
$startIso = date('c', $startTs);
$gcal = 'https://calendar.google.com/calendar/render?' . http_build_query([
    'action'   => 'TEMPLATE',
    'text'     => $ev['name'] . ' ' . $c['groom']['nickname'] . ' & ' . $c['bride']['nickname'],
    'dates'    => gmdate('Ymd\THis\Z', $startTs) . '/' . gmdate('Ymd\THis\Z', strtotime($ev['end'])),
    'details'  => 'Akad Nikah ' . $c['groom']['name'] . ' & ' . $c['bride']['name'],
    'location' => $ev['venue'] . ($ev['address'] !== '' ? ', ' . $ev['address'] : ''),
    'ctz'      => 'Asia/Jakarta',
]);

// Mode "setelah akad": otomatis mulai hari berikutnya. Admin bisa melihat pratinjaunya dengan ?pasca=1.
$after = time() > strtotime(date('Y-m-d 23:59:59', $startTs))
    || (!empty($_GET['pasca']) && !empty($_SESSION['admin']));
// Foto hari-H: taruh file .jpg/.webp/.png di assets/img/akad/ (urut sesuai nama file).
$akadPhotos = [];
if ($after) {
    foreach (glob(APP_ROOT . '/assets/img/akad/*.{jpg,jpeg,JPG,JPEG,webp,png}', GLOB_BRACE) ?: [] as $f) {
        $akadPhotos[] = 'akad/' . basename($f);
    }
    sort($akadPhotos, SORT_NATURAL);
}

$G = $c['groom'];
$B = $c['bride'];
$sealText  = mb_substr($G['nickname'], 0, 1) . '&' . mb_substr($B['nickname'], 0, 1); // "F&G"

$pageTitle = 'Undangan ' . $ev['name'] . ' ' . $c['title'];
$pageDesc  = tanggal_id($ev['start']) . ' · ' . $ev['venue'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="#4A1C24">
    <meta name="robots" content="noindex, nofollow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e(rtrim((string) ($c['site_url'] ?? base_url()), '/') . '/' . img_url('og.jpg')) ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:alt" content="Undangan akad nikah <?= e($G['nickname']) ?> &amp; <?= e($B['nickname']) ?>, <?= e(tanggal_id($ev['start'])) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Caveat:wght@500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=35">
    <noscript><style>.reveal,.words .w{opacity:1;transform:none;filter:none}body.is-locked{overflow:auto;height:auto}.cover-hint{display:none}</style></noscript>
</head>
<body class="<?= $editing ? 'is-editing' : 'is-locked' ?>">
<?= doodle_defs() ?>

<main id="main">

<!-- ============ PEMBUKA: AMPLOP (tertutup → terbuka, tetap di halaman) ============ -->
<section class="cover stage<?= $editing ? ' is-opening is-open' : '' ?>" id="cover" aria-label="Undangan">
    <div class="cover-inner">
        <div class="stage-top">
            <div class="recipient">
                <p class="caps">Kepada Yth.</p>
                <p class="recipient-name"><?= $guest !== '' ? e($guest) : 'Tamu Undangan' ?></p>
            </div>
            <p class="arabic arabic-bismillah" lang="ar" dir="rtl">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
        </div>

        <div class="envelope" id="envelope">
            <?= flowers_layer('envelope') ?>
            <div class="env-back"></div>
            <div class="env-card">
                <span class="cc cc-tl"><svg class="card-corner" viewBox="0 0 40 40" aria-hidden="true"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="cc cc-tr"><svg class="card-corner" viewBox="0 0 40 40" aria-hidden="true"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="cc cc-bl"><svg class="card-corner" viewBox="0 0 40 40" aria-hidden="true"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="cc cc-br"><svg class="card-corner" viewBox="0 0 40 40" aria-hidden="true"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span>
                <p class="std" aria-label="Save the Date"><span class="script std-w">Save</span><span class="caps std-the">the</span><span class="script std-w">Date</span></p>
                <h1 class="caps card-names"><?= e($G['nickname']) ?> &amp; <?= e($B['nickname']) ?></h1>
                <svg class="card-rule" viewBox="0 0 160 14" aria-hidden="true"><path d="M4 7 H62 M98 7 H156"/><path class="r-heart" d="M80 12 c-6 -4 -9 -6 -9 -9 c0 -2.4 3.4 -3.4 5 -1 l4 3 l4 -3 c1.6 -2.4 5 -1.4 5 1 c0 3 -3 5 -9 9z"/><circle cx="66" cy="7" r="1.6"/><circle cx="94" cy="7" r="1.6"/></svg>
                <p class="caps caps-xs card-date"><?= e(date('d . m . Y', $startTs)) ?></p>
            </div>
            <?php if (has_img('kid-fatah-id.jpg') && has_img('kid-gege-id.jpg')): ?>
            <figure class="env-photo ep-l">
                <span class="idp-card"><img class="idp" src="<?= e(img_url('kid-fatah-id.jpg')) ?>" alt="Foto <?= e($G['nickname']) ?> semasa SD, SDN Joglo 01 Pagi" width="316" height="574" decoding="async"></span>
                <figcaption class="ep-name"><?= e($G['nickname']) ?></figcaption>
            </figure>
            <figure class="env-photo ep-r">
                <span class="idp-card"><img class="idp" src="<?= e(img_url('kid-gege-id.jpg')) ?>" alt="Foto <?= e($B['nickname']) ?> semasa SD, SDN Joglo 01 Pagi" width="316" height="570" decoding="async"></span>
                <figcaption class="ep-name"><?= e($B['nickname']) ?></figcaption>
            </figure>
            <?php endif; ?>
            <svg class="env-pocket" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <defs><linearGradient id="pkL" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#C3B985"/><stop offset="1" stop-color="#A99E68"/></linearGradient><linearGradient id="pkB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#BDB27D"/><stop offset="1" stop-color="#ACA16B"/></linearGradient></defs>
                <path class="pk" d="M0 0 L50 52 L100 0 L100 100 L0 100 Z"/>
                <path class="pk-bottom" d="M0 100 L50 52 L100 100 Z"/>
                <path class="pk-line" d="M0 100 L50 52 L100 100"/>
                <path class="pk-edge" d="M0 0 L50 52 L100 0"/>
            </svg>
            <div class="env-flap-wrap"><div class="env-flap"></div></div>
            <button type="button" class="env-seal" id="openInvitation" aria-label="Buka undangan">
                <?= wax_seal_img($sealText, 'wine') ?>
            </button>
        </div>

        <p class="cover-hint">Ketuk segel</p>
        <p class="scroll-cue" aria-hidden="true">gulir ke bawah</p>
    </div>
</section>

    <!-- ============ PEMBUKA & TANGGAL ============ -->
    <section class="section paper opening" id="pembuka">
        <?= flowers_layer('pembuka') ?>
        <?= doodle('crescent', 'dd dd-corner-r') ?>
        <div class="opening-grid">
            <div class="opening-text">
                <p class="arabic reveal" lang="ar" dir="rtl">السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
                <h2 class="script section-script words reveal">Ikatan Suci Kami</h2>
                <?php if ($after): ?>
                <p class="reveal">
                    Alhamdulillah, akad nikah kami telah berlangsung pada <?= e(tanggal_id($ev['start'])) ?>.
                    Terima kasih<?= $guest !== '' ? ', <strong class="guest-name">' . e($guest) . '</strong>,' : '' ?>
                    atas doa, kehadiran, dan kasih sayang yang sudah diberikan untuk kami.
                </p>
                <?php else: ?>
                <p class="reveal">
                    Dengan memohon rahmat dan ridho Allah Subhanahu wa Ta'ala, kami mengundang
                    <?= $guest !== '' ? '<strong class="guest-name">' . e($guest) . '</strong>' : 'Bapak/Ibu/Saudara/i' ?>
                    untuk hadir dan mendoakan akad nikah kami.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($akadPhotos): ?>
    <!-- ============ MOMEN AKAD (setelah hari-H) ============ -->
    <section class="section paper moments" id="momen">
        <h2 class="script section-script words reveal">Momen Akad</h2>
        <div class="moment-grid">
            <?php foreach ($akadPhotos as $i => $ph): ?>
                <a class="moment sketch reveal<?= $i % 3 === 1 ? ' tilt-s' : '' ?>" href="<?= e(img_url($ph)) ?>" target="_blank" rel="noopener">
                    <img src="<?= e(img_url($ph)) ?>" alt="Foto akad nikah <?= $i + 1 ?>" loading="lazy" decoding="async">
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ MEMPELAI ============ -->
    <section class="section wine couple" id="mempelai">
        <?= flowers_layer('mempelai') ?>
        <?= wave_edge('top', 1) ?>
        <?= doodle('hearts', 'dd-inline dd-sm reveal') ?>
        <h2 class="script section-script words reveal">Mempelai</h2>

        <div class="couple-grid">
            <article class="person reveal">
                <h3 class="script person-nick"><?= e($G['nickname']) ?></h3>
                <p class="caps person-name"><?= e($G['name']) ?></p>
                <p class="person-parents"><?= $G['parents'] ?></p>
            </article>

            <?php if (has_img('portrait.jpg')): ?>
            <picture class="portrait reveal">
                <source srcset="<?= e(img_url('portrait.webp')) ?>" type="image/webp">
                <img src="<?= e(img_url('portrait.jpg')) ?>" alt="Ilustrasi <?= e($G['nickname']) ?> &amp; <?= e($B['nickname']) ?>" width="900" height="1210" loading="lazy" decoding="async">
            </picture>
            <?php else: ?>
            <p class="script couple-amp reveal" aria-hidden="true">&amp;</p>
            <?php endif; ?>

            <article class="person reveal">
                <h3 class="script person-nick"><?= e($B['nickname']) ?></h3>
                <p class="caps person-name"><?= e($B['name']) ?></p>
                <p class="person-parents"><?= $B['parents'] ?></p>
            </article>
        </div>
    </section>


    <!-- ============ AKAD NIKAH ============ -->
    <section class="section wine event" id="acara">
        <?= flowers_layer('acara') ?>
        <?= wave_edge('bottom', 4) ?>
        <?= doodle('rings', 'dd-inline reveal') ?>
        <h2 class="script section-script words reveal"><?= e($ev['name']) ?></h2>

        <div class="event-grid">
            <div class="calendar-wrap reveal"><?= flowers_layer('calendar') ?><?= doodle_calendar($startTs) ?></div>

            <dl class="event-meta sketch tilt-r reveal">
                <?= flowers_layer('event') ?><span class="em-c em-tl" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="em-c em-tr" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="em-c em-bl" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span><span class="em-c em-br" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M3 30 V9 Q3 3 9 3 H30 M8 22 Q8 8 22 8 M12 15 q3 -3 6 0 q-3 3 -6 0z M30 3 q4 0 4 4 M3 30 q0 4 4 4"/></svg></span>
                <div>
                    <dt class="caps caps-xs">Hari &amp; Tanggal</dt>
                    <dd><?= e(tanggal_id($ev['start'])) ?></dd>
                </div>
                <div>
                    <dt class="caps caps-xs">Waktu</dt>
                    <dd><?= e($ev['time_text']) ?></dd>
                </div>
                <div>
                    <dt class="caps caps-xs">Tempat</dt>
                    <dd><?= e($ev['venue']) ?><?php if ($ev['address'] !== ''): ?><small class="dd-address"><?= e($ev['address']) ?></small><?php endif; ?></dd>
                </div>
            </dl>
        </div>

        <?php if ($after): ?>
        <p class="script countdown-done reveal">Alhamdulillah, telah sah</p>
        <?php else: ?>
        <div class="countdown reveal" id="countdown" data-target="<?= e($startIso) ?>" aria-live="polite">
            <div><span data-unit="days">0</span><small>Hari</small></div>
            <div><span data-unit="hours">0</span><small>Jam</small></div>
            <div><span data-unit="minutes">0</span><small>Menit</small></div>
            <div><span data-unit="seconds">0</span><small>Detik</small></div>
        </div>
        <p class="script countdown-done" id="countdownDone" hidden>Alhamdulillah, hari bahagia telah tiba</p>

        <div class="actions reveal">
            <a class="btn btn-ivory" id="calBtn" href="<?= e($gcal) ?>" data-ics="kalender.php" target="_blank" rel="noopener">Simpan ke Kalender</a>
            <a class="cal-alt" id="calAlt" href="kalender.php">iPhone / Outlook? Unduh file kalender</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============ LOKASI ============ -->
    <section class="section paper location" id="lokasi">
        <?= flowers_layer('lokasi') ?>
        <?= doodle('mosque', 'dd-inline reveal') ?>
        <h2 class="script section-script words reveal">Lokasi</h2>
        <p class="venue-name reveal"><?= e($ev['venue']) ?></p>
        <?php if ($ev['address'] !== ''): ?><p class="venue-address reveal"><?= e($ev['address']) ?></p><?php endif; ?>

        <div class="map sketch tilt-l reveal">
            <div id="map" class="map-canvas" role="region" aria-label="Peta lokasi <?= e($ev['venue']) ?>"
                 data-lat="<?= e((string) $ev['lat']) ?>" data-lng="<?= e((string) $ev['lng']) ?>"
                 data-label="<?= e($ev['venue']) ?>">
                <a class="map-fallback" href="<?= e($ev['maps_url']) ?>" target="_blank" rel="noopener">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    <strong><?= e($ev['venue']) ?></strong>
                    <span>Ketuk untuk membuka peta</span>
                </a>
            </div>
        </div>

        <div class="actions reveal">
            <a class="btn btn-wine" href="<?= e($ev['maps_url']) ?>" target="_blank" rel="noopener">Google Maps</a>
            <a class="btn btn-outline" href="<?= e($ev['waze_url']) ?>" target="_blank" rel="noopener">Waze</a>
        </div>
    </section>

    <!-- ============ RSVP & UCAPAN ============ -->
    <section class="section paper rsvp" id="rsvp">
        <?= flowers_layer('rsvp') ?>
        <figure class="ayat ayat-rsvp sketch tilt-s reveal">
            <?= doodle('quran', 'dd-inline') ?>
            <p class="arabic arabic-ayat" lang="ar" dir="rtl">
                وَمِنْ آيَاتِهِ أَنْ خَلَقَ لَكُمْ مِنْ أَنْفُسِكُمْ أَزْوَاجًا لِتَسْكُنُوا إِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَوَدَّةً وَرَحْمَةً ۚ إِنَّ فِي ذَٰلِكَ لَآيَاتٍ لِقَوْمٍ يَتَفَكَّرُونَ
            </p>
            <blockquote>
                “Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan
                untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan
                Dia menjadikan di antaramu rasa kasih dan sayang. Sungguh, pada yang demikian itu
                benar-benar terdapat tanda-tanda (kebesaran Allah) bagi kaum yang berpikir.”
            </blockquote>
            <figcaption class="caps caps-xs">QS. Ar-Rum : 21</figcaption>
        </figure>

        <div class="note sketch tilt-s reveal">
            <?= slot_img('floral-rsvp.png', 'floral floral-rsvp') ?>
            <div class="note-seal"><?= wax_seal_img($sealText, 'olive') ?></div>
            <?= doodle('envelope', 'dd dd-note-env') ?>
            <h2 class="script section-script"><?= $after ? 'Terima Kasih' : 'Konfirmasi' ?></h2>

            <?php if ($after): ?>
                <p class="muted after-note">Konfirmasi kehadiran sudah ditutup. Terima kasih untuk setiap doa dan ucapan yang sudah dikirimkan — semuanya kami baca dengan bahagia.</p>
            <?php elseif (!$dbReady): ?>
                <p class="notice">Formulir konfirmasi sedang disiapkan. Silakan kembali beberapa saat lagi.</p>
            <?php else: ?>
            <form class="rsvp-form" id="rsvpForm" action="api/rsvp.php" method="post" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="invited_as" value="<?= e($guest) ?>">
                <input type="hidden" name="u" value="<?= e($guestCode) ?>">
                <div class="hp" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <label class="field">
                    <span class="caps caps-xs">Nama</span>
                    <input type="text" name="name" maxlength="100" required autocomplete="name"
                           value="<?= e($guest) ?>" placeholder="Nama Anda">
                </label>

                <fieldset class="field">
                    <legend class="caps caps-xs">Kehadiran</legend>
                    <div class="choices">
                        <label class="choice"><input type="radio" name="attendance" value="hadir" required><span>Hadir</span></label>
                        <label class="choice"><input type="radio" name="attendance" value="tidak_hadir"><span>Tidak Hadir</span></label>
                        <label class="choice"><input type="radio" name="attendance" value="ragu"><span>Masih Ragu</span></label>
                    </div>
                </fieldset>

                <label class="field" id="guestsField" hidden>
                    <span class="caps caps-xs">Jumlah tamu</span>
                    <select name="guests">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> orang</option>
                        <?php endfor; ?>
                    </select>
                </label>

                <label class="field">
                    <span class="caps caps-xs">Ucapan &amp; doa <em>(opsional)</em></span>
                    <textarea name="message" rows="4" maxlength="500" placeholder="Tuliskan ucapan dan doa terbaik…"></textarea>
                </label>

                <button type="submit" class="btn btn-wine btn-block">Kirim</button>
                <p class="form-status" id="formStatus" role="status" aria-live="polite"></p>
            </form>
            <div class="rsvp-thanks" id="rsvpThanks" hidden>
                <p class="script thanks-title">Terima kasih</p>
                <p class="muted">Konfirmasi Anda sudah kami terima.</p>
                <button type="button" class="link-btn" id="rsvpAgain">Kirim ucapan lagi</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="wishes reveal">
            <?= doodle('bouquet', 'dd-inline') ?>
            <h3 class="script wishes-title">Ucapan &amp; Doa <span id="wishCount"><?= count($wishes) ?: '' ?></span></h3>
            <ul class="wish-list" id="wishList">
                <?php foreach ($wishes as $w): ?>
                    <li class="wish<?= !empty($w['pinned']) ? ' is-pinned' : '' ?>">
                        <div class="wish-head">
                            <strong><?= e($w['name']) ?></strong>
                            <span class="badge"><?= e(attendance_label($w['attendance'])) ?></span>
                        </div>
                        <p><?= nl2br(e($w['message'])) ?></p>
                        <time datetime="<?= e(date('c', strtotime($w['created_at']))) ?>"><?= e(waktu_lalu($w['created_at'])) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="muted wish-empty" id="wishEmpty" <?= $wishes ? 'hidden' : '' ?>>Belum ada ucapan.</p>
            <button type="button" class="link-btn wish-more" id="wishMore"<?= count($wishes) > 6 ? '' : ' hidden' ?>>Lihat semua ucapan</button>
        </div>
    </section>

    <!-- ============ PENUTUP ============ -->
    <section class="section wine closing">
        <?= flowers_layer('penutup') ?>
        <?= wave_edge('top', 5) ?>
        <?= doodle('hearts', 'dd dd-corner-l') ?>
        <?= doodle('crescent', 'dd dd-corner-r') ?>


        <p class="arabic reveal" lang="ar" dir="rtl">بَارَكَ اللَّهُ لَكَ وَبَارَكَ عَلَيْكَ وَجَمَعَ بَيْنَكُمَا فِي خَيْرٍ</p>
        <p class="closing-trans reveal"><em>“Semoga Allah memberkahimu dalam suka maupun duka, dan mempersatukan kalian berdua dalam kebaikan.”</em><br><span class="caps caps-xs">HR. Abu Dawud &amp; Tirmidzi</span></p>

        <p class="arabic reveal" lang="ar" dir="rtl">وَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
        <p class="script closing-names words reveal"><?= e($G['nickname']) ?> &amp; <?= e($B['nickname']) ?></p>
        <?php if (has_img('spray-a.png')): ?>
        <picture class="closing-spray reveal" aria-hidden="true">
            <source srcset="<?= e(img_url('spray-a.webp')) ?>" type="image/webp">
            <img src="<?= e(img_url('spray-a.png')) ?>" alt="" loading="lazy" decoding="async">
        </picture>
        <?php endif; ?>
    </section>

</main>

<?php if ($editing): ?>
<div class="fx-bar" id="fxBar">
    <div class="fx-status" id="fxStatus"></div>
    <div class="fx-row fx-top">
        <select id="fxArea" aria-label="Bagian">
            <?php foreach (FLOWER_AREA_LABELS as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button type="button" id="fxUndo">Urungkan</button>
        <button type="button" class="fx-primary" id="fxSave">Simpan</button>
        <a class="fx-exit" href="admin.php">Keluar</a>
    </div>
    <div class="fx-row fx-tools">
        <button type="button" id="fxBigger" title="Perbesar">＋</button>
        <button type="button" id="fxSmaller" title="Perkecil">－</button>
        <button type="button" id="fxRotL" title="Putar kiri">⟲</button>
        <button type="button" id="fxRotR" title="Putar kanan">⟳</button>
        <button type="button" id="fxFlip">Cermin</button>
        <button type="button" id="fxLayer">Depan/Belakang</button>
        <button type="button" id="fxUp">Paling atas</button>
        <button type="button" class="fx-danger" id="fxDel">Hapus</button>
        <button type="button" id="fxDone">Selesai</button>
    </div>
    <div class="fx-row fx-chips" id="fxChips"></div>
    <div class="fx-row fx-tabs" id="fxTabs"></div>
    <div class="fx-row fx-strip" id="fxStrip" aria-label="Tambah bunga"></div>
    <input type="file" id="fxFile" accept="image/png,image/jpeg,image/webp" hidden>
</div>
<script>
window.FLOWER_EDITOR = <?= json_encode([
    'api'    => 'api/flowers.php',
    'csrf'   => csrf_token(),
    'upload' => 'api/flowers-upload.php',
    'assets' => array_map(fn($a) => ['label' => $a['label'], 'group' => $a['group'], 'thumb' => img_url($a['src'] . '.png')], flower_assets()),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e('assets/js/flower-editor.js?v=' . filemtime(APP_ROOT . '/assets/js/flower-editor.js')) ?>" defer></script>
<?php endif; ?>
<script src="assets/js/main.js?v=16" defer></script>
</body>
</html>
