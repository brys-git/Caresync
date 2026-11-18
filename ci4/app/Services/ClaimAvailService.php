<?php

namespace App\Services;

use Config\CareSync;

/**
 * THE one place that knows Kaagapay's claim / avail rules. Client pages, staff pages,
 * Branch Admin pages, collector lists, reports and dashboards all ask this class; nothing
 * else decides who may claim or avail, what an avail costs, or what the balance is.
 * The numbers (2 months, 30% down payment, 30 days, 6/12/24 months) live in Config\CareSync.
 *
 * Vocabulary
 *   Claim  = the package that is part of the client's OWN plan (plans.package_id; the
 *            Damayan-flagged package only if the plan has none). No extra charge.
 *   Avail  = any other package. Its price is ADDED to the client's balance, with a down
 *            payment and fixed monthly instalments.
 *   Paid months in this cycle = full monthly contributions in the plan's CURRENT cycle,
 *            from PlanLedgerService (never the stored plans.months_paid column).
 *
 * Rules for both claim and avail
 *   1. At least claimMinMonthsPaid (2) paid months in the current cycle.
 *   2. No unsettled balance from an earlier claim or avail (plan balance after a claim,
 *      package balance of an avail, or an old-rule balance).
 *   3. When that balance is fully paid a new cycle starts; the 2-months rule applies again.
 *   4. Only one request open at a time (pending, approved or ongoing).
 *
 * This step (2) holds eligibility, balances and quotes. Approval, allocation of payments and
 * the screens are built on top of it in the next steps.
 */
class ClaimAvailService
{
    public const TYPE_CLAIM = 'claim';
    public const TYPE_AVAIL = 'avail';

    /**
     * RULE (not yet confirmed by Kaagapay - change it here only): an approved avail uses the
     * plan's entitlement for the current cycle, so the client cannot also claim the plan's own
     * package in that cycle. The cycle closes when the plan balance AND the package balance are
     * both fully paid; then the 2-months rule applies again. Set to false and an avail no
     * longer blocks a later claim in the same cycle.
     */
    public const AVAIL_USES_PLAN_ENTITLEMENT = true;

    /** Label stored in service_applications.rule_version / service_balances.rule_version for new records. */
    public const RULE_VERSION = 'v2';

    private CareSync $config;
    private PlanLedgerService $ledger;

    /** @var array<string,bool> tables that exist (the new tables may not be migrated yet) */
    private static array $tableReady = [];

    /** @var array<string,bool> scopes already expired during this request */
    private static array $expiredScopes = [];

    public function __construct(?CareSync $config = null, ?PlanLedgerService $ledger = null)
    {
        $this->config = $config ?? config(CareSync::class);
        $this->ledger = $ledger ?? new PlanLedgerService();
    }

    /* ======================================================================
     * Plan, package and request type
     * ==================================================================== */

    /** The plan holder's current active plan row, or null. @return array<string,mixed>|null */
    public function planFor(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        return db_connect()->table('plans')
            ->where('plan_holder_id', $planHolderId)
            ->where('status', 'active')
            ->orderBy('plan_id', 'DESC')
            ->get(1)
            ->getRowArray() ?: null;
    }

    /** The package that is part of this plan (the one a CLAIM releases). */
    public function planPackageId(array $plan): int
    {
        $own = (int) ($plan['package_id'] ?? 0);
        if ($own > 0) {
            return $own;
        }

        $row = db_connect()->table('packages')->select('package_id')->where('is_damayan_entitlement', 1)->orderBy('package_id', 'ASC')->get(1)->getRowArray();

        return (int) ($row['package_id'] ?? 0);
    }

    /** 'claim' when the package is the plan's own, otherwise 'avail'. */
    public function requestType(array $plan, int $packageId): string
    {
        return $packageId > 0 && $packageId === $this->planPackageId($plan) ? self::TYPE_CLAIM : self::TYPE_AVAIL;
    }

    /** Full monthly contributions in the plan's current cycle (the ledger, not plans.months_paid). */
    public function monthsPaidInCycle(int $planId): int
    {
        $summary = $this->ledger->summarize($planId);

        return (int) ($summary['months_paid'] ?? 0);
    }

    /* ======================================================================
     * Balances
     * ==================================================================== */

    /**
     * Everything the client still owes, split the way every screen shows it.
     *
     * @return array{plan_id:int, plan_balance:float, package_balance:float, legacy_balance:float,
     *               total_balance:float, plan_monthly:float, package_monthly:float, monthly_total_due:float}
     */
    public function balances(int $planId): array
    {
        $db   = db_connect();
        $plan = $db->table('plans')->where('plan_id', $planId)->get(1)->getRowArray();
        $zero = ['plan_id' => $planId, 'plan_balance' => 0.0, 'package_balance' => 0.0, 'legacy_balance' => 0.0, 'total_balance' => 0.0, 'plan_monthly' => 0.0, 'package_monthly' => 0.0, 'monthly_total_due' => 0.0];
        if (! $plan) {
            return $zero;
        }

        $summary     = $this->ledger->summarize($planId) ?? [];
        $planBalance = max(0.0, round(PlanLedgerService::targetFor($plan) - (float) ($summary['total_paid'] ?? 0), 2));

        $packageBalance = 0.0;
        $packageMonthly = 0.0;
        if ($this->tableReady('package_avails')) {
            $rows = $db->query(
                "SELECT pa.avail_id, pa.package_price, pa.status, pa.monthly_instalment,
                        COALESCE((SELECT SUM(al.amount) FROM payment_allocations al WHERE al.avail_id = pa.avail_id), 0) AS paid
                   FROM package_avails pa
                  WHERE pa.plan_id = ? AND pa.status IN ('awaiting_down_payment', 'active')",
                [$planId]
            )->getResultArray();

            foreach ($rows as $row) {
                $left = max(0.0, round((float) $row['package_price'] - (float) $row['paid'], 2));
                $packageBalance += $left;
                if ($row['status'] === 'active' && $left > 0) {
                    $packageMonthly += (float) $row['monthly_instalment'];
                }
            }
        }

        $legacy = (float) ($db->table('service_balances')
            ->selectSum('remaining_balance', 'total')
            ->where('plan_holder_id', (int) $plan['plan_holder_id'])
            ->where('rule_version', 'legacy')
            ->whereIn('status', ['pending_acknowledgment', 'active'])
            ->where('remaining_balance >', 0)
            ->get()->getRowArray()['total'] ?? 0);

        $planMonthly = (float) $plan['monthly_fee'];

        return [
            'plan_id'           => $planId,
            'plan_balance'      => $planBalance,
            'package_balance'   => round($packageBalance, 2),
            'legacy_balance'    => round($legacy, 2),
            'total_balance'     => round($planBalance + $packageBalance + $legacy, 2),
            'plan_monthly'      => round($planMonthly, 2),
            'package_monthly'   => round($packageMonthly, 2),
            'monthly_total_due' => round($planMonthly + $packageMonthly, 2),
        ];
    }

    /** What is still owed on avails alone (the cycle only closes when this is 0). */
    public function packageOutstanding(int $planId): float
    {
        return $this->balances($planId)['package_balance'];
    }

    /* ======================================================================
     * The claim / avail already made in a cycle, and open requests
     * ==================================================================== */

    /**
     * Has the plan's entitlement already been used in this cycle? A claim of the plan's own
     * package, or (while AVAIL_USES_PLAN_ENTITLEMENT is true) an avail that was not cancelled.
     * Records made before this rule have no request_type: they count when the package is the
     * Damayan entitlement, exactly as before.
     */
    public function entitlementUsedInCycle(int $planHolderId, int $cycleNumber): bool
    {
        $availJoin  = $this->tableReady('package_avails') ? 'LEFT JOIN package_avails pa ON pa.application_id = sa.application_id' : '';
        $availCheck = $this->tableReady('package_avails')
            ? "OR (sa.request_type = 'avail' AND " . (self::AVAIL_USES_PLAN_ENTITLEMENT ? '1' : '0') . " = 1 AND (pa.avail_id IS NULL OR pa.status <> 'cancelled'))"
            : '';

        $row = db_connect()->query(
            "SELECT sa.application_id
               FROM service_applications sa
               LEFT JOIN packages p ON p.package_id = sa.package_id
               $availJoin
              WHERE sa.plan_holder_id = ? AND sa.cycle_number = ? AND sa.status = 'approved'
                AND (sa.request_type = 'claim' $availCheck
                     OR (sa.request_type IS NULL AND p.is_damayan_entitlement = 1))
              LIMIT 1",
            [$planHolderId, $cycleNumber]
        )->getRowArray();

        return $row !== null;
    }

    /**
     * The request that is still open: waiting for review, or approved and not completed or
     * cancelled yet (and, for an avail, not cancelled for a missing down payment).
     *
     * @return array<string,mixed>|null
     */
    public function openRequest(int $planHolderId): ?array
    {
        $db         = db_connect();
        $availJoin  = $this->tableReady('package_avails') ? 'LEFT JOIN package_avails pa ON pa.application_id = sa.application_id' : '';
        $serviceKey = $this->tableReady('package_avails') ? 'COALESCE(sb.service_id, pa.service_id)' : 'sb.service_id';
        $availOk    = $this->tableReady('package_avails') ? "AND (pa.avail_id IS NULL OR pa.status <> 'cancelled')" : '';

        return $db->query(
            "SELECT sa.application_id, sa.status, sa.request_type, sa.created_at, sa.package_id, s.status AS service_status
               FROM service_applications sa
               LEFT JOIN service_balances sb ON sb.application_id = sa.application_id
               $availJoin
               LEFT JOIN services s ON s.service_id = $serviceKey AND s.deleted_at IS NULL
              WHERE sa.plan_holder_id = ?
                AND (sa.status = 'pending'
                     OR (sa.status = 'approved' AND (s.service_id IS NULL OR s.status IN ('pending', 'ongoing')) $availOk))
              ORDER BY sa.application_id DESC
              LIMIT 1",
            [$planHolderId]
        )->getRowArray() ?: null;
    }

    /* ======================================================================
     * Eligibility
     * ==================================================================== */

    /**
     * May this plan holder claim or avail this package right now?
     *
     * @return array{allowed:bool, type:string, code:string, message:?string, reasons:list<array{code:string,message:string}>,
     *               plan_id:int, cycle_number:int, months_paid:int, months_needed:int, balances:array<string,float>}
     */
    public function eligibility(int $planHolderId, int $packageId): array
    {
        // Lazy expiry (the host has no scheduled tasks): a down payment that was never made
        // is cancelled the next time anyone looks at this client's requests.
        $this->expireDownPayments($planHolderId);

        $needed = (int) $this->config->claimMinMonthsPaid;
        $plan   = $this->planFor($planHolderId);
        $base   = ['allowed' => false, 'type' => self::TYPE_AVAIL, 'code' => '', 'message' => null, 'reasons' => [], 'plan_id' => 0, 'cycle_number' => 0, 'months_paid' => 0, 'months_needed' => $needed, 'balances' => []];

        if ($plan === null) {
            return $this->denied($base, 'no_plan', 'Become an active, eligible Plan Holder to claim or avail a package.');
        }

        $planId = (int) $plan['plan_id'];
        $type   = $this->requestType($plan, $packageId);
        $base   = ['type' => $type, 'plan_id' => $planId] + $base;

        $summary = $this->ledger->summarize($planId) ?? [];
        if (($summary['lifecycle'] ?? '') === PlanLedgerService::NOT_STARTED) {
            return $this->denied($base, 'not_active', 'Your plan is not active yet.');
        }
        $access = $summary['service_access'] ?? ['allowed' => true, 'reason' => null];
        if (empty($access['allowed'])) {
            return $this->denied($base, 'standing', (string) ($access['reason'] ?? 'Your plan is not in good standing.'));
        }

        // Rules 2 and 3: if the cycle's entitlement was used and the balance is all paid, the cycle
        // should already have moved on - close it now (this is idempotent) and look again.
        $cycleNo = (int) ($plan['current_cycle_number'] ?? 1);
        if ($this->entitlementUsedInCycle($planHolderId, $cycleNo) && $this->balances($planId)['total_balance'] <= 0.004) {
            (new CycleService())->closeCycleIfSettled($planId);
            $plan    = $this->planFor($planHolderId) ?? $plan;
            $cycleNo = (int) ($plan['current_cycle_number'] ?? 1);
            $summary = $this->ledger->summarize($planId) ?? $summary;
        }

        $balances = $this->balances($planId);
        $months   = (int) ($summary['months_paid'] ?? 0);
        $base     = ['cycle_number' => $cycleNo, 'months_paid' => $months, 'balances' => $balances] + $base;

        $reasons = [];

        // Rule 4: one open request at a time.
        if ($this->openRequest($planHolderId) !== null) {
            $reasons[] = ['code' => 'open_request', 'message' => 'You already have a claim or avail in progress. You can make another request once it is completed.'];
        }

        // Rules 2 and 3: an unsettled balance from an earlier claim or avail.
        $usedThisCycle = $this->entitlementUsedInCycle($planHolderId, $cycleNo);
        if ($balances['legacy_balance'] > 0.004 || ($usedThisCycle && $balances['total_balance'] > 0.004)) {
            $reasons[] = ['code' => 'unsettled_balance', 'message' => 'You can request another service after your remaining balance of ' . $this->money($balances['total_balance']) . ' is settled and ' . $needed . ' more months are paid.'];
        } elseif ($usedThisCycle) {
            $reasons[] = ['code' => 'cycle_in_progress', 'message' => 'Your balance is settled. You can request another service after ' . $needed . ' months are paid in your new cycle.'];
        }

        // Rule 1: the 2-months minimum, counted in the current cycle.
        if ($months < $needed) {
            $reasons[] = ['code' => 'min_months', 'message' => "You need at least {$needed} paid months in your current cycle before you can claim or avail a package. You have paid {$months}."];
        }

        if ($reasons !== []) {
            $base['reasons'] = $reasons;

            return $this->denied($base, $reasons[0]['code'], $reasons[0]['message'], $reasons);
        }

        return ['allowed' => true, 'code' => 'ok', 'message' => null, 'reasons' => []] + $base;
    }

    /* ======================================================================
     * Quotes (what a claim / avail will cost) - used by the form, the approval screen and the receipts
     * ==================================================================== */

    /**
     * What a claim or avail of this package amounts to.
     *
     * @param int|null $termMonths  the term the Branch Admin chose (null = not chosen yet: no instalments)
     * @param bool     $waive       down payment waived by the Branch Admin
     *
     * @return array<string,mixed>
     */
    public function quote(int $planHolderId, int $packageId, ?int $termMonths = null, bool $waive = false): array
    {
        $plan    = $this->planFor($planHolderId);
        $package = db_connect()->table('packages')->select('package_id, package_name, base_price')->where('package_id', $packageId)->get(1)->getRowArray();
        if ($plan === null || $package === null) {
            return ['ok' => false, 'error' => 'Plan or package not found.'];
        }

        $planId   = (int) $plan['plan_id'];
        $type     = $this->requestType($plan, $packageId);
        $balances = $this->balances($planId);
        $price    = round((float) $package['base_price'], 2);

        $quote = [
            'ok'                    => true,
            'type'                  => $type,
            'plan_id'               => $planId,
            'package'               => ['package_id' => (int) $package['package_id'], 'name' => (string) $package['package_name'], 'price' => $price],
            'plan_balance'          => $balances['plan_balance'],
            'plan_monthly'          => $balances['plan_monthly'],
            // A claim releases the plan's own package free; an avail adds the package price.
            'package_amount_added'  => $type === self::TYPE_AVAIL ? $price : 0.0,
            'total_balance_after'   => round($balances['total_balance'] + ($type === self::TYPE_AVAIL ? $price : 0.0), 2),
            'down_payment_rate'     => 0.0,
            'down_payment_amount'   => 0.0,
            'down_payment_days'     => (int) $this->config->availDownPaymentDays,
            'waived'                => false,
            'financed_amount'       => 0.0,
            'term_months'           => null,
            'terms_offered'         => $this->config->availTerms(),
            'monthly_instalment'    => null,
            'schedule'              => [],
            'monthly_total_due'     => $balances['plan_monthly'],
        ];

        if ($type === self::TYPE_CLAIM) {
            return $quote;
        }

        $rate     = (float) $this->config->availDownPaymentRate;
        $downCents = $waive ? 0 : (int) round($price * $rate * 100);
        $financed  = round($price - $downCents / 100, 2);

        $quote['down_payment_rate']   = $rate;
        $quote['down_payment_amount'] = round($downCents / 100, 2);
        $quote['waived']              = $waive;
        $quote['financed_amount']     = $financed;

        if ($termMonths !== null) {
            if (! in_array($termMonths, $this->config->availTerms(), true)) {
                return ['ok' => false, 'error' => 'Choose a term of ' . implode(', ', $this->config->availTerms()) . ' months.'] + $quote;
            }
            $schedule                    = self::instalmentSchedule($financed, $termMonths, date('Y-m-d'));
            $quote['term_months']        = $termMonths;
            $quote['schedule']           = $schedule;
            $quote['monthly_instalment'] = $schedule[0]['amount'] ?? 0.0;
            $quote['monthly_total_due']  = round($balances['plan_monthly'] + (float) $quote['monthly_instalment'], 2);
        }

        return $quote;
    }

    /**
     * Guard used before an avail record is written (second line of defence besides the NOT NULL
     * columns): the rate must be above 0 unless the down payment is waived, and the term must be
     * one of the offered terms.
     *
     * @throws \InvalidArgumentException
     */
    public function assertAvailTerms(float $rate, bool $waived, int $termMonths): void
    {
        if (! $waived && $rate <= 0) {
            throw new \InvalidArgumentException('The down-payment rate must be above 0 unless the down payment is waived.');
        }
        if ($rate < 0 || $rate > 1) {
            throw new \InvalidArgumentException('The down-payment rate must be between 0 and 1.');
        }
        if (! in_array($termMonths, $this->config->availTerms(), true)) {
            throw new \InvalidArgumentException('The term must be one of ' . implode(', ', $this->config->availTerms()) . ' months.');
        }
    }

    /**
     * The fixed monthly instalments for an amount: amount / term rounded to centavos, with the
     * last instalment absorbing any rounding difference (35,000 / 12 = 2,916.67 x 11 + 2,916.63).
     * Pure - no database.
     *
     * @return list<array{number:int, due:string, amount:float}>
     */
    public static function instalmentSchedule(float $financed, int $termMonths, string $firstDue): array
    {
        $termMonths = max(1, $termMonths);
        $cents      = (int) round($financed * 100);
        $each       = (int) round($cents / $termMonths);
        $last       = $cents - $each * ($termMonths - 1);
        if ($last < 0) {            // tiny amounts over a long term
            $each = intdiv($cents, $termMonths);
            $last = $cents - $each * ($termMonths - 1);
        }

        $rows = [];
        for ($n = 1; $n <= $termMonths; $n++) {
            $rows[] = ['number' => $n, 'due' => PlanLedgerService::addMonths($firstDue, $n - 1), 'amount' => round(($n === $termMonths ? $last : $each) / 100, 2)];
        }

        return $rows;
    }

    /**
     * When the first instalment is due: one month after the down payment was verified (or the
     * waiver approved), on the same day of the month as the plan's own due date - the first such
     * date that is not earlier than that month.
     */
    public static function firstDueDate(string $verifiedOn, int $planDueDay): string
    {
        $earliest = PlanLedgerService::addMonths($verifiedOn, 1);
        [$y, $m]  = array_map('intval', explode('-', substr($earliest, 0, 7)));

        for ($i = 0; $i < 2; $i++) {
            $dim = (int) date('t', mktime(0, 0, 0, $m, 1, $y));
            $due = sprintf('%04d-%02d-%02d', $y, $m, min(max(1, $planDueDay), $dim));
            if (strcmp($due, $earliest) >= 0) {
                return $due;
            }
            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }

        return $earliest;
    }

    /** The day of the month the plan's contributions fall due (the ledger's anchor day). */
    public function planDueDay(array $plan): int
    {
        $marker = (string) ($plan['contribution_cycle_started_at'] ?? '');
        $source = $marker !== '' ? $marker : (string) ($plan['start_date'] ?? date('Y-m-d'));

        return (int) date('j', strtotime($source));
    }

    /* ======================================================================
     * Lazy expiry of unpaid down payments (no cron on the host)
     * ==================================================================== */

    /**
     * Cancels avails whose down payment was not verified within availDownPaymentDays of approval:
     * "Cancelled - no down payment". Its service is cancelled, its package amount leaves the
     * balance (balances() only counts awaiting/active avails), and the client and the branch
     * admins are told. Called whenever an avail is read, and by `php spark avail:expire`.
     * One cheap indexed query; it does nothing once an avail is no longer waiting.
     *
     * @param int|null $planHolderId limit to one plan holder (null = everyone)
     *
     * @return int how many avails were cancelled
     */
    public function expireDownPayments(?int $planHolderId = null): int
    {
        if (! $this->tableReady('package_avails')) {
            return 0;
        }

        $scope = (string) ($planHolderId ?? 'all');
        if (isset(self::$expiredScopes[$scope])) {
            return 0;
        }
        self::$expiredScopes[$scope] = true;

        $db      = db_connect();
        $builder = $db->table('package_avails pa')
            ->select('pa.avail_id, pa.service_id, pa.plan_id, pa.branch_id, pa.approved_by, pa.package_price, ph.user_id AS client_user_id, ph.plan_holder_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = pa.plan_holder_id', 'inner')
            ->where('pa.status', 'awaiting_down_payment')
            ->where('pa.down_payment_status', 'due')
            ->where('pa.down_payment_due_by <', date('Y-m-d'));
        if ($planHolderId !== null) {
            $builder->where('pa.plan_holder_id', $planHolderId);
        }

        $count = 0;
        foreach ($builder->get()->getResultArray() as $avail) {
            $db->transStart();
            $db->table('package_avails')->where('avail_id', (int) $avail['avail_id'])->where('status', 'awaiting_down_payment')->update([
                'status'           => 'cancelled',
                'cancelled_reason' => 'Cancelled - no down payment',
                'cancelled_at'     => date('Y-m-d H:i:s'),
            ]);
            if ((int) $avail['service_id'] > 0) {
                $db->table('services')->where('service_id', (int) $avail['service_id'])->where('status', 'pending')->update(['status' => 'cancelled']);
            }
            $db->transComplete();

            if (! $db->transStatus()) {
                continue;
            }
            $count++;

            $notify = new NotificationService();
            $notify->notify((int) $avail['client_user_id'], 'Your package request was cancelled because the down payment was not paid in time. The package amount was removed from your balance.', 'general', false, false, (int) $avail['plan_id']);

            $admins = $db->table('users')->select('user_id')->where('role_id', 2)->where('status', 'active')->where('deleted_at', null);
            if ((int) $avail['branch_id'] > 0) {
                $admins->where('branch_id', (int) $avail['branch_id']);
            }
            foreach ($admins->get()->getResultArray() as $admin) {
                $notify->notify((int) $admin['user_id'], 'An avail (request #' . (int) $avail['avail_id'] . ') was cancelled - no down payment was received in time.', 'general');
            }

            $actor = (int) ($avail['approved_by'] ?: $avail['client_user_id']);
            (new ActivityLogService())->log($actor, 'cancelled', 'package_avail', (int) $avail['avail_id'], 'Avail cancelled - no down payment within ' . (int) $this->config->availDownPaymentDays . ' days');
        }

        return $count;
    }

    /** Forget which scopes were already checked (tests, and the spark command). */
    public static function resetExpiryMemory(): void
    {
        self::$expiredScopes = [];
    }

    /* ======================================================================
     * Small helpers
     * ==================================================================== */

    private function tableReady(string $table): bool
    {
        return self::$tableReady[$table] ??= db_connect()->tableExists($table);
    }

    private function money(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }

    /**
     * @param array<string,mixed>                          $base
     * @param list<array{code:string,message:string}>|null $reasons
     *
     * @return array<string,mixed>
     */
    private function denied(array $base, string $code, string $message, ?array $reasons = null): array
    {
        return ['allowed' => false, 'code' => $code, 'message' => $message, 'reasons' => $reasons ?? [['code' => $code, 'message' => $message]]] + $base;
    }
}
