# End-to-end test checklist (localhost, test data)

Follow this by hand, or run the automated version (the same steps, ids in brackets) described at the bottom.
Use a **test copy of the database** (`caresync_test`), never the real `kaagapay_db`, and sign in with test accounts.

Setup: XAMPP running, the app at `http://localhost/caresync/ci4/public/` (or `php spark serve`), test database imported from a fresh
`mysqldump` of `kaagapay_db`. Settings under test: down payment 30 %, 30 days, terms 6/12/24, at least 2 paid months to claim/avail,
commission 10 % of **plan contributions only** (`Config\CareSync::$commissionAppliesTo = 'plan_only'`).

## 1. System Admin
| Step | What to do | Expected |
| --- | --- | --- |
| A0 | Sign in as System Admin | Admin dashboard with "Needs attention" |
| A1.1 | Branches -> Add branch **without** a manager | Saved, gets a branch code |
| A1.3 | Dashboard | "Branches with no manager yet" counts it |
| A1.4 | Add a branch **with** a manager (pick a Branch Admin) | Saved, manager shown |
| A2.1-A2.4 | Users -> Add: Branch Admin, Staff (tick "can collect"), Collector, System Admin | Each saved with a one-time temporary password; role and branch right; the Government ID step is required (upload any test image: "needs staff review" is fine on a box without the ID-check key) |
| A2.6 | Sign in as the new user | Works |
| A2.7 | Edit a user's name | Saved |
| A2.8 | Reset password | New temporary password shown, the old one stops working |
| A2.9-A2.11 | Deactivate a user who is signed in elsewhere | On their next click they are signed out; they cannot sign in again |
| A2.12 | Reactivate | Can sign in |
| A3.1 | Plan builder -> new plan, price **20,000**, monthly **400** | Saved, approved/active, appears at registration |
| J12-J15 | Compare dashboard "Package balances" / "Accounts with overdue payments" with the Avails and Overdue reports | Same numbers |

## 2. Branch Admin
| Step | What to do | Expected |
| --- | --- | --- |
| B1 | Plan holders -> Register: pick the new plan, upload a test ID, add a beneficiary | Created, on the 20,000/400 plan, **ID Control No. assigned at once** for a branch-registered (already active) holder (`KKM-<3 letters>-<year>-00001`) |
| B2 | Payment Tracking -> Record cash, 2 months | Paid; ledger shows 2 months; claim form opens (needs 2 paid months) |
| C1-C2 | File a casket claim for her (death certificate + beneficiary ID) | Pending request |
| D1-D4 | Approve it; Ongoing services: Start, then Complete | Approved with no amount owed; service pending -> ongoing -> completed |
| E2-E3 | A second holder applies for a package as a client; approve with **12 months** | Down payment = 30 % of the price, due in 30 days; instalment = rest / 12; service cannot start yet |
| E3.5 | Record the down payment (cash) | Avail becomes active, 12 instalments written; service can start |
| E4 | Record a payment of one instalment + 400 | Preview and receipt show "Applied to: Instalment 1 of 12 / Plan <month>" |
| F3-F8 | A third holder's avail: approve with **waiver** (reason under 15 characters is refused; a real reason works) | Waiver (who, when, why) on the request page, the client's My claims and the Avails and waivers report |
| H4-H11 | See Collector section | Remittance confirm / discrepancy |
| J1-J11 | Reports: Overdue (as of / due from-to), Avails and waivers, Remittance (split totals), Commission; Print / Excel / PDF each; Ledger PDF | All open; package columns present |

## 3. Staff
| Step | What to do | Expected |
| --- | --- | --- |
| G1 | Payments -> record cash 2 months | Counted by the ledger immediately |
| G2 | File a claim **for a beneficiary** (choose the registered beneficiary + relationship) | Claim filed, claimant and filer recorded |
| G3-G4 | Open an avail request, add a note | Note shows on the Branch Admin's page |
| G5 | Open an avail request | No waiver / money terms for Staff |

## 4. Collector
| Step | What to do | Expected |
| --- | --- | --- |
| H0 | (Branch Admin) assign the collector to the whole branch | Saved |
| H1 | Collector -> Collection list | Branch plan holders listed with plan + package amounts |
| H2 | Record payment for a client with a package: enter an amount | Preview shows where it goes; saved as **pending** until the Branch Admin verifies |
| H4 | (Branch Admin) verify it | Paid, split plan / instalment |
| H5-H6 | Collector -> Remit collections: tick the collection | Gross, commission (10 % of the plan part only), net to hand over; submit |
| H7-H9 | (Branch Admin) Remittances -> open -> Confirm received | Commission becomes earned; collector notified |
| H10 | Another remittance: Report discrepancy (amount received + reason) | Status discrepancy, commission on hold, collector notified |
| H11-H12 | Commission report | Earned / waiting / on hold; no "Mark as paid" |

## 5. Client
| Step | What to do | Expected |
| --- | --- | --- |
| E2.0-E2.2 | Sign in as the client; Services -> apply for a package | Pending request on **My claims** |
| E4.4-E4.5 | Dashboard and Payments | "Plan contribution + Package instalment = Total due"; "Plan balance + Package balance = Total balance"; pending payments marked "pending" |
| I2 | Make payment -> GCash, amount + 13-digit reference | Recorded as pending |
| I4 | (Collector) approves the GCash payment | Paid and applied |
| I6-I7 | Download the receipt and the ledger PDF | Receipt has "Applied to" lines; ledger has plan and package sections |
| I9 | My plan | Shows the ID Control No. |

## 6. Security
| Step | What to do | Expected |
| --- | --- | --- |
| K1 | Send a POST without / with a wrong CSRF token (e.g. with the browser dev tools, or `curl -X POST`) | HTTP 403, nothing changes |
| K4 | Enter a wrong password 6 times for the same account | 6th shows "Too many sign-in attempts", even the right password is refused for a minute |
| K5 | Deactivate a signed-in user | Signed out on the next request |
| K2 | Branch Admin of branch B opens a client, ledger, claim, receipt or remittance of branch A (by URL) | Not found / refused |
| K3 | A plan holder opens another client's receipt, or staff pages | Refused |

## Automated version
`php run_all.php` (kept outside the repository, in the test tooling) runs exactly these steps over HTTP against the test server
(`php -S localhost:8081` with the test database) and prints PASS/FAIL per id above.

## Last automated run (19 Nov 2026, caresync_test)
132 checks, 132 passed, 0 failed (sections A-K above). Real bugs the run found and that were fixed first:
1. Staff created with a temporary password lost the "can collect" flag.
2. The first cash payment of an already-active holder was not counted by the ledger (no cycle number).
3. Approving a collector's payment overwrote who collected it (breaking remittances).
4. The Branch Admin Remittance report returned an error (methods removed by mistake in the Part E commit).
Not exercised: the real Government ID check (no API key on this machine; the "needs staff review" path was used).
