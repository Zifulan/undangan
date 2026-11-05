<?php
declare(strict_types=1);

/**
 * Doodle garis bergaya gambar tangan (SVG).
 * Warna mengikuti section: currentColor untuk garis utama, --d-green untuk daun,
 * --d-bg untuk isian (menutup garis di belakangnya), --d-rose untuk aksen.
 */

/** Filter "goresan tangan" — cukup dicetak sekali per halaman. */
function doodle_defs(): string
{
    return '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">'
        . '<filter id="doodle-rough"><feTurbulence type="fractalNoise" baseFrequency=".05" numOctaves="2" seed="5"/>'
        . '<feDisplacementMap in="SourceGraphic" scale="1.8"/></filter>'
        // goyangan lebih lebar & halus untuk bingkai kotak
        . '<filter id="sketch-rough" x="-5%" y="-5%" width="110%" height="110%"><feTurbulence type="fractalNoise" baseFrequency=".012 .02" numOctaves="2" seed="8"/>'
        . '<feDisplacementMap in="SourceGraphic" scale="5"/></filter></svg>';
}

function doodle(string $name, string $class = ''): string
{
    static $art = null;
    if ($art === null) {
        $art = [
            // Dua cincin dengan berlian
            'rings' => '
                <circle cx="47" cy="74" r="22"/><circle cx="47" cy="74" r="18" class="l"/>
                <circle cx="73" cy="70" r="22" class="bg"/><circle cx="73" cy="70" r="18" class="l"/>
                <path d="M65 46 L68 40 H78 L81 46 L73 54 Z" class="bg"/>
                <path d="M65 46 H81 M68 40 L71 46 L73 54 M78 40 L75 46 L73 54" class="l"/>
                <path d="M33 62 q4 -8 12 -10" class="l"/>
                <path d="M96 28 l1.5 4.5 l4.5 1.5 l-4.5 1.5 l-1.5 4.5 l-1.5 -4.5 l-4.5 -1.5 l4.5 -1.5z" class="g"/>
                <path d="M26 36 v6 M23 39 h6 M100 92 v5 M97.5 94.5 h5" class="g"/>',

            // Masjid dengan kubah & dua menara
            'mosque' => '
                <path d="M12 104 H108"/>
                <path d="M30 104 V70 H90 V104"/>
                <path d="M36 70 C30 54 48 46 60 36 C72 46 90 54 84 70"/>
                <path d="M60 36 V27"/>
                <path d="M57 22 a4.5 4.5 0 1 0 6 -4.5 a3.4 3.4 0 1 1 -6 4.5z"/>
                <path d="M30 77 H90" class="l"/>
                <path d="M52 104 V89 Q60 78 68 89 V104"/>
                <path d="M38 94 V86 Q42 80 46 86 V94 Z M74 94 V86 Q78 80 82 86 V94 Z" class="l"/>
                <path d="M15 104 V52 H24 V104 M13 64 H26 M15 52 L19.5 40 L24 52 M19.5 40 V34"/>
                <path d="M96 104 V52 H105 V104 M94 64 H107 M96 52 L100.5 40 L105 52 M100.5 40 V34"/>
                <path d="M19.5 72 v6 M100.5 72 v6" class="l"/>
                <path d="M40 24 v5 M37.5 26.5 h5 M84 18 v5 M81.5 20.5 h5" class="g"/>',

            // Rehal (dudukan) & mushaf terbuka
            'quran' => '
                <path d="M36 74 L84 108 M42 72 L88 104"/>
                <path d="M84 74 L36 108 M78 72 L32 104"/>
                <path d="M36 108 L32 104 M84 108 L88 104"/>
                <path d="M55 88 q5 4 10 0" class="l"/>
                <path d="M60 50 Q46 42 24 44 L26 72 Q46 70 60 78 Z" class="bg"/>
                <path d="M60 50 Q74 42 96 44 L94 72 Q74 70 60 78 Z" class="bg"/>
                <path d="M60 50 V78"/>
                <path d="M24 44 L22 47 L24 75 Q44 73 60 81 Q76 73 96 75 L98 47 L96 44" class="l"/>
                <g class="l"><path d="M31 52 Q44 50 54 55"/><path d="M31 58 Q44 56 54 61"/><path d="M31 64 Q44 62 54 67"/>
                <path d="M89 52 Q76 50 66 55"/><path d="M89 58 Q76 56 66 61"/><path d="M89 64 Q76 62 66 67"/></g>
                <path d="M60 22 l2 6 l6 2 l-6 2 l-2 6 l-2 -6 l-6 -2 l6 -2 z" class="g"/>
                <path d="M38 28 v5 M35.5 30.5 h5 M84 30 v5 M81.5 32.5 h5" class="g"/>',

            // Hati
            'hearts' => '
                <path d="M58 96 C28 76 20 60 28 47 C36 35 52 39 58 51 C64 39 80 35 88 47 C96 60 88 76 58 96Z"/>
                <path d="M58 88 C36 73 30 61 35 52 C41 43 52 46 58 56" class="l"/>
                <path d="M28 30 c-4 -6 -12 -2 -8 4 c2 4 8 6 8 6 c0 0 6 -2 8 -6 c4 -6 -4 -10 -8 -4z"/>
                <path d="M94 24 c-3 -5 -9 -2 -6 3 c1 3 6 5 6 5 c0 0 5 -2 6 -5 c3 -5 -3 -8 -6 -3z" class="p"/>
                <path d="M100 82 c-3 -4 -8 -1 -5 3 c1 2 5 4 5 4 c0 0 4 -2 5 -4 c3 -4 -2 -7 -5 -3z" class="f"/>
                <path d="M16 62 l-6 -2 M18 72 l-6 2 M100 54 l6 -2 M101 64 l6 1" class="l"/>',

            // Untaian lampu hias
            'lights' => '
                <path d="M2 28 Q32 58 60 38 Q88 20 118 42"/>
                <g class="g"><path d="M16 38 v5 M38 45 v5 M60 37 v5 M82 29 v5 M104 33 v5"/></g>
                <path d="M13 43 Q9 52 16 57 Q23 52 19 43 Z" class="p"/>
                <path d="M35 50 Q31 59 38 64 Q45 59 41 50 Z" class="bg"/>
                <path d="M57 42 Q53 51 60 56 Q67 51 63 42 Z" class="p"/>
                <path d="M79 34 Q75 43 82 48 Q89 43 85 34 Z" class="bg"/>
                <path d="M101 38 Q97 47 104 52 Q111 47 107 38 Z" class="p"/>
                <path d="M30 60 l-4 3 M46 60 l4 3 M38 69 v4 M74 44 l-4 3 M90 44 l4 3 M82 53 v4" class="l"/>',

            // Buket bunga mawar
            'bouquet' => '
                <g class="g">
                  <path d="M40 60 Q24 60 18 48 Q32 46 40 60 M40 60 Q28 54 18 48"/>
                  <path d="M80 60 Q96 60 102 48 Q88 46 80 60 M80 60 Q92 54 102 48"/>
                  <path d="M50 36 Q44 22 50 14 Q58 24 50 36 M50 36 Q50 24 50 14"/>
                  <path d="M74 38 Q84 28 94 30 Q88 42 74 38 M74 38 Q84 34 94 30"/>
                  <path d="M36 38 q-5 -8 0 -13 M84 24 q5 -6 11 -5"/>
                </g>
                <path d="M33 26 q-4 -6 2 -9 q5 3 -2 9z" class="p"/>
                <path d="M95 20 q-1 -7 6 -8 q2 6 -6 8z" class="p"/>
                <g class="g"><path d="M64 30 l2 -9 M66 21 l-4 -4 M66 21 l5 -3"/></g>
                <g class="f"><circle cx="62" cy="16.5" r="1.6"/><circle cx="71.5" cy="17.5" r="1.6"/><circle cx="66" cy="20.5" r="1.3"/></g>
                <path d="M30 52 q-2 -12 10 -13 q12 0 11 12 q-1 11 -11 11 q-10 0 -10 -10z" class="bg"/>
                <path d="M69 52 q-2 -12 10 -13 q12 0 11 12 q-1 11 -11 11 q-10 0 -10 -10z" class="bg"/>
                <path d="M47 40 q-2 -14 13 -15 q14 0 13 14 q-1 13 -13 13 q-12 0 -13 -12z" class="bg"/>
                <path d="M40 51 q-4 -2 -1 -5 q5 -2 6 3 q1 6 -6 7 q-7 0 -7 -7"/>
                <path d="M79 51 q-4 -2 -1 -5 q5 -2 6 3 q1 6 -6 7 q-7 0 -7 -7"/>
                <path d="M60 39 q-5 -2 -1 -6 q6 -3 7 3 q1 7 -7 8 q-8 0 -8 -8 q0 -6 6 -8"/>
                <path d="M49 46 q4 6 11 6 M71 46 q-3 5 -9 6" class="l"/>
                <path d="M36 62 L58 110 L62 110 L84 62" class="bg"/>
                <path d="M36 62 Q60 72 84 62"/>
                <path d="M48 66 L59 108 M72 66 L61 108" class="l"/>
                <path d="M60 88 c-8 -9 -18 -5 -14 1 c3 4 10 3 14 -1 c4 4 11 5 14 1 c4 -6 -6 -10 -14 -1z" class="bg"/>
                <circle cx="60" cy="88" r="2.2" class="f"/>
                <path d="M59 90 q-4 8 -9 12 M61 90 q4 8 10 11"/>',

            // Amplop dengan segel hati
            'envelope' => '
                <path d="M18 42 H102 V98 H18 Z" class="bg"/>
                <path d="M18 44 L60 74 L102 44"/>
                <path d="M18 98 L50 68 M102 98 L70 68" class="l"/>
                <circle cx="60" cy="74" r="9" class="f"/>
                <path d="M60 71 c-2 -3 -6 -1 -4 2 c1 2 4 3.5 4 3.5 c0 0 3 -1.5 4 -3.5 c2 -3 -2 -5 -4 -2z" class="hole"/>
                <path d="M60 24 c-3 -5 -9 -2 -6 3 c1 3 6 5 6 5 c0 0 5 -2 6 -5 c3 -5 -3 -8 -6 -3z" class="p"/>
                <path d="M46 26 l-5 -3 M74 26 l5 -3 M60 14 v-4" class="l"/>',

            // Panah melengkung (untuk label nama di sampul)
            'arrow' => '
                <path d="M14 22 C42 10 70 22 66 44 C63 58 46 58 50 46 C54 34 78 40 88 62 C93 73 95 82 96 92"/>
                <path d="M86 84 L96 94 L102 82"/>',

            // Bulan sabit & bintang
            'crescent' => '
                <path d="M70 24 A36 36 0 1 0 94 84 A28 28 0 1 1 70 24Z"/>
                <path d="M66 34 A27 27 0 0 0 44 66" class="l"/>
                <path d="M92 40 l3 8 l8 1 l-6 5 l2 8 l-7 -4 l-7 4 l2 -8 l-6 -5 l8 -1 z" class="p"/>
                <path d="M24 24 v6 M21 27 h6 M102 96 v5 M99.5 98.5 h5 M100 18 v4 M98 20 h4" class="g"/>
                <circle cx="30" cy="96" r="1.6" class="f"/>',
        ];
    }

    if (!isset($art[$name])) {
        return '';
    }

    $viewBox = ['lights' => '0 18 120 62'][$name] ?? '0 0 120 120';

    return '<svg class="doodle doodle-' . e($name) . ' ' . e($class) . '" viewBox="' . $viewBox . '" aria-hidden="true" focusable="false">'
        . '<g filter="url(#doodle-rough)">' . $art[$name] . '</g></svg>';
}

/**
 * Kalender bergaya gambar tangan (mulai Senin), tanggal acara ditandai hati.
 */
function doodle_calendar(int $ts): string
{
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $heads  = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    $y      = (int) date('Y', $ts);
    $m      = (int) date('n', $ts);
    $day    = (int) date('j', $ts);
    $first  = mktime(0, 0, 0, $m, 1, $y);
    $offset = (int) date('N', $first) - 1;
    $total  = (int) date('t', $first);

    $cells = '';
    foreach ($heads as $h) {
        $cells .= '<span class="dcal-head">' . $h . '</span>';
    }
    for ($i = 0; $i < $offset; $i++) {
        $cells .= '<span></span>';
    }
    for ($d = 1; $d <= $total; $d++) {
        if ($d === $day) {
            $cells .= '<span class="dcal-day is-event" aria-label="' . $d . ' ' . $months[$m] . ' — hari akad">'
                . '<svg class="dcal-heart" viewBox="0 0 60 56" aria-hidden="true"><g filter="url(#doodle-rough)">'
                // hati utama (diisi) + garis sketsa yang sedikit bergeser
                . '<path class="h-fill" d="M30 47 C14 36 8 27 12 19 C16 11 26 12 30 21 C34 12 44 11 48 19 C52 27 46 36 30 47Z"/>'
                . '<path class="h-line" d="M31.5 45.5 C16 35 10.5 26.5 14 19.5 C18 12.5 27 13.5 31 21.5 C35 13 44.5 12.5 48.5 20 C52 27.5 46.5 35.5 31.5 45.5Z"/>'
                // garis "berkilau" di atas
                . '<path class="h-burst" d="M12 12 l3.5 3.5 M30 7 v4.5 M48 12 l-3.5 3.5"/>'
                // hati kecil
                . '<path class="h-mini" d="M53 42 c-2 -3 -6 -1 -4 2 c1 2 4 3.4 4 3.4 c0 0 3 -1.4 4 -3.4 c2 -3 -2 -5 -4 -2z"/>'
                . '</g></svg>'
                . '<b>' . $d . '</b></span>';
        } else {
            $cells .= '<span class="dcal-day">' . $d . '</span>';
        }
    }

    $frame = '<svg class="dcal-frame" viewBox="0 0 300 340" preserveAspectRatio="none" aria-hidden="true">'
        . '<g filter="url(#doodle-rough)">'
        . '<path class="dcal-fill" d="M14 52 H286 V326 H14 Z"/>'
        . '<path d="M14 52 H286 V326 H14 Z"/>'
        . '<path class="l" d="M22 60 H278 V318 H22 Z"/>'
        // mahkota bergulir di atas bingkai
        . '<path d="M62 52 C60 38 72 31 82 37 C90 42 85 53 77 50 C71 47 74 40 79 41"/>'
        . '<path d="M82 37 C110 20 190 20 218 37"/>'
        . '<path d="M238 52 C240 38 228 31 218 37 C210 42 215 53 223 50 C229 47 226 40 221 41"/>'
        . '<path d="M112 29 q-7 -10 2 -13 q6 3 0 9 M188 29 q7 -10 -2 -13 q-6 3 0 9"/>'
        . '<path class="f" d="M150 33 c-5 -7 -14 -4 -9 3 c3 4 9 7 9 7 c0 0 6 -3 9 -7 c5 -7 -4 -10 -9 -3z"/>'
        . '<path d="M14 52 c-7 0 -11 -7 -6 -11 c4 -4 10 0 6 4 M286 52 c7 0 11 -7 6 -11 c-4 -4 -10 0 -6 4"/>'
        . '<path class="l" d="M136 326 l14 -6 l14 6"/>'
        . '</g></svg>';

    return '<div class="dcal" role="img" aria-label="Kalender ' . $months[$m] . ' ' . $y . ', akad tanggal ' . $day . '">'
        . $frame
        . '<div class="dcal-body">'
        . '<p class="script dcal-month">' . $months[$m] . '</p>'
        . '<p class="dcal-year">' . $y . '</p>'
        . '<div class="dcal-grid">' . $cells . '</div>'
        . '</div></div>';
}

/**
 * Tepi bergelombang bergaya tangan untuk pergantian warna section.
 * Dipasang di dalam section .wine (atas/bawah), menutupi tepi lurus section tetangga.
 */
function wave_edge(string $pos, int $seed = 1): string
{
    $w = 1200;
    $pts = [];
    for ($x = 0; $x <= $w; $x += 40) {
        $y = 20 + 7 * sin($x / 97 + $seed * 1.7) + 4 * sin($x / 41 + $seed * .9) + 2 * sin($x / 13 + $seed);
        $pts[] = [$x, round($y, 1)];
    }
    $d = 'M0 40 L0 ' . $pts[0][1];
    for ($i = 1; $i < count($pts); $i++) {
        $mx = ($pts[$i - 1][0] + $pts[$i][0]) / 2;
        $my = ($pts[$i - 1][1] + $pts[$i][1]) / 2;
        $d .= ' Q' . $pts[$i - 1][0] . ' ' . $pts[$i - 1][1] . ' ' . $mx . ' ' . $my;
    }
    $d .= ' L' . $w . ' ' . end($pts)[1] . ' L' . $w . ' 40 Z';

    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 40' preserveAspectRatio='none'><path d='" . $d . "'/></svg>";
    $uri = 'data:image/svg+xml,' . rawurlencode($svg);

    return '<div class="edge edge-' . e($pos) . '" style="--mask:url(&quot;' . $uri . '&quot;)" aria-hidden="true"></div>';
}
