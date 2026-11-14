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
