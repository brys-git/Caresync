# Deploying Parts B, D and E (claims/avails, payment allocation, reports, ID Control No., collector remittances)

For: InfinityFree (no SSH, no cron, phpMyAdmin only). Do it on the **staging subdomain first**, then on the
live site. Do not merge `feature/part-b` into `main` until staging has passed the test list below.

Files in this folder:

| File | What it is |
| --- | --- |
| `DEPLOY.md` | This checklist. |
| `partB_migrations.sql` | **Fallback** for the database changes (11 migrations: Part B, Part D, Part E). Import **once**, and only if you do *not* use the `/migrate/<key>` page (Method A). |
| `partD_backfill.sql` | ID Control Nos for plan holders who are already active. **Does not exist yet**: it is generated from *your* live export (step 2), not from local data. |
| `E2E_CHECKLIST.md` | The hand-followable end-to-end test (the same steps the automated run does). |

The general host notes (folder layout, `.htaccess`, security headers) are in `ci4/DEPLOY.md`; this file is the
checklist for this release.

---

## Which way to update the database

| | Method A (**use this**) | Method B (fallback) |
| --- | --- | --- |
| How | upload the files, open `https://<site>/migrate/<key>` once | import `partB_migrations.sql` in phpMyAdmin |
| What it does | applies **whatever the database is missing**, in order - it does not matter exactly which level live is at | needs live to be exactly at `2026-11-17-010000`; older levels need `pre_partB.sql` first |
| Records it in `migrations` | yes, by itself | yes, the last step of the file |
| Use when | always, **on staging first** | the `/migrate` page fails or cannot be reached |

Use **one** of them on a database, never both (the second would stop with "already exists").
Both were tested locally against a copy of the pre-Part-B database: the resulting schema is identical to the
development database (every table, column, index and key).

---

## Before you start

- [ ] You have the staging subdomain + its own empty MySQL database ready (InfinityFree: *Subdomains*, *MySQL Databases*).
- [ ] You know the live database name, user and password (InfinityFree panel). Change the password if it was ever shared.
- [ ] The Anthropic key for the ID check (`governmentidverification.anthropicApiKey`) is at hand.
- [ ] Pick a quiet time: from the export in step 1 until the update in step 3, nobody should approve registrations
      or record payments (otherwise those changes are missing from the numbers generated in step 2 - see step 7).

## Staging first

Do steps 1-8 on staging using a **copy of the live database** (import your live export into the staging database
first), with `app.baseURL` set to the staging address. Run the test list. Only then repeat on live.

## 1. Back up the database (phpMyAdmin)

1. phpMyAdmin -> select the database -> **Export** -> *Quick* -> *SQL* -> Go.
2. Save the file as `live_backup_YYYYMMDD-HHMM.sql` somewhere safe. **This is your way back.** Do not continue without it.
3. Look at the migration level (on staging, and on live right before step 3). In the SQL tab:

   ```sql
   SELECT version, class FROM migrations ORDER BY version DESC LIMIT 12;
   ```

   - Method A works from **any** level. Note the newest version for the record.
   - Method B only: the newest row must be `2026-11-17-010000` (or a later version that is *not* one of the eleven
     below), with **no** row for `2026-11-19-000000` ... `2026-11-21-000000`. Older than `2026-11-17-010000` ->
     stop, paste the result to the developer (a `deploy/pre_partB.sql` is needed first). Any of the eleven already
     present -> stop, do not import the file.

## 2. Generate the ID numbers from the live export (done by the developer, on a copy)

Send the export from step 1 to the developer. They will import it into a scratch database, update its schema,
run `php spark idcontrol:backfill` (list only), show **you the list** of plan holders with the number each would
get (oldest approval first, year of each number = year of that holder's approval), and, after you agree, write
`deploy/partD_backfill.sql` with `php spark idcontrol:backfill --sql deploy/partD_backfill.sql`. The export is
used only in that scratch database and deleted afterwards.
Check the file: one `UPDATE` per plan holder, each guarded so an existing number is never overwritten.

## 3. Update the database

### Method A - the `/migrate/<key>` page (primary)

1. Upload the files first (step 5), so the server has the new migration files.
2. In the server's `.env` add one line with a long random value (at least 16 characters, letters and digits):

   ```
   app.migrateKey = 'PUT-A-LONG-RANDOM-VALUE-HERE'
   ```

3. Open `https://<your-site>/migrate/PUT-A-LONG-RANDOM-VALUE-HERE` **once**. Expected answer (plain text):
   *"Database is up to date."* Any other answer (HTTP 500): stop, do not repeat it, restore the backup (step 1)
   and send the developer the last lines of `writable/logs/log-*.log`. A wrong key shows the normal "page not
   found" page - that is how it is meant to look.
4. **Right away delete the `app.migrateKey` line from `.env`** (the page does nothing without it, but a key that
   stays on the server is a door that stays unlocked). Check the page is closed again: the same address must now
   show "page not found".
5. Check: `SELECT version FROM migrations ORDER BY id DESC LIMIT 11;` ends with `2026-11-21-000000`, and
   `SHOW TABLES LIKE 'remittances';` returns a row.

### Method B - the SQL file (fallback)

1. phpMyAdmin -> database -> **Import** -> `partB_migrations.sql` -> *SQL*, charset *utf8mb4* -> Go.
2. Expect "Import has been successfully finished". Any red error: **stop**, do not import again, restore the
   backup (step 1) and tell the developer the exact message. (MySQL cannot undo table changes: never run it twice.)
3. Check as in Method A, point 5.

No data is deleted by either method. Existing payments keep working exactly as before (`awaiting_remittance` = 0).
The only data write is `''` -> `NULL` for blank ID Control Nos (section 7b of the SQL file; the same thing is
done by the Part D migration).

## 4. Import the ID numbers

phpMyAdmin -> **Import** -> `partD_backfill.sql`. Check:
`SELECT plan_holder_id, id_control_no FROM plan_holders WHERE id_control_no IS NOT NULL ORDER BY id_control_no;`
matches the list you approved in step 2.

## 5. Upload the files

(Do this **before** Method A in step 3.)

1. Upload the **contents of `ci4/`** into `htdocs/` (not the `ci4` folder itself). Overwrite existing files.
   Show hidden files so `.htaccess` is included. Do **not** upload your local `.env`, `composer.phar`,
   `tests/`, or anything inside `writable/` (sessions, logs, uploads, imports).
2. `vendor/` is unchanged (dompdf is already there). If the live `vendor/` is missing, upload it from your computer.
3. Deleted in this release (remove them from the server if they are still there): the old `PlanHolders::store`
   registration route is gone (nothing to delete on the server, the new `PlanHolders.php` replaces it).

## 6. Settings

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
- `app.migrateKey` is **only** there for the minutes it takes to run Method A. It must not be in `.env` afterwards.
- The business rules are code, in `app/Config/CareSync.php`, not `.env`: down payment 30%, 30 days, terms 6/12/24,
  minimum months paid 2, and for collectors: `commissionRate` 0.10, `commissionAppliesTo` `all_collections`
  (confirmed by Kaagapay; `plan_only` is the other option), `remitGcash` true, `remittanceMaxDays` 7,
  `monthCloseDueDay` 5. Change them there and re-upload that one file.

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

Log in with each role and tick. (`E2E_CHECKLIST.md` has the long version with expected results.)

**General**
- [ ] `/login` opens; page source has no `debugbar` / `DEBUG-VIEW`; `/.env` and `/app/Config/App.php` are refused.
- [ ] A wrong address shows the branded "We could not find that page".
- [ ] `/migrate/anything` shows "page not found" (the update page is closed).
- [ ] `POST /plan-holders/store` no longer exists (the old registration route): registration only works through
      the *Register a plan holder* wizard.

**ID Control No. (Part D)**
- [ ] A plan holder who was already active shows their `KKM-<BRANCH>-<YEAR>-<5 digits>` on *My plan*, the staff
      *client details* page, the ledger PDF and a receipt PDF.
- [ ] Register a new plan holder (Staff -> new client): the form shows "Assigned after approval" in a disabled
      box. Approve the registration: the number appears, e.g. `KKM-CAL-2026-00003`, one higher than the last.
- [ ] The edit-client forms (Staff, Branch Admin, Admin) have no editable ID Control No. box.

**Plans**
- [ ] With **one** active plan, a new registration (any role) is on that plan: its monthly fee, not PHP 240.
      With two or more, the plan picker shows and the chosen plan is stored.

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

**Collectors, remittances and the monthly close (Part E)**
- [ ] Branch Admin assigns a collector an area. The collector's *Remit collections* page lists everyone in that area
      with Collected / Remitted / Confirmed / Not collected / Remaining and a line like
      "32 of 50 collected, 3 not collected, 15 remaining".
- [ ] Collector records a cash payment: the client sees **"Paid to collector - waiting for office confirmation"**
      with the OR number and is **not** overdue; the payment is not counted as money in the dashboards yet.
- [ ] A client pays by GCash; the collector checks the reference and approves: same state, and it appears in
      *Remit collections* (GCash shown separately from cash).
- [ ] Collector submits a remittance of some of the collections (a second one later the same month is fine):
      gross, cash / GCash, commission (10% of everything collected) and net are shown; the Branch Admin gets a
      notification.
- [ ] Branch Admin: *Remittances* -> open -> **Confirm received PHP <net>**: that remittance's payments become *paid*
      (ledger, receipts, client notified once); other remittances are untouched. **Report a discrepancy** on another:
      only that one goes on hold, its payments stay "waiting".
- [ ] *Mark not collected* needs a reason (Other needs a note). *Close <month>* stays disabled while a plan holder is
      unaccounted for or a collection is not in a **confirmed** remittance; once everything is confirmed it closes.
- [ ] Reports -> *Monthly collection summary* (Admin and Branch Admin): the closed month, with Print / Excel / PDF.
- [ ] A collection that has waited more than 7 days shows under *Needs attention* (Branch Admin) and on the
      collector's page; a month not closed by the 5th shows "Collector X hasn't closed <month>".
- [ ] The old *Mark as paid* button is gone from the Commission report, which shows earned / waiting / on hold.

**Reports and dashboards**
- [ ] Overdue report: separate Plan and Package columns; Print, Excel, PDF all open.
- [ ] Remittance and Collections reports: plan / instalments / down payments split.
- [ ] Avails and waivers report (Admin + Branch Admin): Print, Excel, PDF.
- [ ] Dashboards (Admin, Branch Admin, Staff): *Package balances* card; *Needs attention* lists avails waiting for
      a down payment and overdue instalments.
- [ ] Open the receipt PDF and the ledger PDF: text not cut off, peso signs and totals right, fits the page.

**Not available on this host**
- Nothing runs on a schedule (no cron): the "cancel after 30 days with no down payment" rule runs whenever an avail
  is read, and the "not remitted for 7 days" / "month not closed by the 5th" warnings are worked out whenever the
  Branch Admin dashboard or the collector's page opens.

## If something goes wrong

1. Put the previous files back (re-upload the old version of the site).
2. phpMyAdmin -> database -> **Operations**/**Import** the backup from step 1 (drop the tables first if phpMyAdmin
   offers "Drop all"; or create a fresh database and point `.env` at it).
3. Empty `writable/cache` and `writable/session` again. Make sure `app.migrateKey` is not in `.env`.

## What this release does NOT include

- How single services and add-ons are paid is still open with Kaagapay; for now they are treated as avails
  (paid in full up front when under the minimum financed amount).
- The old commission-payout classes and the `commission_payouts` table are still in the code and database, unused
  (the *Mark as paid* screen is gone); removing them is a separate clean-up.
