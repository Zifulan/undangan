<?php
/**
 * Salin file ini menjadi `config.php` lalu isi sesuai akun cPanel Anda.
 * (cPanel → MySQL® Databases: buat database + user, lalu "Add User To Database" dengan ALL PRIVILEGES.)
 *
 * Tabel akan dibuat otomatis saat pertama kali diakses — tidak perlu import SQL / seeder.
 */
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'cpaneluser_undangan',   // nama database (biasanya diawali username cPanel)
        'user'    => 'cpaneluser_undangan',   // user database
        'pass'    => 'GANTI_PASSWORD_DB',
        'charset' => 'utf8mb4',
    ],

    // Password untuk membuka halaman admin.php (daftar RSVP & ekspor CSV)
    'admin_password' => 'GANTI_PASSWORD_ADMIN',
];
