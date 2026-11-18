# Deploying CareSync on InfinityFree

InfinityFree serves your site from one folder, `htdocs/`, and you cannot point
the web root at `public/`. The files below make that safe and keep the address
clean (`https://yourdomain/login`, no `/ci4/public/` and no `index.php`).

## Folder layout

Upload the **contents of the `ci4` folder directly into `htdocs/`**, so it looks
like this:

```
htdocs/
├── .htaccess          <- sends every request into public/ and blocks private files
├── .env               <- your live settings (never share this file)
├── app/               <- blocked from the web
├── system/            <- blocked from the web
├── vendor/            <- blocked from the web (includes dompdf for PDFs)
├── writable/          <- blocked from the web (logs, cache, uploads)
├── public/
│   ├── .htaccess      <- sends pretty URLs to index.php, hides X-Powered-By
│   ├── index.php
│   └── assets/ ...
├── spark, composer.json ...   <- blocked from the web
```

Do **not** upload the `ci4` folder itself (that is what puts `/ci4/public/` in
the address). Show hidden files in your FTP client so `.htaccess` and `.env`
are uploaded.

## Settings (`.env`)

```
CI_ENVIRONMENT = production

app.baseURL = 'https://yourdomain.example/'
app.appTimezone = 'Asia/Manila'

database.default.hostname = sqlXXX.infinityfree.com
database.default.database = if0_XXXXXXXX_caresync
database.default.username = if0_XXXXXXXX
database.default.password = ...
database.default.DBDriver = MySQLi
database.default.port = 3306
```

`app.baseURL` must be the plain domain with a trailing slash. Every link in the
system is built from it. Keep `CI_ENVIRONMENT = production` so visitors never
see error details.

## After uploading

1. Run any new database migrations (see the release notes). Only add a
   migration if one is listed; none are needed for the latest fix pass.
2. Open `https://yourdomain.example/login`.
3. Check that private files are refused. Each of these must show 403 or 404:
   - `/.env`
   - `/app/Config/App.php`
   - `/composer.json`
   - `/writable/logs/`
4. Open a wrong address such as `/no-such-page`. You should see the branded
   CareSync "We could not find that page" screen.

## Notes

- Security headers (frame, content-type, referrer, permissions and HTTPS
  strict-transport) are added by `app/Filters/SecureHeaders.php`. The camera is
  allowed on this site only, for the ID verification photo.
- A Content-Security-Policy is **not** switched on yet because the pages use
  inline scripts. The steps to enable it are in the comments of
  `app/Filters/SecureHeaders.php`.
- `public/.htaccess` hides the `X-Powered-By` header. If the host does not
  allow it, the line is ignored.
- On a host where you *can* point the web root at `public/`, do that and the
  root `.htaccess` is simply not used.

## Before going live

Do these in order. Do not skip step 1: while the live site is in development
mode it shows the debug toolbar and error details to everyone.

1. **Production mode.** In the server's `.env`, set `CI_ENVIRONMENT = production`.
2. **New database password.** Change the MySQL password in the InfinityFree
   control panel, then put the new password in the server's `.env`
   (`database.default.password`). Never reuse a password that was ever written
   in a file you shared or emailed.
3. **Upload the right folder.** Upload the **contents** of `ci4/` into `htdocs/`,
   not the `ci4` folder itself. Do not upload `.env` from your computer; create
   the server's own `.env` there.
   Use `.env.production.example` as the template for that file.
4. **Update the database (migrations).** InfinityFree has no command line, so:
   add `app.migrateKey = 'a-long-random-value'` (16+ characters) to the server's
   `.env`, open `https://your-domain/migrate/a-long-random-value` once, wait for
   "Database is up to date", then **delete that line from `.env`**. Back up the
   database in phpMyAdmin first. Without the key the address simply shows the 404 page.
5. **Clear old sessions.** Empty `writable/session/` on the server (keep
   `index.html`) so nobody keeps a session from the development site.
6. **Test.**
   - Log in with each role (System Admin, Branch Admin, Staff, Plan Holder, Collector).
   - Save one form (for example a client note).
   - Enter a wrong password 6 times in a row. The 6th try must say
     "Too many sign-in attempts" and be blocked for a minute.
   - View the source of the login page: it must contain no `debugbar`,
     `DEBUG-VIEW` or `kint` text.

7. **Delete used credential files.** Importing existing members writes a file of
   temporary passwords to `writable/imports/` (for example
   `faustino_aclan_credentials_*.csv`). Once the passwords have been handed out,
   open `https://your-domain/admin/imports/credentials` (System Admin) and press **Delete**, or delete the file
   from `writable/imports/` in the InfinityFree file manager. Do not leave these
   files on the server, do not download them to a shared computer, and never put
   them in a zip or in Git.

### What not to share
When you zip the project for someone, leave out `.env`, `env`,
`INFINITYFREE_ENV.txt`, `vendor/`, `composer.phar`, and the contents of
`writable/session`, `writable/logs`, `writable/debugbar`, `writable/cache` and
`writable/uploads` and `writable/imports` (these hold sessions, logs, customers' ID photos
and temporary passwords). `tools/make-share-zip.ps1` builds a clean zip for you:
`powershell -ExecutionPolicy Bypass -File tools\make-share-zip.ps1`.
