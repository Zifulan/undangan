<?php
/**
 * Seluruh isi undangan ada di sini — ubah teks cukup dari file ini.
 */
return [
    'title' => 'Fatah & Gege',

    // Alamat situs (untuk link tamu & pratinjau WhatsApp).
    'site_url' => 'https://undangan.zif.my.id',

    // Pesan WhatsApp dari halaman admin. {nama} dan {link} diganti otomatis.
    'wa_message' => "Assalamu'alaikum {nama},\n\n"
        . "Dengan memohon rahmat dan ridho Allah SWT, kami mengundang {nama} untuk hadir dan mendoakan akad nikah kami.\n\n"
        . "Undangan lengkap:\n{link}\n\n"
        . "Merupakan suatu kebahagiaan bagi kami apabila {nama} berkenan hadir dan memberikan doa restu.\n\n"
        . "Wassalamu'alaikum,\nFatah & Gege",

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
