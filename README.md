# Undangan Akad Nikah — Fatah & Gege

A digital akad nikah invitation in plain PHP and MySQL, made for cPanel shared hosting.
No framework, no Composer, no seeder, no SQL import.

## Deploy to cPanel (zif.my.id)

1. **Create the database.** In cPanel, open **MySQL® Databases** and:
   - create a database, e.g. `cpaneluser_undangan`
   - create a user with a strong password
   - under **Add User To Database**, add that user to the database with **ALL PRIVILEGES**
2. **Upload the files.** In **File Manager**, upload everything in this repo to `public_html/`
   (or to a subdomain's folder). You can also use cPanel **Git™ Version Control** to clone the repo.
3. **Configure.** Copy `config.example.php` to `config.php` and fill in the database name,
   user, password and an admin password.
4. **Done.** Open the site once. The `rsvp` table is created automatically on the first visit.

PHP 7.4 or newer is required (the PHP 8.x versions offered in cPanel work).

## Personal guest links

Add `?to=` to the URL to show the guest's name on the cover:

```
https://zif.my.id/?to=Budi%20Santoso
https://zif.my.id/?to=Bapak%20Ahmad%20%26%20Keluarga
```

(Spaces are `%20`, `&` is `%26`.) The name also pre-fills the RSVP form.

## Admin

`https://zif.my.id/admin.php` shows RSVP totals, the list of responses and wishes,
deletion of spam, and **CSV export** (opens in Excel or Google Sheets).

## Editing content

All text (names, parents, date, venue, map) is in `includes/content.php`.
Colours are CSS variables at the top of `assets/css/style.css`.
The WhatsApp/social preview image is `assets/img/og.jpg` (1200×630).

## Structure

```
index.php             the invitation
admin.php             RSVP dashboard and CSV export (password protected)
api/rsvp.php          RSVP endpoint (CSRF, honeypot, rate limit)
includes/             bootstrap, DB (auto-creates the table), content (blocked by .htaccess)
assets/               CSS, JS, images
config.example.php    copy to config.php (config.php is git-ignored)
```
