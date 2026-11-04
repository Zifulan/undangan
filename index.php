<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$c     = $CONTENT;
$ev    = $c['event'];
$guest = trim(mb_substr((string) ($_GET['to'] ?? ''), 0, 80));

$wishes  = [];
$dbReady = false;
try {
    $wishes  = latest_wishes(50);
    $dbReady = true;
} catch (Throwable $ex) {
    // Halaman tetap tampil walau database belum disiapkan.
}

$startIso = date('c', strtotime($ev['start']));
$gcal = 'https://calendar.google.com/calendar/render?' . http_build_query([
    'action'   => 'TEMPLATE',
    'text'     => $ev['name'] . ' ' . $c['groom']['nickname'] . ' & ' . $c['bride']['nickname'],
    'dates'    => gmdate('Ymd\THis\Z', strtotime($ev['start'])) . '/' . gmdate('Ymd\THis\Z', strtotime($ev['end'])),
    'details'  => 'Akad Nikah ' . $c['groom']['name'] . ' & ' . $c['bride']['name'],
    'location' => $ev['venue'],
    'ctz'      => 'Asia/Jakarta',
]);

$pageTitle = 'Undangan ' . $ev['name'] . ' ' . $c['title'];
$pageDesc  = tanggal_id($ev['start']) . ' · ' . $ev['venue'];

/** Ornamen pemisah: belah ketupat dengan dua titik. */
function ornament(string $class = ''): string
{
    return '<div class="ornament ' . $class . '" aria-hidden="true">'
        . '<span class="line"></span>'
        . '<svg viewBox="0 0 48 16" width="48" height="16" fill="currentColor">'
        . '<circle cx="9" cy="8" r="1.4"/>'
        . '<path d="M24 1.5 30.5 8 24 14.5 17.5 8Z" fill="none" stroke="currentColor" stroke-width="1"/>'
        . '<path d="M24 5.2 26.8 8 24 10.8 21.2 8Z"/>'
        . '<circle cx="39" cy="8" r="1.4"/></svg>'
        . '<span class="line"></span></div>';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="#1A110B">
    <meta name="robots" content="noindex, nofollow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e(base_url()) ?>/assets/img/og.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <noscript><style>.reveal{opacity:1;transform:none}.cover{display:none}body.is-locked{overflow:auto;height:auto}</style></noscript>
</head>
<body class="is-locked">

<!-- ============ COVER ============ -->
<section class="cover" id="cover" aria-label="Sampul undangan">
    <div class="frame" aria-hidden="true"></div>
    <div class="cover-inner">
        <p class="eyebrow">Undangan <?= e($ev['name']) ?></p>
        <h1 class="names-script">
            <?= e($c['groom']['nickname']) ?>
            <span class="amp">&amp;</span>
            <?= e($c['bride']['nickname']) ?>
        </h1>
        <p class="cover-date"><?= e(tanggal_id($ev['start'])) ?></p>

        <?= ornament('on-dark') ?>

        <div class="recipient">
            <p class="recipient-label">Kepada Yth.<br>Bapak/Ibu/Saudara/i</p>
            <p class="recipient-name"><?= $guest !== '' ? e($guest) : 'Tamu Undangan' ?></p>
        </div>

        <button type="button" class="btn btn-light" id="openInvitation">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 7l9 6 9-6"/><rect x="3" y="5" width="18" height="14" rx="1.5"/></svg>
            Buka Undangan
        </button>
    </div>
</section>

<main id="main">

    <!-- ============ HERO ============ -->
    <section class="hero section-dark">
        <div class="frame" aria-hidden="true"></div>
        <p class="arabic arabic-lg reveal" lang="ar" dir="rtl">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
        <p class="eyebrow reveal">Akad Nikah</p>
        <h2 class="names-script reveal">
            <?= e($c['groom']['nickname']) ?>
            <span class="amp">&amp;</span>
            <?= e($c['bride']['nickname']) ?>
        </h2>
        <p class="hero-date reveal"><?= e(tanggal_id($ev['start'])) ?></p>
        <div class="scroll-hint" aria-hidden="true"><span></span></div>
    </section>

    <!-- ============ SALAM & AYAT ============ -->
    <section class="section intro">
        <p class="arabic reveal" lang="ar" dir="rtl">السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
        <p class="lead reveal">
            Dengan memohon rahmat dan ridho Allah Subhanahu wa Ta'ala, kami bermaksud
            menyelenggarakan akad nikah putra-putri kami.
        </p>

        <?= ornament('reveal') ?>

        <figure class="ayat reveal">
            <p class="arabic arabic-ayat" lang="ar" dir="rtl">
                وَمِنْ آيَاتِهِ أَنْ خَلَقَ لَكُمْ مِنْ أَنْفُسِكُمْ أَزْوَاجًا لِتَسْكُنُوا إِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَوَدَّةً وَرَحْمَةً ۚ إِنَّ فِي ذَٰلِكَ لَآيَاتٍ لِقَوْمٍ يَتَفَكَّرُونَ
            </p>
            <blockquote>
                “Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan
                untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan
                Dia menjadikan di antaramu rasa kasih dan sayang. Sungguh, pada yang demikian itu
                benar-benar terdapat tanda-tanda (kebesaran Allah) bagi kaum yang berpikir.”
            </blockquote>
            <figcaption>QS. Ar-Rum : 21</figcaption>
        </figure>
    </section>

    <!-- ============ MEMPELAI ============ -->
    <section class="section couple" id="mempelai">
        <p class="eyebrow reveal">Mempelai</p>

        <article class="person reveal">
            <div class="monogram" aria-hidden="true"><?= e(mb_substr($c['groom']['nickname'], 0, 1)) ?></div>
            <h3 class="person-name"><?= e($c['groom']['name']) ?></h3>
            <p class="person-parents"><?= $c['groom']['parents'] ?></p>
        </article>

        <div class="couple-amp reveal" aria-hidden="true">&amp;</div>

        <article class="person reveal">
            <div class="monogram" aria-hidden="true"><?= e(mb_substr($c['bride']['nickname'], 0, 1)) ?></div>
            <h3 class="person-name"><?= e($c['bride']['name']) ?></h3>
            <p class="person-parents"><?= $c['bride']['parents'] ?></p>
        </article>
    </section>

    <!-- ============ ACARA ============ -->
    <section class="section section-dark event" id="acara">
        <div class="frame" aria-hidden="true"></div>
        <p class="eyebrow reveal">Insya Allah akan dilaksanakan pada</p>

        <div class="event-card reveal">
            <h3 class="event-title"><?= e($ev['name']) ?></h3>
            <?= ornament('on-dark') ?>
            <dl class="event-meta">
                <div>
                    <dt>Hari &amp; Tanggal</dt>
                    <dd><?= e(tanggal_id($ev['start'])) ?></dd>
                </div>
                <div>
                    <dt>Waktu</dt>
                    <dd><?= e($ev['time_text']) ?></dd>
                </div>
                <div>
                    <dt>Tempat</dt>
                    <dd><?= e($ev['venue']) ?></dd>
                </div>
            </dl>
        </div>

        <div class="countdown reveal" id="countdown" data-target="<?= e($startIso) ?>" aria-live="polite">
            <div><span data-unit="days">0</span><small>Hari</small></div>
            <div><span data-unit="hours">0</span><small>Jam</small></div>
            <div><span data-unit="minutes">0</span><small>Menit</small></div>
            <div><span data-unit="seconds">0</span><small>Detik</small></div>
        </div>
        <p class="countdown-done" id="countdownDone" hidden>Alhamdulillah, hari bahagia telah tiba.</p>

        <div class="actions reveal">
            <a class="btn btn-light" href="<?= e($gcal) ?>" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="1.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                Simpan ke Kalender
            </a>
        </div>
    </section>

    <!-- ============ LOKASI ============ -->
    <section class="section location" id="lokasi">
        <p class="eyebrow reveal">Lokasi</p>
        <h3 class="section-title reveal"><?= e($ev['venue']) ?></h3>
        <div class="map reveal">
            <div id="map" class="map-canvas" role="region" aria-label="Peta lokasi <?= e($ev['venue']) ?>"
                 data-lat="<?= e((string) $ev['lat']) ?>" data-lng="<?= e((string) $ev['lng']) ?>"
                 data-label="<?= e($ev['venue']) ?>">
                <a class="map-fallback" href="<?= e($ev['maps_url']) ?>" target="_blank" rel="noopener">Buka peta lokasi</a>
            </div>
        </div>
        <div class="actions reveal">
            <a class="btn btn-dark" href="<?= e($ev['maps_url']) ?>" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                Google Maps
            </a>
            <a class="btn btn-outline" href="<?= e($ev['waze_url']) ?>" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 11l18-8-8 18-2-8-8-2z"/></svg>
                Waze
            </a>
        </div>
    </section>

    <!-- ============ RSVP & UCAPAN ============ -->
    <section class="section rsvp" id="rsvp">
        <p class="eyebrow reveal">Konfirmasi Kehadiran</p>
        <h3 class="section-title reveal">RSVP &amp; Doa Restu</h3>
        <p class="muted reveal">Mohon kesediaannya untuk mengonfirmasi kehadiran dan menitipkan doa untuk kami.</p>

        <?php if (!$dbReady): ?>
            <p class="notice reveal">Formulir konfirmasi sedang disiapkan. Silakan kembali beberapa saat lagi.</p>
        <?php else: ?>
        <form class="rsvp-form reveal" id="rsvpForm" action="api/rsvp.php" method="post" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="invited_as" value="<?= e($guest) ?>">
            <div class="hp" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <label class="field">
                <span>Nama</span>
                <input type="text" name="name" maxlength="100" required autocomplete="name"
                       value="<?= e($guest) ?>" placeholder="Nama Anda">
            </label>

            <fieldset class="field">
                <legend>Kehadiran</legend>
                <div class="choices">
                    <label class="choice"><input type="radio" name="attendance" value="hadir" required><span>Hadir</span></label>
                    <label class="choice"><input type="radio" name="attendance" value="tidak_hadir"><span>Tidak Hadir</span></label>
                    <label class="choice"><input type="radio" name="attendance" value="ragu"><span>Masih Ragu</span></label>
                </div>
            </fieldset>

            <label class="field" id="guestsField" hidden>
                <span>Jumlah tamu</span>
                <select name="guests">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> orang</option>
                    <?php endfor; ?>
                </select>
            </label>

            <label class="field">
                <span>Ucapan &amp; doa <em>(opsional)</em></span>
                <textarea name="message" rows="4" maxlength="500" placeholder="Tuliskan ucapan dan doa terbaik…"></textarea>
            </label>

            <button type="submit" class="btn btn-dark btn-block">Kirim</button>
            <p class="form-status" id="formStatus" role="status" aria-live="polite"></p>
        </form>
        <?php endif; ?>

        <div class="wishes reveal">
            <h4 class="wishes-title">Ucapan &amp; Doa <span id="wishCount"><?= count($wishes) ?: '' ?></span></h4>
            <ul class="wish-list" id="wishList">
                <?php foreach ($wishes as $w): ?>
                    <li class="wish">
                        <div class="wish-head">
                            <strong><?= e($w['name']) ?></strong>
                            <span class="badge"><?= e(attendance_label($w['attendance'])) ?></span>
                        </div>
                        <p><?= nl2br(e($w['message'])) ?></p>
                        <time datetime="<?= e(date('c', strtotime($w['created_at']))) ?>"><?= e(waktu_lalu($w['created_at'])) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="muted wish-empty" id="wishEmpty" <?= $wishes ? 'hidden' : '' ?>>Belum ada ucapan. Jadilah yang pertama.</p>
        </div>
    </section>

    <!-- ============ PENUTUP ============ -->
    <section class="section section-dark closing">
        <div class="frame" aria-hidden="true"></div>
        <p class="lead reveal">
            Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i
            berkenan hadir dan memberikan doa restu.
        </p>
        <p class="arabic reveal" lang="ar" dir="rtl">بَارَكَ اللَّهُ لَكَ وَبَارَكَ عَلَيْكَ وَجَمَعَ بَيْنَكُمَا فِي خَيْرٍ</p>
        <p class="muted-light reveal"><em>“Semoga Allah memberkahimu dalam suka maupun duka, dan mempersatukan kalian berdua dalam kebaikan.”</em> (HR. Abu Dawud &amp; Tirmidzi)</p>

        <?= ornament('on-dark reveal') ?>

        <p class="arabic reveal" lang="ar" dir="rtl">وَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
        <p class="signoff reveal">Kami yang berbahagia,</p>
        <p class="names-script names-sm reveal">
            <?= e($c['groom']['nickname']) ?> <span class="amp">&amp;</span> <?= e($c['bride']['nickname']) ?>
        </p>
        <p class="muted-light reveal">beserta keluarga besar</p>
    </section>

    <footer class="footer">
        <p><?= e($c['groom']['nickname']) ?> &amp; <?= e($c['bride']['nickname']) ?> · <?= e(date('Y', strtotime($ev['start']))) ?></p>
    </footer>
</main>

<script src="assets/vendor/leaflet/leaflet.js" defer></script>
<script src="assets/js/main.js?v=2" defer></script>
</body>
</html>
