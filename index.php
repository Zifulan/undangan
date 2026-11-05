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
    'city'     => 'Jakarta',
];
$guest = trim(mb_substr((string) ($_GET['to'] ?? ''), 0, 80));

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
    'location' => $ev['venue'],
    'ctz'      => 'Asia/Jakarta',
]);

$G = $c['groom'];
$B = $c['bride'];
$monogram  = mb_substr($G['nickname'], 0, 1) . mb_substr($B['nickname'], 0, 1);   // "FG"
$sealText  = mb_substr($G['nickname'], 0, 1) . '&' . mb_substr($B['nickname'], 0, 1); // "F&G"
$months    = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$days      = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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
    <meta property="og:image" content="<?= e(base_url()) ?>/assets/img/og.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Caveat:wght@500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css?v=8">
    <noscript><style>.reveal,.words .w{opacity:1;transform:none;filter:none}.cover{display:none}body.is-locked{overflow:auto;height:auto}</style></noscript>
</head>
<body class="is-locked">
<?= doodle_defs() ?>

<!-- ============ SAMPUL: AMPLOP & SEGEL ============ -->
<section class="cover paper" id="cover" aria-label="Sampul undangan"<?= slot_bg('bg-cover.jpg') ?>>
    <div class="cover-inner">
        <div class="recipient">
            <p class="caps">Kepada Yth.</p>
            <p class="recipient-name"><?= $guest !== '' ? e($guest) : 'Tamu Undangan' ?></p>
        </div>

        <?php if (has_img('kid-fatah.jpg') && has_img('kid-gege.jpg')): ?>
        <div class="kids">
            <figure class="kid kid-l">
                <img src="<?= e(img_url('kid-fatah.jpg')) ?>" alt="Foto <?= e($G['nickname']) ?> semasa SD" width="480" height="603" decoding="async">
                <figcaption class="kid-label"><?= doodle('arrow', 'kid-arrow') ?><span><?= e($G['nickname']) ?></span></figcaption>
            </figure>
            <figure class="kid kid-r">
                <img src="<?= e(img_url('kid-gege.jpg')) ?>" alt="Foto <?= e($B['nickname']) ?> semasa SD" width="480" height="602" decoding="async">
                <figcaption class="kid-label"><span><?= e($B['nickname']) ?></span><?= doodle('arrow', 'kid-arrow') ?></figcaption>
            </figure>
            <?= doodle('hearts', 'kid-hearts') ?>
        </div>
        <?php endif; ?>

        <div class="envelope" id="envelope">
            <div class="env-back"></div>
            <div class="env-card">
                <p class="caps caps-xs">Undangan Akad Nikah</p>
                <p class="script env-names"><?= e($G['nickname']) ?> <span>&amp;</span> <?= e($B['nickname']) ?></p>
                <p class="caps caps-xs"><?= e(date('d . m . Y', $startTs)) ?></p>
            </div>
            <div class="env-pocket"></div>
            <div class="env-flap-wrap"><div class="env-flap"<?= slot_bg('envelope-liner.jpg') ?>></div></div>
            <?= slot_img('floral-envelope.png', 'floral floral-envelope') ?>
            <button type="button" class="env-seal" id="openInvitation" aria-label="Buka undangan">
                <?= wax_seal($sealText, 'plum') ?>
            </button>
        </div>

        <p class="cover-hint">Ketuk segel</p>
    </div>
</section>

<main id="main">

    <!-- ============ KARTU NAMA ============ -->
    <section class="section paper hero" id="beranda">
        <?= doodle('lights', 'dd-lights reveal') ?>
        <p class="arabic arabic-bismillah reveal" lang="ar" dir="rtl">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>

        <div class="cartouche cartouche-hero tilt-l reveal">
            <?= cartouche_frame() ?>
            <?= slot_img('floral-hero.png', 'floral floral-hero') ?>
            <?= doodle('rings', 'dd dd-hero-rings') ?>
            <?= has_img('floral-hero.png') ? '' : doodle('hearts', 'dd dd-hero-hearts') ?>
            <div class="cartouche-body">
                <p class="caps">Undangan<br>Akad Nikah</p>
                <h1 class="couple-names">
                    <span class="script name-script words"><?= e($G['nickname']) ?></span>
                    <span class="caps caps-name"><?= e($G['name']) ?></span>
                    <span class="script name-and">dan</span>
                    <span class="script name-script words"><?= e($B['nickname']) ?></span>
                    <span class="caps caps-name"><?= e($B['name']) ?></span>
                </h1>
            </div>
        </div>
    </section>

    <!-- ============ PEMBUKA & TANGGAL ============ -->
    <section class="section paper opening" id="pembuka">
        <?= doodle('crescent', 'dd dd-corner-r') ?>
        <div class="opening-grid">
            <div class="calendar-wrap tilt-r reveal">
                <?= doodle_calendar($startTs) ?>
                <?= slot_img('floral-date.png', 'floral floral-date') ?>
            </div>

            <div class="opening-text">
                <p class="arabic reveal" lang="ar" dir="rtl">السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
                <h2 class="script section-script words reveal">Ikatan Suci Kami</h2>
                <p class="reveal">
                    Dengan memohon rahmat dan ridho Allah Subhanahu wa Ta'ala, kami bermaksud
                    menyelenggarakan akad nikah putra-putri kami.
                </p>
            </div>
        </div>

        <figure class="ayat sketch tilt-s reveal">
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
    </section>

    <!-- ============ MEMPELAI ============ -->
    <section class="section wine couple" id="mempelai">
        <?= wave_edge('top', 1) ?><?= wave_edge('bottom', 2) ?>
        <?= doodle('hearts', 'dd-inline dd-sm reveal') ?>
        <h2 class="script section-script words reveal">Mempelai</h2>

        <div class="couple-grid">
            <article class="person reveal">
                <h3 class="script person-nick"><?= e($G['nickname']) ?></h3>
                <p class="caps person-name"><?= e($G['name']) ?></p>
                <p class="person-parents"><?= $G['parents'] ?></p>
            </article>

            <div class="arch reveal" aria-hidden="true">
                <?= arch_frame() ?>
                <div class="arch-body"<?= slot_bg('bg-banner.jpg') ?>>
                    <span class="script arch-mono"><?= e(mb_substr($G['nickname'], 0, 1)) ?><small>&amp;</small><?= e(mb_substr($B['nickname'], 0, 1)) ?></span>
                </div>
            </div>

            <article class="person reveal">
                <h3 class="script person-nick"><?= e($B['nickname']) ?></h3>
                <p class="caps person-name"><?= e($B['name']) ?></p>
                <p class="person-parents"><?= $B['parents'] ?></p>
            </article>
        </div>
    </section>

    <!-- ============ BANNER MONOGRAM ============ -->
    <section class="banner" aria-hidden="true"<?= slot_bg('bg-banner.jpg') ?>>
        <span class="script banner-mono reveal"><?= e(mb_substr($G['nickname'], 0, 1)) ?><span>&amp;</span><?= e(mb_substr($B['nickname'], 0, 1)) ?></span>
    </section>

    <!-- ============ AKAD NIKAH ============ -->
    <section class="section wine event" id="acara">
        <?= wave_edge('top', 3) ?><?= wave_edge('bottom', 4) ?>
        <?= doodle('rings', 'dd-inline reveal') ?>
        <h2 class="script section-script words reveal"><?= e($ev['name']) ?></h2>

        <dl class="event-meta sketch tilt-r reveal">
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
                <dd><?= e($ev['venue']) ?></dd>
            </div>
        </dl>

        <div class="countdown reveal" id="countdown" data-target="<?= e($startIso) ?>" aria-live="polite">
            <div><span data-unit="days">0</span><small>Hari</small></div>
            <div><span data-unit="hours">0</span><small>Jam</small></div>
            <div><span data-unit="minutes">0</span><small>Menit</small></div>
            <div><span data-unit="seconds">0</span><small>Detik</small></div>
        </div>
        <p class="script countdown-done" id="countdownDone" hidden>Alhamdulillah, hari bahagia telah tiba</p>

        <div class="actions reveal">
            <a class="btn btn-ivory" href="<?= e($gcal) ?>" target="_blank" rel="noopener">Simpan ke Kalender</a>
        </div>
    </section>

    <!-- ============ LOKASI ============ -->
    <section class="section paper location" id="lokasi">
        <?= doodle('mosque', 'dd-inline reveal') ?>
        <h2 class="script section-script words reveal">Lokasi</h2>
        <p class="venue-name reveal"><?= e($ev['venue']) ?></p>

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
        <div class="note sketch tilt-s reveal">
            <?= slot_img('floral-rsvp.png', 'floral floral-rsvp') ?>
            <div class="note-seal"><?= wax_seal($sealText, 'olive') ?></div>
            <?= doodle('envelope', 'dd dd-note-env') ?>
            <h2 class="script section-script">Konfirmasi</h2>

            <?php if (!$dbReady): ?>
                <p class="notice">Formulir konfirmasi sedang disiapkan. Silakan kembali beberapa saat lagi.</p>
            <?php else: ?>
            <form class="rsvp-form" id="rsvpForm" action="api/rsvp.php" method="post" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="invited_as" value="<?= e($guest) ?>">
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
            <p class="muted wish-empty" id="wishEmpty" <?= $wishes ? 'hidden' : '' ?>>Belum ada ucapan.</p>
        </div>
    </section>

    <!-- ============ PENUTUP ============ -->
    <section class="section wine closing">
        <?= wave_edge('top', 5) ?>
        <?= doodle('hearts', 'dd dd-corner-l') ?>
        <?= doodle('crescent', 'dd dd-corner-r') ?>
        <p class="arabic reveal" lang="ar" dir="rtl">بَارَكَ اللَّهُ لَكَ وَبَارَكَ عَلَيْكَ وَجَمَعَ بَيْنَكُمَا فِي خَيْرٍ</p>
        <p class="closing-trans reveal"><em>“Semoga Allah memberkahimu dalam suka maupun duka, dan mempersatukan kalian berdua dalam kebaikan.”</em><br><span class="caps caps-xs">HR. Abu Dawud &amp; Tirmidzi</span></p>

        <div class="closing-seal reveal"><?= wax_seal($sealText, 'olive') ?></div>

        <p class="arabic reveal" lang="ar" dir="rtl">وَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</p>
        <p class="script closing-names words reveal"><?= e($G['nickname']) ?> &amp; <?= e($B['nickname']) ?></p>
    </section>

</main>

<script src="assets/vendor/leaflet/leaflet.js" defer></script>
<script src="assets/js/main.js?v=7" defer></script>
</body>
</html>
