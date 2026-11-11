<?php
/**
 * Seluruh isi undangan ada di sini — ubah teks cukup dari file ini.
 */
return [
    'title' => 'Fatah & Gege',

    // Alamat situs (untuk link tamu & pratinjau WhatsApp).
    'site_url' => 'https://undangan.zif.my.id',

    // Pesan WhatsApp di halaman admin (pilih gayanya di sana). Kode yang diganti otomatis:
    // {nama} {link} {tanggal} (Sabtu, 12 Desember 2026) {jam} (08.00 WIB) {tempat} {alamat}
    'wa_messages' => [
        'hangat' => "Assalamu'alaikum {nama} 🤍\n\n"
            . "Insya Allah kami akan melangsungkan akad nikah pada:\n"
            . "*{tanggal}*\n"
            . "{jam} · {tempat}, Meruya Selatan\n\n"
            . "Kami ingin sekali {nama} hadir dan ikut mendoakan. Undangan & konfirmasi kehadiran:\n"
            . "{link}\n\n"
            . "Wassalamu'alaikum,\n*Fatah & Gege*",
        'formal' => "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\n"
            . "Kepada Yth. {nama},\n\n"
            . "Dengan memohon rahmat dan ridho Allah SWT, kami mengundang untuk hadir dan mendoakan akad nikah kami:\n\n"
            . "*{tanggal}*\n"
            . "Pukul {jam}\n"
            . "{tempat}, {alamat}\n\n"
            . "Undangan lengkap & konfirmasi kehadiran:\n"
            . "{link}\n\n"
            . "Atas kehadiran dan doa restunya, kami ucapkan terima kasih.\n\n"
            . "Wassalamu'alaikum Warahmatullahi Wabarakatuh\n*Fatah & Gege*",
        'santai' => "Hai {nama}! 👋\n\n"
            . "Kita nikah nih 😄 Akadnya *{tanggal}*, jam 8 pagi di {tempat}.\n\n"
            . "Dateng ya, sekalian doain kita 🤍\n"
            . "Detail & RSVP di sini: {link}\n\n"
            . "— Fatah & Gege",
    ],

    'groom' => [
        'nickname' => 'Fatah',
        'name'     => 'Fatahillah Rachman',
        'parents'  => 'Putra dari Bapak Muhammad Farid Zuhdi<br>&amp; Ibu Helma Djunila',
    ],
    'bride' => [
        'nickname' => 'Gege',
        'name'     => 'Gendhys Azizah Duanty',
        'parents'  => 'Putri dari Bapak Heri Purwanto<br>&amp; Ibu Antiq Lita Savita Purnama',
    ],

    'event' => [
        'name'      => 'Akad Nikah',
        'start'     => '2026-12-12 08:00:00',   // Asia/Jakarta (WIB)
        'end'       => '2026-12-12 10:00:00',   // perkiraan, untuk kalender
        'time_text' => '08.00 WIB – selesai',
        'venue'     => "Masjid Manarul 'Amal",
        'address'   => 'Jl. Meruya Selatan No. 1, RT.1/RW.8, Meruya Selatan, Kembangan, Jakarta Barat 11650',
        // Koordinat lokasi (untuk peta di halaman)
        'lat'       => -6.2104465,
        'lng'       => 106.7371328,
        'maps_url'  => 'https://maps.google.com/?cid=11657600879784974533',
        'waze_url'  => 'https://waze.com/ul?ll=-6.2104465,106.7371328&navigate=yes',
    ],
];
