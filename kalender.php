<?php
declare(strict_types=1);

/**
 * File kalender (.ics) untuk iPhone, Outlook, dan aplikasi kalender lain.
 * Berisi pengingat H-1 dan 2 jam sebelum acara.
 */
require __DIR__ . '/includes/bootstrap.php';

$c  = $CONTENT;
$ev = $c['event'];

$esc = fn(string $s) => str_replace(["\\", ';', ',', "\n"], ["\\\\", '\;', '\,', '\n'], $s);
$utc = fn(string $d) => gmdate('Ymd\THis\Z', strtotime($d));
$site = rtrim((string) ($c['site_url'] ?? base_url()), '/');
$title = $ev['name'] . ' ' . $c['groom']['nickname'] . ' & ' . $c['bride']['nickname'];
$loc = $ev['venue'] . (($ev['address'] ?? '') !== '' ? ', ' . $ev['address'] : '');
$desc = 'Akad Nikah ' . $c['groom']['name'] . ' & ' . $c['bride']['name'] . "\n"
      . ($ev['time_text'] ?? '') . "\n"
      . 'Peta: ' . ($ev['maps_url'] ?? '') . "\n"
      . 'Undangan: ' . $site . '/';

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Undangan ' . $esc($c['groom']['nickname'] . ' & ' . $c['bride']['nickname']) . '//ID',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'BEGIN:VEVENT',
    'UID:akad-' . md5($ev['start'] . $title) . '@' . (parse_url($site, PHP_URL_HOST) ?: 'undangan'),
    'DTSTAMP:' . gmdate('Ymd\THis\Z'),
    'DTSTART:' . $utc($ev['start']),
    'DTEND:' . $utc($ev['end']),
    'SUMMARY:' . $esc($title),
    'LOCATION:' . $esc($loc),
    'DESCRIPTION:' . $esc($desc),
    'URL:' . $site . '/',
    isset($ev['lat'], $ev['lng']) ? 'GEO:' . $ev['lat'] . ';' . $ev['lng'] : null,
    'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc('Besok: ' . $title), 'TRIGGER:-P1D', 'END:VALARM',
    'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc($title . ' dimulai 2 jam lagi'), 'TRIGGER:-PT2H', 'END:VALARM',
    'END:VEVENT',
    'END:VCALENDAR',
];

// Lipat baris > 75 oktet sesuai RFC 5545.
$fold = function (string $l): string {
    $out = '';
    while (strlen($l) > 75) {
        $cut = 75;
        while ($cut > 0 && (ord($l[$cut]) & 0xC0) === 0x80) { // jangan memotong karakter UTF-8
            $cut--;
        }
        $out .= substr($l, 0, $cut) . "\r\n ";
        $l = substr($l, $cut);
    }
    return $out . $l;
};

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="akad-fatah-gege.ics"');
header('Cache-Control: public, max-age=3600');
echo implode("\r\n", array_map($fold, array_filter($lines))) . "\r\n";
