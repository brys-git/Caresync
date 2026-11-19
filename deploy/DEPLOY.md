# Deploying Part B + Part D (claims/avails, payment allocation, reports, ID Control No.)

For: InfinityFree (no SSH, no cron, phpMyAdmin only). Do it on the **staging subdomain first**, then on the
live site. Do not merge `feature/part-b` into `main` until staging has passed the test list below.

Files in this folder:

| File | What it is |
| --- | --- |
| `partB_migrations.sql` | All database changes of Part B **and** Part D (9 migrations). Import **once**. |
| `partD_backfill.sql` | ID Control Nos for plan holders who are already active. **Does not exist yet**: it is generated from *your* live export (step 2), not from local data. |
| `DEPLOY.md` | This checklist. |

The general host notes (folder layout, `.htaccess`, security headers) are in `ci4/DEPLOY.md`; this file is the
checklist for this release.

---

## Before you start

- [ ] You have the staging subdomain + its own empty MySQL database ready (InfinityFree: *Subdomains*, *MySQL Databases*).
- [ ] You know the live database name, user and password (InfinityFree panel). Change the password if it was ever shared.
- [ ] The Anthropic key for the ID check (`governmentidverification.anthropicApiKey`) is at hand.
- [ ] Pick a quiet time: from the export in step 1 until the import in step 4, nobody should approve registrations
      or record payments (otherwise those changes are missing from the numbers generated in step 2 - see step 7).

## Staging first

Do steps 1-8 on staging using a **copy of the live database** (import your live export into the staging database
first), with `app.baseURL` set to the staging address. Run the test list. Only then repeat on live.

## 1. Back up the database (phpMyAdmin)

1. phpMyAdmin -> select the database -> **Export** -> *Quick* -> *SQL* -> Go.
2. Save the file as `live_backup_YYYYMMDD-HHMM.sql` somewhere safe. **This is your way back.** Do not continue without it.
3. In the SQL tab run `SELECT COUNT(*) AS migrations, MAX(version) AS latest FROM migrations;`
   It must show `latest = 2026-11-17-010000` (or later) and the count must **not** already include the nine
   versions listed at the bottom of `partB_migrations.sql`. If it does not match, stop and ask.

## 2. Generate the ID numbers from the live export (done by the developer, on a copy)

Send the export from step 1 to the developer. They will import it into a scratch database, apply
`partB_migrations.sql`, run `php spark idcontrol:backfill` (list only), show **you the list** of plan holders with
the number each would get (oldest approval first, year of each number = year of that holder's approval), and,
after you agree, write `deploy/partD_backfill.sql` with `php spark idcontrol:backfill --sql deploy/partD_backfill.sql`.
Check the file: one `UPDATE` per plan holder, each guarded so an existing number is never overwritten.

## 3. Import the schema changes

1. phpMyAdmin -> database -> **Import** -> choose `partB_migrations.sql` -> keep *SQL*, charset *utf8mb4* -> Go.
2. Expect "Import has been successfully finished". Any red error: **stop**, do not import again, restore the
   backup (step 1) and tell the developer the exact message. (MySQL cannot undo table changes, so never run it twice.)
3. Check: `SELECT version FROM migrations ORDER BY id DESC LIMIT 9;` shows the nine new versions, and
   `SHOW TABLES LIKE 'package_avails';` returns a row.

No data is deleted. The only data write is `''` -> `NULL` for blank ID Control Nos (explained in the file).

## 4. Import the ID numbers

phpMyAdmin -> **Import** -> `partD_backfill.sql`. Check:
`SELECT plan_holder_id, id_control_no FROM plan_holders WHERE id_control_no IS NOT NULL ORDER BY id_control_no;`
matches the list you approved in step 2.

## 5. Upload the files

1. Upload the **contents of `ci4/`** into `htdocs/` (not the `ci4` folder itself). Overwrite existing files.
   Show hidden files so `.htaccess` is included. Do **not** upload your local `.env`, `composer.phar`,
   `tests/`, or anything inside `writable/` (sessions, logs, uploads, imports).
2. New in this release (for reference): `app/Services/ClaimAvailService.php`, `IdControlService.php`, new
   migrations, `app/Commands/*`, `public/assets/js/allocation-preview.js`, `public/assets/css/burial-request.css`,
   new views under `app/Views/{client,partials,admin/reports,reports/pdf}`, and changed routes.
3. `vendor/` is unchanged (dompdf is already there). If the live `vendor/` is missing, upload it from your computer.

## 6. Settings (`.env` on the server)

Edit the server's own `.env` (create it from `.env.production.example` if it does not exist):

```
CI_ENVIRONMENT = production
app.baseURL = 'https://YOUR-DOMAIN/'
app.appTimezone = 'Asia/Manila'

database.default.hostname = sqlXXX.infinityfree.com
database.default.database = if0_XXXXXXXX_caresync
database.default.username = if0_XXXXXXXX
database.default.password = THE-DB-PASSWORD
database.default.DBDriver = MySQLi
database.default.port = 3306

governmentidverification.anthropicApiKey = 'sk-ant-...'   # the ID-check key
```

- `CI_ENVIRONMENT` must be `production` (no debug toolbar, no error details).
- Do **not** add `app.migrateKey`: the database was already updated by the SQL file. (If it is ever present, remove it.)
- The claim/avail rules (down payment 30%, 30 days, terms 6/12/24, minimum months paid 2, ...) are in
  `app/Config/CareSync.php`; they are code, not `.env`.

## 7. Empty the caches and old sessions

On the server, delete the **contents** of `writable/cache/` and `writable/session/` (keep each `index.html`).
Everyone is logged out once; that is expected.

Then, to catch anyone approved between the export (step 1) and now, run in phpMyAdmin:

```sql
SELECT ph.plan_holder_id, u.first_name, u.last_name FROM plan_holders ph JOIN users u ON u.user_id = ph.user_id
WHERE ph.status = 'active' AND (ph.id_control_no IS NULL OR TRIM(ph.id_control_no) = '');
```

It should return no rows. If it returns some, ask the developer to generate a small extra backfill file for them
(approvals made **after** the new code is live are numbered automatically).

## 8. Test list (staging, then live)

Log in with each role and tick:

**General**
- [ ] `/login` opens; page source has no `debugbar` / `DEBUG-VIEW`; `/.env` and `/app/Config/App.php` are refused.
- [ ] A wrong address shows the branded "We could not find that page".

**ID Control No. (Part D)**
- [ ] A plan holder who was already active shows their `KKM-<BRANCH>-<YEAR>-<5 digits>` on *My plan*, the staff
      *client details* page, the ledger PDF and a receipt PDF.
- [ ] Register a new plan holder (Staff -> new client): the form shows "Assigned after approval" in a disabled
      box. Approve the registration: the number appears, e.g. `KKM-CAL-2026-00003`, one higher than the last.
- [ ] The edit-client forms (Staff, Branch Admin, Admin) have no editable ID Control No. box.

**Claims and avails (Part B)** - use a test plan holder with at least 2 months paid
- [ ] Client: *My claims* opens; claim a casket (no balance added); the form shows the quote.
- [ ] Client: avail a package: quote shows 30% down payment, term choice 6/12/24, deadline. Branch Admin
      approves with a term; the client sees the down payment due.
- [ ] Record the down payment (cash, Branch Admin) -> service can start; instalments appear.
- [ ] Record a payment that covers a plan month **and** an instalment: the preview shows the split; the receipt
      PDF lists "Applied to: Plan ... / Instalment 1 of N".
- [ ] A waiver (Branch Admin only, reason of 15+ characters) shows on the claim page, *My claims* and the new
      *Avails and waivers* report.
- [ ] Client dashboard / Payments page show "Plan + Package = Total due" for a client with an avail, and look
      exactly as before for a client without one.

**Reports and dashboards**
- [ ] Overdue report: separate Plan and Package columns; Print, Excel, PDF all open.
- [ ] Remittance and Collections reports: plan / instalments / down payments split.
- [ ] Avails and waivers report (Admin + Branch Admin): Print, Excel, PDF.
- [ ] Dashboards (Admin, Branch Admin, Staff): *Package balances* card; *Needs attention* lists avails waiting for
      a down payment and overdue instalments.
- [ ] Open the receipt PDF and the ledger PDF: text not cut off, peso signs and totals right, fits the page.

**Not available on this host**
- The "cancel after 30 days with no down payment" rule runs whenever an avail is read (no cron needed); there is
  nothing to schedule.

## If something goes wrong

1. Put the previous files back (re-upload the old version of the site).
2. phpMyAdmin -> database -> **Operations**/**Import** the backup from step 1 (drop the tables first if phpMyAdmin
   offers "Drop all"; or create a fresh database and point `.env` at it).
3. Empty `writable/cache` and `writable/session` again.

## What this release does NOT include

- Part E (commission / remittance by collectors) - on hold until the commission question is answered.
- How single services and add-ons are paid is still open with Kaagapay; for now they are treated as avails
  (paid in full up front when under the minimum financed amount).
