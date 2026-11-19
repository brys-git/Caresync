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

    public const ROLE_BRANCH_ADMIN = 2;
    public const ROLE_STAFF        = 3;

    /** A waiver of the down payment needs a written reason of at least this many characters. */
    public const WAIVER_REASON_MIN = 15;

    /** A rejection needs a reason (shown to the client). */
    public const REJECTION_REASON_MIN = 5;

    /**
     * RULE (not yet confirmed by Kaagapay - change it here only): an approved avail uses the
     * plan's entitlement for the current cycle, so the client cannot also claim the plan's own
     * package in that cycle. The cycle closes when the plan balance AND the package balance are
     * both fully paid; then the 2-months rule applies again. Set to false and an avail no
     * longer blocks a later claim in the same cycle.
     */
    public const AVAIL_USES_PLAN_ENTITLEMENT = true;

    /**
     * RULE (not yet confirmed by Kaagapay - change it here only): how single services and paid
     * add-ons are paid. Until Kaagapay answers, they are treated as an AVAIL:
     *   - a single service on its own (e.g. Balik Probinsya) = an avail of that service's price;
     *   - add-ons on a CLAIM (e.g. Burial Attire) = their total is added to the balance as a package
     *     amount, with the same down payment and instalment rules;
     *   - add-ons on an AVAIL = added to the avail's package price.
     * They need the Branch Admin (money terms) and the same 2-month rule. A small amount (below
     * Config\CareSync::availMinFinancedAmount once the down payment is taken) is paid in full up
     * front instead of in instalments. Set to false and services / add-ons owe nothing (the old,
     * incorrect behaviour) - not recommended.
     */
    public const PRICED_EXTRAS_ARE_PACKAGE_AMOUNTS = true;

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
    public function openRequest(int $planHolderId, ?int $ignoreApplicationId = null): ?array
    {
        $db         = db_connect();
        $availJoin  = $this->tableReady('package_avails') ? 'LEFT JOIN package_avails pa ON pa.application_id = sa.application_id' : '';
        $link       = $this->columnReady('service_applications', 'service_id') ? 'sa.service_id, ' : '';
        $serviceKey = $this->tableReady('package_avails') ? "COALESCE({$link}sb.service_id, pa.service_id)" : "COALESCE({$link}sb.service_id)";
        // A cancelled avail / service closes the request; a cancelled ADD-ON on a claim does not (the casket claim stands).
        $availOk    = $this->tableReady('package_avails') ? "AND (pa.avail_id IS NULL OR pa.status <> 'cancelled' OR pa.kind = 'addons')" : '';

        return $db->query(
            "SELECT sa.application_id, sa.status, sa.request_type, sa.created_at, sa.package_id, sa.awaiting_death_certificate, s.status AS service_status
               FROM service_applications sa
               LEFT JOIN service_balances sb ON sb.application_id = sa.application_id
               $availJoin
               LEFT JOIN services s ON s.service_id = $serviceKey AND s.deleted_at IS NULL
              WHERE sa.plan_holder_id = ? AND sa.application_id <> ?
                AND (sa.status = 'pending'
                     OR (sa.status = 'approved' AND (s.service_id IS NULL OR s.status IN ('pending', 'ongoing')) $availOk))
              ORDER BY sa.application_id DESC
              LIMIT 1",
            [$planHolderId, (int) $ignoreApplicationId]
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
    public function eligibility(int $planHolderId, int $packageId, ?int $ignoreApplicationId = null): array
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
        if ($this->openRequest($planHolderId, $ignoreApplicationId) !== null) {
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
     * What a request is worth, whatever it is: a package, a single service (with its route price),
     * and any add-ons. Pure pricing - nothing is saved.
     *
     * @param array{package_id?:int, service_list_id?:int, route_id?:int, attire?:bool} $item
     *
     * @return array{type:string, kind:string, name:string, item_price:float, addons_total:float, addons_label:string, charge:float, free:bool, package_id:int, service_list_id:int}|null
     */
    public function priceItem(int $planHolderId, array $item): ?array
    {
        $db       = db_connect();
        $plan     = $this->planFor($planHolderId);
        $packageId = (int) ($item['package_id'] ?? 0);
        $serviceId = (int) ($item['service_list_id'] ?? 0);
        $attire   = ! empty($item['attire']) ? (float) ClaimSubmissionService::BURIAL_ATTIRE_PRICE : 0.0;
        $charged  = self::PRICED_EXTRAS_ARE_PACKAGE_AMOUNTS;

        if ($packageId > 0) {
            $pkg = $db->table('packages')->select('package_id, package_name, base_price')->where('package_id', $packageId)->get(1)->getRowArray();
            if ($pkg === null) {
                return null;
            }
            $price = round((float) $pkg['base_price'], 2);
            $type  = $plan === null ? self::TYPE_CLAIM : $this->requestType($plan, $packageId);
            $addons = $charged ? $attire : 0.0;

            return $type === self::TYPE_CLAIM
                ? ['type' => self::TYPE_CLAIM, 'kind' => 'addons', 'name' => (string) $pkg['package_name'], 'item_price' => $price, 'addons_total' => $addons, 'addons_label' => $addons > 0 ? 'Burial Attire Package' : '', 'charge' => $addons, 'free' => true, 'package_id' => $packageId, 'service_list_id' => 0]
                : ['type' => self::TYPE_AVAIL, 'kind' => 'package', 'name' => (string) $pkg['package_name'], 'item_price' => $price, 'addons_total' => $addons, 'addons_label' => $addons > 0 ? 'Burial Attire Package' : '', 'charge' => round($price + $addons, 2), 'free' => false, 'package_id' => $packageId, 'service_list_id' => 0];
        }

        if ($serviceId > 0) {
            $svc = $db->table('service_list')->select('service_list_id, service_name, base_price')->where('service_list_id', $serviceId)->get(1)->getRowArray();
            if ($svc === null) {
                return null;
            }
            $price = round((float) $svc['base_price'], 2);
            if ((int) ($item['route_id'] ?? 0) > 0) {
                $route = $db->table('service_routes')->select('price')->where('route_id', (int) $item['route_id'])->where('service_list_id', $serviceId)->get(1)->getRowArray();
                $price = $route ? round((float) $route['price'], 2) : $price;
            }

            return ['type' => 'service', 'kind' => 'service', 'name' => (string) $svc['service_name'], 'item_price' => $price, 'addons_total' => 0.0, 'addons_label' => '', 'charge' => $charged ? $price : 0.0, 'free' => false, 'package_id' => 0, 'service_list_id' => $serviceId];
        }

        return null;
    }

    /** The item an application row asks for. @return array{package_id:int, service_list_id:int, route_id:int, attire:bool} */
    public function itemFromApplication(array $app): array
    {
        return ['package_id' => (int) ($app['package_id'] ?? 0), 'service_list_id' => (int) ($app['service_list_id'] ?? 0), 'route_id' => (int) ($app['selected_route_id'] ?? 0), 'attire' => ! empty($app['burial_attire_selected'])];
    }

    /**
     * What a claim or avail of this package amounts to (kept for callers that only know a package).
     *
     * @return array<string,mixed>
     */
    public function quote(int $planHolderId, int $packageId, ?int $termMonths = null, bool $waive = false): array
    {
        return $this->quoteItem($planHolderId, ['package_id' => $packageId], $termMonths, $waive);
    }

    /**
     * The exact quote for a request: what is free, what is added to the balance, the down payment,
     * and the instalments. One function for the forms, the approval screen and the saved avail.
     *
     * @param int|null $termMonths  the term the Branch Admin chose (null = not chosen yet: no instalments)
     * @param bool     $waive       down payment waived by the Branch Admin
     *
     * @return array<string,mixed>
     */
    public function quoteItem(int $planHolderId, array $item, ?int $termMonths = null, bool $waive = false): array
    {
        $plan   = $this->planFor($planHolderId);
        $priced = $this->priceItem($planHolderId, $item);
        if ($plan === null || $priced === null) {
            return ['ok' => false, 'error' => 'Plan or item not found.'];
        }

        $planId   = (int) $plan['plan_id'];
        $balances = $this->balances($planId);
        $charge   = $priced['charge'];
        $min      = (float) $this->config->availMinFinancedAmount;

        $quote = [
            'ok'                    => true,
            'type'                  => $priced['type'],
            'kind'                  => $priced['kind'],
            'free'                  => $priced['free'],
            'plan_id'               => $planId,
            'package'               => ['package_id' => $priced['package_id'], 'name' => $priced['name'], 'price' => $charge, 'item_price' => $priced['item_price']],
            'addons_total'          => $priced['addons_total'],
            'addons_label'          => $priced['addons_label'],
            'plan_balance'          => $balances['plan_balance'],
            'plan_monthly'          => $balances['plan_monthly'],
            // A claim releases the plan's own package free; anything else adds its price to the balance.
            'package_amount_added'  => $charge,
            'total_balance_after'   => round($balances['total_balance'] + $charge, 2),
            'down_payment_rate'     => 0.0,
            'down_payment_amount'   => 0.0,
            'down_payment_days'     => (int) $this->config->availDownPaymentDays,
            'waived'                => false,
            'upfront'               => false,
            'min_financed'          => $min,
            'financed_amount'       => 0.0,
            'term_months'           => null,
            'terms_offered'         => $this->config->availTerms(),
            'monthly_instalment'    => null,
            'schedule'              => [],
            'monthly_total_due'     => $balances['plan_monthly'],
        ];

        if ($charge <= 0.004) {
            return $quote;
        }

        $rate      = (float) $this->config->availDownPaymentRate;
        $downCents = $waive ? 0 : (int) round($charge * $rate * 100);
        $financed  = round($charge - $downCents / 100, 2);

        // A small amount is paid in full up front: no instalments.
        if (! $waive && $financed < $min) {
            $quote['upfront']             = true;
            $quote['down_payment_rate']   = 1.0;
            $quote['down_payment_amount'] = $charge;
            $quote['financed_amount']     = 0.0;
            $quote['terms_offered']       = [];
            $quote['term_months']         = 0;
            $quote['monthly_instalment']  = 0.0;

            return $quote;
        }

        $quote['down_payment_rate']   = $rate;
        $quote['down_payment_amount'] = round($downCents / 100, 2);
        $quote['waived']              = $waive;
        $quote['financed_amount']     = $financed;

        // A waived small amount is one instalment (nothing is taken up front).
        if ($waive && $financed < $min) {
            $quote['terms_offered'] = [1];
            $termMonths             = 1;
        }

        if ($termMonths !== null) {
            if (! in_array($termMonths, $quote['terms_offered'], true)) {
                return ['ok' => false, 'error' => 'Choose a term of ' . implode(', ', $quote['terms_offered']) . ' months.'] + $quote;
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
    public function assertAvailTerms(float $rate, bool $waived, int $termMonths, bool $upfront = false): void
    {
        if ($upfront) {
            return;   // paid in full up front: no term, and the "rate" is 100%
        }
        if (! $waived && $rate <= 0) {
            throw new \InvalidArgumentException('The down-payment rate must be above 0 unless the down payment is waived.');
        }
        if ($rate < 0 || $rate > 1) {
            throw new \InvalidArgumentException('The down-payment rate must be between 0 and 1.');
        }
        if (! ($waived && $termMonths === 1) && ! in_array($termMonths, $this->config->availTerms(), true)) {
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
            ->select('pa.avail_id, pa.service_id, pa.kind, pa.addons_label, pa.plan_id, pa.branch_id, pa.approved_by, pa.package_price, ph.user_id AS client_user_id, ph.plan_holder_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = pa.plan_holder_id', 'inner')
            ->where('pa.status', 'awaiting_down_payment')
            ->where('pa.down_payment_status', 'due')
            ->where('pa.down_payment_due_by <', date('Y-m-d'));
        if ($planHolderId !== null) {
            $builder->where('pa.plan_holder_id', $planHolderId);
        }

        $count = 0;
        foreach ($builder->get()->getResultArray() as $avail) {
            if ($this->cancelNoDownPayment($avail)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cancels ONE avail for a missing down payment. Safe when two requests get here at the same
     * time: the update only matches while the avail is still 'awaiting_down_payment' with the down
     * payment still due, inside a transaction, and the notifications go out only if that update
     * really changed a row - so the loser of a race changes nothing and tells nobody.
     *
     * @param array<string,mixed> $avail avail_id, service_id, plan_id, branch_id, approved_by, client_user_id
     */
    public function cancelNoDownPayment(array $avail): bool
    {
        $db = db_connect();

        $addonOnly = (string) ($avail['kind'] ?? 'package') === 'addons';

        $db->transStart();
        $db->table('package_avails')
            ->where('avail_id', (int) $avail['avail_id'])
            ->where('status', 'awaiting_down_payment')
            ->where('down_payment_status', 'due')
            ->update([
                'status'           => 'cancelled',
                'cancelled_reason' => $addonOnly ? 'Cancelled - add-on not paid' : 'Cancelled - no down payment',
                'cancelled_at'     => date('Y-m-d H:i:s'),
            ]);
        $changed = $db->affectedRows() > 0;
        // Only an avail or a single service is cancelled as a whole; for a claim only the add-on goes.
        if ($changed && ! $addonOnly && (int) ($avail['service_id'] ?? 0) > 0) {
            $db->table('services')->where('service_id', (int) $avail['service_id'])->where('status', 'pending')->update(['status' => 'cancelled']);
        }
        $db->transComplete();

        if (! $changed || ! $db->transStatus()) {
            return false;
        }

        $notify = new NotificationService();
        $notify->notify(
            (int) $avail['client_user_id'],
            $addonOnly
                ? 'The add-on' . (! empty($avail['addons_label']) ? ' (' . $avail['addons_label'] . ')' : '') . ' on your claim was cancelled because it was not paid in time, and its amount was removed from your balance. Your casket claim is still approved.'
                : 'Your package request was cancelled because the down payment was not paid in time. The package amount was removed from your balance.',
            'general', false, false, (int) $avail['plan_id']
        );

        $admins = $db->table('users')->select('user_id')->where('role_id', 2)->where('status', 'active')->where('deleted_at', null);
        if ((int) ($avail['branch_id'] ?? 0) > 0) {
            $admins->where('branch_id', (int) $avail['branch_id']);
        }
        foreach ($admins->get()->getResultArray() as $admin) {
            $notify->notify((int) $admin['user_id'], $addonOnly ? 'The add-on on a claim (#' . (int) $avail['avail_id'] . ') was cancelled - it was not paid in time. The claim itself still stands.' : 'An avail (request #' . (int) $avail['avail_id'] . ') was cancelled - no down payment was received in time.', 'general');
        }

        $actor = (int) (($avail['approved_by'] ?? 0) ?: $avail['client_user_id']);
        (new ActivityLogService())->log($actor, 'cancelled', 'package_avail', (int) $avail['avail_id'], ($addonOnly ? 'Add-on cancelled - not paid within ' : 'Avail cancelled - no down payment within ') . (int) $this->config->availDownPaymentDays . ' days');

        return true;
    }
    /** Forget which scopes were already checked (tests, and the spark command). */
    public static function resetExpiryMemory(): void
    {
        self::$expiredScopes = [];
    }

    /* ======================================================================
     * Applying payments (payment_allocations)
     *
     * A payment from a client who has an avail is split into slices: plan contributions and
     * package instalments. Slices are written when the payment is VERIFIED and rebuilt from
     * scratch (reallocatePlan) whenever a payment is rejected/cancelled/reversed, so the rows can
     * never drift from the payments. Payments with no rows count 100% as plan money (nothing is
     * backfilled), and clients without an avail never get rows.
     * ==================================================================== */

    /**
     * Pure: pour one payment into what is due, oldest first.
     *
     * 1. Everything due on or before $asOf - plan contributions AND package instalments together,
     *    oldest due date first (a plan contribution goes before an instalment due the same day).
     * 2. What is left is ADVANCE on the plan (its future contributions).
     * 3. Only when every plan contribution is paid does the rest go to future instalments.
     * 4. Anything beyond that stays unallocated (plan overpayment, as today).
     *
     * @param list<array{number:int, due:string, remaining:float}>                                          $planDue
     * @param list<array{instalment_id:int, avail_id:int, number:int, due:string, remaining:float}>        $instalmentDue
     *
     * @return array{slices:list<array<string,mixed>>, plan_amount:float, package_amount:float, excess:float}
     */
    public static function allocate(array $planDue, array $instalmentDue, float $amount, string $asOf): array
    {
        $pool   = (int) round($amount * 100);
        $slices = [];
        $planLeft = [];   // number => cents still unpaid

        $items = [];
        foreach ($planDue as $p) {
            $cents = (int) round($p['remaining'] * 100);
            if ($cents <= 0) {
                continue;
            }
            $planLeft[(int) $p['number']] = $cents;
            $items[] = ['kind' => 'plan', 'order' => 0, 'due' => (string) $p['due'], 'number' => (int) $p['number'], 'room' => $cents, 'row' => $p];
        }
        foreach ($instalmentDue as $i) {
            $cents = (int) round($i['remaining'] * 100);
            if ($cents > 0) {
                $items[] = ['kind' => 'instalment', 'order' => 1, 'due' => (string) $i['due'], 'number' => (int) $i['number'], 'room' => $cents, 'row' => $i];
            }
        }

        $take = static function (array $item, bool $advance) use (&$pool, &$slices, &$planLeft): void {
            $put = min($item['room'], $pool);
            if ($put <= 0) {
                return;
            }
            $pool -= $put;
            $slice = ['kind' => $item['kind'], 'number' => $item['number'], 'due' => $item['due'], 'amount' => round($put / 100, 2), 'advance' => $advance];
            if ($item['kind'] === 'instalment') {
                $slice['instalment_id'] = (int) $item['row']['instalment_id'];
                $slice['avail_id']      = (int) $item['row']['avail_id'];
            } else {
                $planLeft[$item['number']] -= $put;
            }
            $slices[] = $slice;
        };

        // 1. what is due now
        $dueNow = array_values(array_filter($items, static fn (array $it): bool => strcmp($it['due'], $asOf) <= 0));
        usort($dueNow, static fn (array $a, array $b): int => [$a['due'], $a['order'], $a['number']] <=> [$b['due'], $b['order'], $b['number']]);
        foreach ($dueNow as $it) {
            $take($it, false);
        }

        // 2. advance on the plan
        $futurePlan = array_values(array_filter($items, static fn (array $it): bool => $it['kind'] === 'plan' && strcmp($it['due'], $asOf) > 0));
        usort($futurePlan, static fn (array $a, array $b): int => $a['number'] <=> $b['number']);
        foreach ($futurePlan as $it) {
            $take($it, true);
        }

        // 3. future instalments, only once every plan month is paid
        if ($pool > 0 && array_sum($planLeft) <= 0) {
            $futureInst = array_values(array_filter($items, static fn (array $it): bool => $it['kind'] === 'instalment' && strcmp($it['due'], $asOf) > 0));
            usort($futureInst, static fn (array $a, array $b): int => [$a['due'], $a['number']] <=> [$b['due'], $b['number']]);
            foreach ($futureInst as $it) {
                $take($it, true);
            }
        }

        $plan = 0.0;
        $pkg  = 0.0;
        foreach ($slices as $sl) {
            $sl['kind'] === 'plan' ? $plan += $sl['amount'] : $pkg += $sl['amount'];
        }

        return ['slices' => $slices, 'plan_amount' => round($plan, 2), 'package_amount' => round($pkg, 2), 'excess' => round($pool / 100, 2)];
    }

    /** True when the plan has a package amount still to pay (a down payment due, or unpaid instalments). */
    public function hasPackageItems(int $planId): bool
    {
        if (! $this->tableReady('package_avails')) {
            return false;
        }

        return db_connect()->table('package_avails')->where('plan_id', $planId)->whereIn('status', ['awaiting_down_payment', 'active'])->countAllResults() > 0;
    }

    /**
     * What a payment of $amount would be applied to, for the preview on the recording screens:
     * "₱3,156.67 -> Plan Oct 2026 ₱240.00 + Instalment 1 ₱2,916.67".
     *
     * @return array{ok:bool, error?:string, slices:list<array<string,mixed>>, plan_amount:float, package_amount:float, excess:float, text:string}
     */
    public function previewAllocation(int $planId, float $amount, string $paymentDate, string $paymentType = 'monthly_contribution', ?int $availId = null): array
    {
        $empty = ['ok' => false, 'slices' => [], 'plan_amount' => 0.0, 'package_amount' => 0.0, 'excess' => 0.0, 'text' => ''];
        if ($amount <= 0) {
            return ['error' => 'Enter an amount above 0.'] + $empty;
        }
        $db = db_connect();

        if ($paymentType === 'avail_down_payment') {
            $avail = $availId ? $db->table('package_avails')->where('avail_id', $availId)->where('plan_id', $planId)->get(1)->getRowArray() : null;
            if (! $avail || $avail['status'] !== 'awaiting_down_payment') {
                return ['error' => 'There is no down payment waiting on this plan.'] + $empty;
            }
            $left = max(0.0, round((float) $avail['down_payment_amount'] - $this->downPaymentPaid((int) $avail['avail_id']), 2));
            if ($amount - $left > 0.004) {
                return ['error' => 'The down payment still due is ' . $this->money($left) . '.', 'excess' => round($amount - $left, 2)] + $empty;
            }

            return ['ok' => true, 'slices' => [['kind' => 'down_payment', 'amount' => round($amount, 2)]], 'plan_amount' => 0.0, 'package_amount' => round($amount, 2), 'excess' => 0.0, 'text' => $this->money($amount) . ' -> Package down payment ' . $this->money($amount)] + $empty;
        }

        $res = self::allocate($this->planDueNow($planId), $this->instalmentsDue($planId), $amount, $paymentDate);

        return ['ok' => true, 'text' => $this->describeSlices($amount, $res)] + $res + $empty;
    }

    /** Human text for a split: "₱3,156.67 -> Plan Oct 2026 ₱240.00 + Instalment 1 ₱2,916.67". */
    public function describeSlices(float $amount, array $res): string
    {
        $parts = [];
        $slices = array_values($res['slices']);
        for ($i = 0, $n = count($slices); $i < $n; $i++) {
            $sl = $slices[$i];
            if ($sl['kind'] === 'plan') {
                // a long run of plan months of the same sort reads as one range: "Plan Nov 2026 - Nov 2027 (13 months)"
                $j = $i; $sum = (float) $sl['amount'];
                while ($j + 1 < $n && $slices[$j + 1]['kind'] === 'plan' && ! empty($slices[$j + 1]['advance']) === ! empty($sl['advance'])) {
                    $j++;
                    $sum += (float) $slices[$j]['amount'];
                }
                if ($j - $i >= 2) {
                    $parts[] = 'Plan ' . date('M Y', strtotime((string) $sl['due'])) . ' - ' . date('M Y', strtotime((string) $slices[$j]['due'])) . ' (' . ($j - $i + 1) . ' months) ' . $this->money($sum) . (! empty($sl['advance']) ? ' (advance)' : '');
                    $i = $j;
                    continue;
                }
            }
            $parts[] = ($sl['kind'] === 'plan' ? 'Plan ' . date('M Y', strtotime((string) $sl['due'])) : 'Instalment ' . (int) $sl['number']) . ' ' . $this->money((float) $sl['amount']) . (! empty($sl['advance']) ? ' (advance)' : '');
        }
        if ($res['excess'] > 0.004) {
            $parts[] = 'Extra ' . $this->money((float) $res['excess']) . ' kept on the plan';
        }

        return $this->money($amount) . ' -> ' . ($parts === [] ? 'nothing is due' : implode(' + ', $parts));
    }

    /** Down payment money verified so far for an avail. */
    public function downPaymentPaid(int $availId): float
    {
        if (! $this->tableReady('payment_allocations')) {
            return 0.0;
        }

        return (float) (db_connect()->table('payment_allocations')->selectSum('amount', 't')->where('avail_id', $availId)->where('target_type', 'avail_down_payment')->get()->getRowArray()['t'] ?? 0);
    }

    /**
     * Can this service be started (Ongoing)? An avail's service needs its down payment fully paid
     * and VERIFIED (or the waiver approved). A service that is not an avail has no such condition.
     */
    public function downPaymentCleared(int $serviceId): bool
    {
        if ($serviceId <= 0 || ! $this->tableReady('package_avails')) {
            return true;
        }
        $avail = db_connect()->table('package_avails')->select('down_payment_status, status, kind')->where('service_id', $serviceId)->get(1)->getRowArray();
        if ($avail === null) {
            return true;
        }

        // A claim whose add-on was cancelled (not paid in time) may start: the casket claim stands.
        if ($avail['status'] === 'cancelled') {
            return (($avail['kind'] ?? 'package') === 'addons');
        }

        return in_array($avail['down_payment_status'], ['paid', 'waived'], true);
    }

    /** Plan contributions already due (or part paid) that still need money, from the ledger. */
    private function planDueNow(int $planId): array
    {
        $rows = [];
        foreach ($this->ledger->contributionsWithMoney($planId) as $c) {
            if ($c['remaining'] > 0.004) {
                $rows[] = ['number' => (int) $c['number'], 'due' => (string) $c['due'], 'remaining' => (float) $c['remaining']];
            }
        }

        return $rows;
    }

    /** Package instalments that still need money, oldest first. @return list<array<string,mixed>> */
    private function instalmentsDue(int $planId): array
    {
        if (! $this->tableReady('package_avail_instalments')) {
            return [];
        }

        $rows = db_connect()->query(
            "SELECT i.instalment_id, i.avail_id, i.number, i.due_date AS due, ROUND(i.amount - i.paid_amount, 2) AS remaining
               FROM package_avail_instalments i
               JOIN package_avails pa ON pa.avail_id = i.avail_id
              WHERE pa.plan_id = ? AND pa.status = 'active' AND i.paid_amount < i.amount
              ORDER BY i.due_date, i.number",
            [$planId]
        )->getResultArray();

        return array_map(static fn (array $r): array => ['instalment_id' => (int) $r['instalment_id'], 'avail_id' => (int) $r['avail_id'], 'number' => (int) $r['number'], 'due' => (string) $r['due'], 'remaining' => (float) $r['remaining']], $rows);
    }

    /** Call when a payment has just been verified (CycleService::afterPaymentVerified does). */
    public function onPaymentVerified(int $paymentId, int $planId): void
    {
        if (! $this->tableReady('payment_allocations') || ! $this->tableReady('package_avails') || $planId <= 0) {
            return;
        }

        $payment = db_connect()->table('payments')->select('payment_type, status')->where('payment_id', $paymentId)->get(1)->getRowArray();
        if ($payment === null || ! in_array($payment['status'], ['paid', 'verified'], true) || $payment['payment_type'] === 'reinstatement_fee') {
            return;
        }

        // Clients without an avail keep 100% plan payments and never get allocation rows.
        $hasAvail = db_connect()->table('package_avails')->where('plan_id', $planId)->where('status !=', 'cancelled')->countAllResults() > 0;
        if (! $hasAvail) {
            return;
        }

        $this->reallocatePlan($planId, [$paymentId]);
    }

    /** Call when a payment is rejected, cancelled or reversed: its slices go and everything is recomputed. */
    public function onPaymentReversed(int $paymentId, int $planId): void
    {
        if (! $this->tableReady('payment_allocations') || $planId <= 0) {
            return;
        }

        $had = db_connect()->table('payment_allocations')->where('payment_id', $paymentId)->countAllResults() > 0;
        if (! $had) {
            return;
        }

        db_connect()->table('payment_allocations')->where('payment_id', $paymentId)->delete();
        $this->reallocatePlan($planId);
        $this->ledger->sync($planId, true);
    }

    /**
     * Rebuilds every allocation of a plan from its verified payments, in date order: package down
     * payments go only to their down payment, every other payment goes oldest-due-first (allocate()).
     * The payments replayed are those that already have rows, plus $includePaymentIds; payments
     * from before the first avail has no rows and stay plain plan money.
     *
     * @param list<int> $includePaymentIds payments that were just verified
     *
     * @return array{payments:int, avails:int}
     */
    public function reallocatePlan(int $planId, array $includePaymentIds = []): array
    {
        $db     = db_connect();
        $plan   = $db->table('plans')->where('plan_id', $planId)->get(1)->getRowArray();
        $avails = $plan ? $db->table('package_avails')->where('plan_id', $planId)->where('status !=', 'cancelled')->orderBy('avail_id')->get()->getResultArray() : [];
        if (! $plan || $avails === []) {
            return ['payments' => 0, 'avails' => 0];
        }

        $ids = array_map('intval', array_column($db->table('payment_allocations')->select('payment_id')->distinct()->where('plan_id', $planId)->get()->getResultArray(), 'payment_id'));
        $ids = array_values(array_unique(array_merge($ids, array_map('intval', $includePaymentIds))));
        $payments = $ids === [] ? [] : $db->table('payments')
            ->select('payment_id, payment_date, amount, payment_type, avail_id, verified_at')
            ->where('plan_id', $planId)
            ->whereIn('payment_id', $ids)
            ->whereIn('status', ['paid', 'verified'])
            ->where('payment_type !=', 'reinstatement_fee')
            ->get()->getResultArray();
        usort($payments, static fn (array $a, array $b): int => [(string) $a['payment_date'], (int) $a['payment_id']] <=> [(string) $b['payment_date'], (int) $b['payment_id']]);

        $dueDay = $this->planDueDay($plan);
        $wasDue = [];
        foreach ($avails as $a) {
            $wasDue[(int) $a['avail_id']] = $a['down_payment_status'] === 'due';
        }
        $db->transStart();

        // Reset: forget every allocation and schedule of this plan's avails.
        $db->table('payment_allocations')->where('plan_id', $planId)->delete();
        $state = [];
        foreach ($avails as $a) {
            $db->table('package_avail_instalments')->where('avail_id', (int) $a['avail_id'])->delete();
            $waived = $a['down_payment_status'] === 'waived';
            $db->table('package_avails')->where('avail_id', (int) $a['avail_id'])->update([
                'down_payment_status'     => $waived ? 'waived' : 'due',
                'down_payment_payment_id' => null,
                'down_payment_paid_at'    => null,
                'first_due_date'          => null,
                'status'                  => $waived ? 'active' : 'awaiting_down_payment',
            ]);
            $state[(int) $a['avail_id']] = ['avail' => $a, 'down_paid' => 0, 'inst' => []];
            if ($waived) {
                $state[(int) $a['avail_id']]['inst'] = $this->writeSchedule($a, (string) substr((string) ($a['waived_at'] ?: $a['approved_at'] ?: date('Y-m-d')), 0, 10), $dueDay);
            }
        }

        $planMoney = [];   // plan money of the payments replayed so far (plan slices + advance + excess)
        foreach ($payments as $p) {
            $amount = (float) $p['amount'];
            $pid    = (int) $p['payment_id'];

            if ($p['payment_type'] === 'avail_down_payment') {
                $aid = (int) $p['avail_id'];
                if (! isset($state[$aid])) {
                    continue;
                }
                $a    = $state[$aid]['avail'];
                $need = (int) round((float) $a['down_payment_amount'] * 100) - $state[$aid]['down_paid'];
                $put  = min((int) round($amount * 100), max(0, $need));
                if ($put <= 0) {
                    continue;
                }
                $this->insertAllocation($pid, 'avail_down_payment', $planId, $aid, null, null, $put / 100);
                $state[$aid]['down_paid'] += $put;
                if ($state[$aid]['down_paid'] >= (int) round((float) $a['down_payment_amount'] * 100)) {
                    $when = substr((string) ($p['verified_at'] ?: $p['payment_date']), 0, 10);
                    $db->table('package_avails')->where('avail_id', $aid)->update([
                        'down_payment_status' => 'paid', 'down_payment_payment_id' => $pid, 'down_payment_paid_at' => $when . ' 00:00:00',
                    ]);
                    $state[$aid]['inst'] = $this->writeSchedule($a, $when, $dueDay);
                }
                continue;
            }

            // Every other payment: oldest due first across plan contributions and instalments.
            $planDue = [];
            foreach ($this->ledger->contributionsWithMoney($planId, $ids, $planMoney) as $c) {
                if ($c['remaining'] > 0.004) {
                    $planDue[] = ['number' => (int) $c['number'], 'due' => (string) $c['due'], 'remaining' => (float) $c['remaining']];
                }
            }
            $instDue = [];
            foreach ($state as $aid => $st) {
                foreach ($st['inst'] as $iid => $i) {
                    $left = round($i['amount'] - $i['paid'], 2);
                    if ($left > 0.004) {
                        $instDue[] = ['instalment_id' => $iid, 'avail_id' => $aid, 'number' => $i['number'], 'due' => $i['due'], 'remaining' => $left];
                    }
                }
            }

            $res = self::allocate($planDue, $instDue, $amount, (string) $p['payment_date']);
            foreach ($res['slices'] as $sl) {
                if ($sl['kind'] === 'plan') {
                    $this->insertAllocation($pid, 'plan_contribution', $planId, null, null, (int) $sl['number'], (float) $sl['amount']);
                    continue;
                }
                $aid = (int) $sl['avail_id'];
                $iid = (int) $sl['instalment_id'];
                $this->insertAllocation($pid, 'avail_instalment', $planId, $aid, $iid, null, (float) $sl['amount']);
                $state[$aid]['inst'][$iid]['paid'] = round($state[$aid]['inst'][$iid]['paid'] + (float) $sl['amount'], 2);
                $full = $state[$aid]['inst'][$iid]['paid'] >= $state[$aid]['inst'][$iid]['amount'] - 0.004;
                $db->table('package_avail_instalments')->where('instalment_id', $iid)->update([
                    'paid_amount' => $state[$aid]['inst'][$iid]['paid'],
                    'paid_at'     => $full ? substr((string) $p['payment_date'], 0, 10) . ' 00:00:00' : null,
                ]);
            }
            $planMoney[] = ['date' => (string) $p['payment_date'], 'amount' => round($res['plan_amount'] + $res['excess'], 2)];
        }

        // An avail whose instalments are all paid is settled.
        foreach ($state as $aid => $st) {
            $downPaid = $db->table('package_avails')->where('avail_id', $aid)->whereIn('down_payment_status', ['paid', 'waived'])->countAllResults() > 0;
            $noInstalmentsNeeded = $st['inst'] === [] && $downPaid && (float) $st['avail']['financed_amount'] <= 0.004;
            if ($noInstalmentsNeeded || ($st['inst'] !== [] && array_sum(array_map(static fn (array $i): int => $i['paid'] >= $i['amount'] - 0.004 ? 0 : 1, $st['inst'])) === 0)) {
                $db->table('package_avails')->where('avail_id', $aid)->update(['status' => 'settled']);
            }
        }
        $db->transComplete();

        // Told once: only when this run is what turned the down payment from due to paid.
        foreach ($wasDue as $aid => $was) {
            if ($was && $db->table('package_avails')->where('avail_id', $aid)->where('down_payment_status', 'paid')->countAllResults() > 0) {
                $this->notifyDownPaymentReceived($aid);
            }
        }

        return ['payments' => count($payments), 'avails' => count($avails)];
    }

    /**
     * Writes an avail's instalment schedule (once its down payment is verified or the waiver is
     * approved) and makes the avail active. The first instalment is due one month after $basisDate,
     * on the plan's due day.
     *
     * @return array<int, array{number:int, due:string, amount:float, paid:float}> by instalment_id
     */
    public function writeSchedule(array $avail, string $basisDate, int $planDueDay): array
    {
        $db = db_connect();
        $db->table('package_avail_instalments')->where('avail_id', (int) $avail['avail_id'])->delete();

        $financed = (float) $avail['financed_amount'];
        $first    = self::firstDueDate($basisDate, $planDueDay);
        $out      = [];
        if ($financed > 0.004) {
            foreach (self::instalmentSchedule($financed, (int) $avail['term_months'], $first) as $row) {
                $db->table('package_avail_instalments')->insert(['avail_id' => (int) $avail['avail_id'], 'number' => $row['number'], 'due_date' => $row['due'], 'amount' => $row['amount'], 'paid_amount' => 0]);
                $out[(int) $db->insertID()] = ['number' => $row['number'], 'due' => $row['due'], 'amount' => $row['amount'], 'paid' => 0.0];
            }
        }
        $db->table('package_avails')->where('avail_id', (int) $avail['avail_id'])->update(['first_due_date' => $out === [] ? null : $first, 'status' => 'active']);

        return $out;
    }

    private function insertAllocation(int $paymentId, string $type, int $planId, ?int $availId, ?int $instalmentId, ?int $contributionNo, float $amount): void
    {
        db_connect()->table('payment_allocations')->insert([
            'payment_id' => $paymentId, 'target_type' => $type, 'plan_id' => $planId, 'avail_id' => $availId,
            'instalment_id' => $instalmentId, 'contribution_number' => $contributionNo, 'amount' => round($amount, 2),
        ]);
    }
    /* ======================================================================
     * Recording screens (staff, branch admin, collector, client GCash)
     * ==================================================================== */

    /**
     * How a payment for this plan is entered. A client with a package amount to pay is asked for an
     * AMOUNT (with a preview of where it goes); a client without one keeps the months-based entry.
     *
     * @return array{amount_mode:bool, down_avail:?array<string,mixed>, down_left:float}
     */
    public function recordingEntry(int $planId): array
    {
        $none = ['amount_mode' => false, 'down_avail' => null, 'down_left' => 0.0];
        if (! $this->hasPackageItems($planId)) {
            return $none;
        }

        $avail = db_connect()->table('package_avails')->where('plan_id', $planId)->where('status', 'awaiting_down_payment')->orderBy('avail_id')->get(1)->getRowArray();
        $left  = $avail ? max(0.0, round((float) $avail['down_payment_amount'] - $this->downPaymentPaid((int) $avail['avail_id']), 2)) : 0.0;

        return ['amount_mode' => true, 'down_avail' => $avail ?: null, 'down_left' => $left];
    }

    /**
     * Everything a recording screen needs to save a payment entered as an amount: where it goes,
     * the payment type, the months it covers and the coverage period. The server always computes
     * this itself; the preview on the screen is only a convenience.
     *
     * @param string $kind 'regular' (plan contributions + instalments, oldest first) or 'down_payment'
     *
     * @return array{ok:bool, error?:string, payment_type?:string, avail_id?:?int, amount?:float, months_covered?:int, coverage_start?:?string, coverage_end?:?string, text?:string, excess?:float}
     */
    public function prepareAmountPayment(int $planId, float $amount, string $paymentDate, string $kind = 'regular'): array
    {
        $entry = $this->recordingEntry($planId);
        if (! $entry['amount_mode']) {
            return ['ok' => false, 'error' => 'This plan has no package amount to pay.'];
        }

        if ($kind === 'down_payment') {
            if ($entry['down_avail'] === null) {
                return ['ok' => false, 'error' => 'There is no package down payment waiting on this plan.'];
            }
            $prev = $this->previewAllocation($planId, $amount, $paymentDate, 'avail_down_payment', (int) $entry['down_avail']['avail_id']);
            if (! $prev['ok']) {
                return ['ok' => false, 'error' => (string) ($prev['error'] ?? 'Check the amount.')];
            }

            return ['ok' => true, 'payment_type' => 'avail_down_payment', 'avail_id' => (int) $entry['down_avail']['avail_id'], 'amount' => round($amount, 2), 'months_covered' => 0, 'coverage_start' => null, 'coverage_end' => null, 'text' => $prev['text'], 'excess' => 0.0];
        }

        $prev = $this->previewAllocation($planId, $amount, $paymentDate);
        if (! $prev['ok']) {
            return ['ok' => false, 'error' => (string) ($prev['error'] ?? 'Check the amount.')];
        }
        if ($prev['excess'] > 0.004) {
            return ['ok' => false, 'error' => 'That is ' . $this->money($prev['excess']) . ' more than this plan and its package can take. Lower the amount.'];
        }

        $due = array_values(array_map(static fn (array $sl): string => (string) $sl['due'], array_filter($prev['slices'], static fn (array $sl): bool => $sl['kind'] === 'plan')));
        sort($due);

        return [
            'ok' => true, 'payment_type' => 'monthly_contribution', 'avail_id' => null, 'amount' => round($amount, 2),
            'months_covered' => count($due), 'coverage_start' => $due[0] ?? null, 'coverage_end' => $due === [] ? null : PlanLedgerService::addDays(PlanLedgerService::addMonths((string) end($due), 1), -1),
            'text' => $prev['text'], 'excess' => 0.0,
        ];
    }
    /* ======================================================================
     * Deciding a request: approve (claim or avail), reject, start the service
     *
     * Claims (the plan's own package): Branch Admin or Staff. Avails: Branch Admin only - they set
     * the money terms (down payment, term, waiver). Staff can view avails and record payments.
     * ==================================================================== */

    /** One request with its plan holder and package, scoped to a branch. @return array<string,mixed>|null */
    private function loadApplication(int $applicationId, int $branchId): ?array
    {
        if ($applicationId <= 0 || $branchId <= 0) {
            return null;
        }

        $row = db_connect()->table('service_applications sa')
            ->select('sa.*, ph.branch_id, ph.user_id AS client_user_id, p.package_name, p.base_price AS package_price, sl.service_name, sl.base_price AS service_price')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('sa.application_id', $applicationId)
            ->get(1)
            ->getRowArray();

        return $row && (int) $row['branch_id'] === $branchId ? $row : null;
    }

    /** 'claim', 'avail', or 'service' (a single service, not a package). */
    public function typeOfApplication(array $app, ?array $plan): string
    {
        if ((int) ($app['package_id'] ?? 0) <= 0) {
            return 'service';
        }

        return $plan === null ? self::TYPE_CLAIM : $this->requestType($plan, (int) $app['package_id']);
    }

    /**
     * Approves a pending request.
     *
     * @param array{term?:int|string, waive?:bool, reason?:string} $options only used when the request adds an amount to the balance
     *
     * @return array{ok:bool, message:string, type?:string}
     */
    public function approve(int $applicationId, int $branchId, int $actorUserId, int $actorRole, array $options = []): array
    {
        $this->expireDownPayments(null);

        $app = $this->loadApplication($applicationId, $branchId);
        if ($app === null) {
            return ['ok' => false, 'message' => 'Claim not found.'];
        }
        if ($app['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Only pending claims can be approved.'];
        }
        if (! in_array($actorRole, [self::ROLE_BRANCH_ADMIN, self::ROLE_STAFF], true)) {
            return ['ok' => false, 'message' => 'You are not allowed to approve this.'];
        }

        $planHolderId = (int) $app['plan_holder_id'];
        $plan         = $this->planFor($planHolderId);
        if ($plan === null) {
            return ['ok' => false, 'message' => 'This plan holder has no active plan.'];
        }

        $priced = $this->priceItem($planHolderId, $this->itemFromApplication($app));
        if ($priced === null) {
            return ['ok' => false, 'message' => 'The requested package or service was not found.'];
        }

        // Anything that adds an amount to the balance sets money terms: Branch Admin only - except a CLAIM
        // whose add-on is paid in full up front (no term, no waiver), which Staff may approve like a plain claim.
        if ($actorRole !== self::ROLE_BRANCH_ADMIN) {
            if (! $this->staffMayDecide($priced, $planHolderId, $this->itemFromApplication($app))) {
                return ['ok' => false, 'message' => 'Only a Branch Admin can approve this: it adds an amount to the client\'s balance that has instalments to set (or may need a waiver).'];
            }
            if (! empty($options['waive'])) {
                return ['ok' => false, 'message' => 'Only a Branch Admin can waive a down payment.'];
            }
        }

        $el = $this->eligibility($planHolderId, (int) $app['package_id'], $applicationId);
        if (! $el['allowed']) {
            return ['ok' => false, 'message' => (string) $el['message']];
        }

        return $this->approveRequest($app, $plan, $actorUserId, $options, $priced);
    }

    /**
     * May Staff decide this request? Yes when nothing is added to the balance (a plain claim), or when it is a
     * CLAIM whose add-on is small enough to be paid in full up front (no term to choose, no waiver).
     * Everything with instalments to set, and every avail or single service, needs the Branch Admin.
     */
    public function staffMayDecide(array $priced, int $planHolderId, array $item): bool
    {
        if ($priced['charge'] <= 0.004) {
            return true;
        }
        if ($priced['type'] !== self::TYPE_CLAIM) {
            return false;
        }
        $probe = $this->quoteItem($planHolderId, $item, null, false);

        return ! empty($probe['upfront']);
    }

    /**
     * Saves the approval: the service row, the application, and - when something is added to the
     * balance (an avail, a service, or add-ons on a claim) - the avail record with its terms. The
     * rate, term and amounts used are stored on it.
     */
    private function approveRequest(array $app, array $plan, int $actorUserId, array $options, array $priced): array
    {
        $isClaim = $priced['type'] === self::TYPE_CLAIM;
        $charge  = $priced['charge'];
        $db      = db_connect();
        $now     = date('Y-m-d H:i:s');
        $notify  = new NotificationService();
        $money   = fn (float $v): string => $this->money($v);
        $what    = $priced['name'];

        $quote = null;
        $waive = false;
        $reason = '';
        $term = 0;
        if ($charge > 0.004) {
            $waive  = ! empty($options['waive']);
            $reason = trim((string) ($options['reason'] ?? ''));
            $term   = (int) ($options['term'] ?? 0);

            if ($waive && mb_strlen($reason) < self::WAIVER_REASON_MIN) {
                return ['ok' => false, 'message' => 'A waiver needs a reason of at least ' . self::WAIVER_REASON_MIN . ' characters.'];
            }
            $probe = $this->quoteItem((int) $app['plan_holder_id'], $this->itemFromApplication($app), null, $waive);
            if (empty($probe['ok'])) {
                return ['ok' => false, 'message' => (string) ($probe['error'] ?? 'Could not price this request.')];
            }
            if ($probe['upfront']) {
                $term = 0;                                // paid in full up front: nothing to choose
            } elseif ($waive && $probe['terms_offered'] === [1]) {
                $term = 1;                                // waived small amount: one instalment
            } elseif (! in_array($term, $probe['terms_offered'], true)) {
                return ['ok' => false, 'message' => 'Choose a term of ' . implode(', ', $probe['terms_offered']) . ' months.'];
            }

            $quote = $this->quoteItem((int) $app['plan_holder_id'], $this->itemFromApplication($app), $term ?: null, $waive);
            if (empty($quote['ok'])) {
                return ['ok' => false, 'message' => (string) ($quote['error'] ?? 'Could not price this request.')];
            }
            try {
                $this->assertAvailTerms((float) $quote['down_payment_rate'], $waive, $term, (bool) $quote['upfront']);
            } catch (\InvalidArgumentException $e) {
                return ['ok' => false, 'message' => $e->getMessage()];
            }
            if (! $waive && $quote['down_payment_amount'] <= 0) {
                return ['ok' => false, 'message' => 'The down payment would be 0. Check the down-payment rate, or waive it with a reason.'];
            }
        }

        $dueBy = date('Y-m-d', strtotime('+' . (int) $this->config->availDownPaymentDays . ' days'));
        $availId = 0;

        $db->transStart();
        $serviceId = $this->createService($app, $priced['item_price'] + $priced['addons_total'], $isClaim ? 'Created from approved claim.' : 'Created from approved request.');
        $db->table('service_applications')->where('application_id', (int) $app['application_id'])->update([
            'status' => 'approved', 'request_type' => $isClaim ? self::TYPE_CLAIM : self::TYPE_AVAIL, 'rule_version' => self::RULE_VERSION,
            'cycle_number' => (int) ($plan['current_cycle_number'] ?? 1), 'service_id' => $serviceId,
            'decided_by' => $actorUserId, 'decided_at' => $now, 'rejection_reason' => null,
        ]);
        if ($quote !== null) {
            $db->table('package_avails')->insert([
                'application_id' => (int) $app['application_id'], 'plan_holder_id' => (int) $app['plan_holder_id'], 'plan_id' => (int) $plan['plan_id'],
                'package_id' => $priced['package_id'] > 0 ? $priced['package_id'] : null, 'service_list_id' => $priced['service_list_id'] > 0 ? $priced['service_list_id'] : null,
                'kind' => $priced['kind'], 'addons_amount' => $priced['addons_total'], 'addons_label' => $priced['addons_label'] !== '' ? $priced['addons_label'] : null,
                'branch_id' => (int) $app['branch_id'], 'service_id' => $serviceId, 'cycle_number' => (int) ($plan['current_cycle_number'] ?? 1),
                'package_price' => $charge, 'down_payment_rate' => $quote['down_payment_rate'], 'down_payment_amount' => $quote['down_payment_amount'],
                'down_payment_status' => $waive ? 'waived' : 'due', 'down_payment_due_by' => $waive ? null : $dueBy,
                'waived_by' => $waive ? $actorUserId : null, 'waived_at' => $waive ? $now : null, 'waiver_reason' => $waive ? $reason : null,
                'financed_amount' => $quote['financed_amount'], 'term_months' => $term, 'monthly_instalment' => (float) ($quote['monthly_instalment'] ?? 0),
                'status' => $waive ? 'active' : 'awaiting_down_payment', 'approved_by' => $actorUserId, 'approved_at' => $now,
            ]);
            $availId = (int) $db->insertID();
            if ($waive) {
                $this->writeSchedule($db->table('package_avails')->where('avail_id', $availId)->get(1)->getRowArray(), date('Y-m-d'), $this->planDueDay($plan));
            }
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            return ['ok' => false, 'message' => 'Failed to approve the request.'];
        }

        if ($isClaim) {
            $pkg = $db->table('packages')->select('is_damayan_entitlement')->where('package_id', $priced['package_id'])->get(1)->getRowArray();
            if ($pkg && (int) $pkg['is_damayan_entitlement'] === 1) {
                $db->table('plans')->where('plan_id', (int) $plan['plan_id'])->update(['last_damayan_claim_at' => $now]);
            }
            (new CycleService())->closeCycleIfSettled((int) $plan['plan_id']);
        }

        // ---- tell the client
        $extra = $priced['addons_label'] !== '' ? ' (including ' . $priced['addons_label'] . ' ' . $money($priced['addons_total']) . ')' : '';
        if ($quote === null) {
            $msg = 'Your claim for ' . $what . ' has been approved. Your plan balance stays and you keep paying your monthly contribution.';
        } elseif ($waive) {
            $msg = 'Your request for ' . $what . $extra . ' was approved with no down payment. You pay ' . $money((float) $quote['monthly_instalment']) . ' a month for ' . $term . ' month' . ($term === 1 ? '' : 's') . ', on top of your plan contribution.';
        } elseif ($quote['upfront']) {
            $msg = 'Your ' . ($isClaim ? 'claim' : 'request') . ' for ' . $what . ' was approved. The ' . $money($charge) . ($isClaim ? ' for your add-on' : '') . ' is paid in full by ' . date('F j, Y', strtotime($dueBy)) . ' or the request is cancelled. Your service can start once it is verified.';
        } else {
            $msg = 'Your request for ' . $what . $extra . ' was approved. Pay the down payment of ' . $money((float) $quote['down_payment_amount']) . ' by ' . date('F j, Y', strtotime($dueBy))
                . ' or the request is cancelled. Your service can start once it is verified. After that you pay ' . $money((float) $quote['monthly_instalment']) . ' a month for ' . $term . ' months.';
        }
        $notify->notify((int) $app['client_user_id'], $msg, 'service_approved', false, false, (int) $plan['plan_id']);

        $logId = $availId > 0 ? $availId : (int) $app['application_id'];
        (new ActivityLogService())->log(
            $actorUserId, 'approved', $availId > 0 ? 'package_avail' : 'service_application', $logId,
            'Approved ' . ($availId > 0 ? 'avail #' . $availId : 'claim #' . (int) $app['application_id']) . ' of ' . $what
                . ($quote !== null ? ' (' . ($quote['upfront'] ? 'paid in full up front' : $term . ' months') . ', down payment ' . ($waive ? 'waived: ' . $reason : $money((float) $quote['down_payment_amount'])) . ')' : '')
        );

        return ['ok' => true, 'type' => $priced['type'], 'message' => $quote === null
            ? 'Claim approved and moved to services.'
            : 'Approved. ' . ($waive ? 'The down payment was waived.' : 'The ' . ($quote['upfront'] ? 'payment' : 'down payment') . ' of ' . $money((float) $quote['down_payment_amount']) . ' is due by ' . date('M j, Y', strtotime($dueBy)) . '.')];
    }

    private function createService(array $app, float $price, string $note): int
    {
        $db = db_connect();
        $db->table('services')->insert([
            'plan_holder_id' => (int) $app['plan_holder_id'], 'branch_id' => (int) $app['branch_id'],
            'service_list_id' => (int) ($app['service_list_id'] ?? 0) > 0 ? (int) $app['service_list_id'] : null,
            'package_id' => (int) ($app['package_id'] ?? 0) > 0 ? (int) $app['package_id'] : null,
            'total_cost' => round($price, 2), 'service_date' => date('Y-m-d'), 'status' => 'pending', 'notes' => $note,
        ]);

        return (int) $db->insertID();
    }
    /**
     * Rejects a pending request. A reason is required, saved and sent to the client. An avail can
     * only be rejected by a Branch Admin (like approving it).
     *
     * @return array{ok:bool, message:string}
     */
    public function reject(int $applicationId, int $branchId, int $actorUserId, int $actorRole, string $reason): array
    {
        $this->expireDownPayments(null);

        $app = $this->loadApplication($applicationId, $branchId);
        if ($app === null) {
            return ['ok' => false, 'message' => 'Claim not found.'];
        }
        if ($app['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Only pending claims can be rejected.'];
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < self::REJECTION_REASON_MIN) {
            return ['ok' => false, 'message' => 'Enter the reason for rejecting this request (at least ' . self::REJECTION_REASON_MIN . ' characters). The client will see it.'];
        }
        if (! in_array($actorRole, [self::ROLE_BRANCH_ADMIN, self::ROLE_STAFF], true)) {
            return ['ok' => false, 'message' => 'You are not allowed to reject this.'];
        }

        $plan   = $this->planFor((int) $app['plan_holder_id']);
        $priced = $plan ? $this->priceItem((int) $app['plan_holder_id'], $this->itemFromApplication($app)) : null;
        if ($actorRole !== self::ROLE_BRANCH_ADMIN && $priced !== null && ! $this->staffMayDecide($priced, (int) $app['plan_holder_id'], $this->itemFromApplication($app))) {
            return ['ok' => false, 'message' => 'Only a Branch Admin can decide on a request that adds an amount to the balance with instalments to set (an avail, a service, or add-ons not paid in full up front). Add a note for the Branch Admin instead.'];
        }

        db_connect()->table('service_applications')->where('application_id', $applicationId)->update([
            'status' => 'rejected', 'rejection_reason' => $reason, 'decided_by' => $actorUserId, 'decided_at' => date('Y-m-d H:i:s'),
        ]);

        $what = $app['package_name'] ?? $app['service_name'] ?? 'your request';
        (new NotificationService())->notify((int) $app['client_user_id'], 'Your request for ' . $what . ' was rejected. Reason: ' . $reason, 'service_rejected', false, false, $plan ? (int) $plan['plan_id'] : null);
        (new ActivityLogService())->log($actorUserId, 'rejected', 'service_application', $applicationId, 'Rejected request #' . $applicationId . ': ' . $reason);

        return ['ok' => true, 'message' => 'Request rejected. The client was told the reason.'];
    }

    /**
     * Everything the review page needs about one request: its type, whether it can be approved
     * now, the full quote for each offered term (with and without a waiver), and - once approved -
     * the avail with its down payment, waiver and approver.
     *
     * @return array<string,mixed>|null
     */
    public function decisionInfo(int $applicationId, int $branchId): ?array
    {
        $this->expireDownPayments(null);

        $app = $this->loadApplication($applicationId, $branchId);
        if ($app === null) {
            return null;
        }

        $plan = $this->planFor((int) $app['plan_holder_id']);
        $type = $this->typeOfApplication($app, $plan);
        $info = ['type' => $type, 'plan' => $plan, 'eligibility' => null, 'quotes' => [], 'terms' => $this->config->availTerms(), 'avail' => null, 'balances' => $plan ? $this->balances((int) $plan['plan_id']) : null, 'down_payment_days' => (int) $this->config->availDownPaymentDays, 'waiver_min' => self::WAIVER_REASON_MIN];

        $info['priced'] = $plan !== null ? $this->priceItem((int) $app['plan_holder_id'], $this->itemFromApplication($app)) : null;
        $money = $info['priced'] !== null && $info['priced']['charge'] > 0.004;
        if ($app['status'] === 'pending' && $plan !== null && $info['priced'] !== null) {
            $info['eligibility'] = $this->eligibility((int) $app['plan_holder_id'], (int) $app['package_id'], $applicationId);
            if ($money) {
                $item  = $this->itemFromApplication($app);
                $probe = $this->quoteItem((int) $app['plan_holder_id'], $item, null, false);
                $info['upfront'] = ! empty($probe['upfront']);
                $terms = $info['upfront'] ? [0] : $this->config->availTerms();
                $info['terms'] = $info['upfront'] ? [] : $terms;
                foreach ($terms as $term) {
                    $info['quotes'][$term] = ['normal' => $this->quoteItem((int) $app['plan_holder_id'], $item, $term ?: null, false), 'waived' => $this->quoteItem((int) $app['plan_holder_id'], $item, null, true)];
                    if (($info['quotes'][$term]['waived']['terms_offered'] ?? []) === [1]) {
                        $info['quotes'][$term]['waived'] = $this->quoteItem((int) $app['plan_holder_id'], $item, 1, true);
                    } elseif ($term > 0) {
                        $info['quotes'][$term]['waived'] = $this->quoteItem((int) $app['plan_holder_id'], $item, $term, true);
                    }
                }
                $info['down_due_by'] = date('Y-m-d', strtotime('+' . (int) $this->config->availDownPaymentDays . ' days'));
            }
        }
        if ($this->tableReady('package_avails')) {
            $info['avail'] = db_connect()->table('package_avails pa')
                ->select('pa.*, ab.first_name AS approver_first, ab.last_name AS approver_last, wb.first_name AS waiver_first, wb.last_name AS waiver_last')
                ->join('users ab', 'ab.user_id = pa.approved_by', 'left')
                ->join('users wb', 'wb.user_id = pa.waived_by', 'left')
                ->where('pa.application_id', $applicationId)
                ->get(1)->getRowArray() ?: null;
            if ($info['avail'] !== null) {
                $info['avail']['down_paid'] = $this->downPaymentPaid((int) $info['avail']['avail_id']);
            }
        }

        return $info;
    }

    /**
     * May the service move to this status? An avail's service cannot start (ongoing) or finish
     * until its down payment is fully paid and verified, or waived.
     *
     * @return array{ok:bool, message:string}
     */
    public function checkServiceStatusChange(int $serviceId, string $newStatus): array
    {
        if (! in_array($newStatus, ['ongoing', 'completed'], true) || $this->downPaymentCleared($serviceId)) {
            return ['ok' => true, 'message' => ''];
        }

        $avail = db_connect()->table('package_avails')->where('service_id', $serviceId)->get(1)->getRowArray();
        if ($avail !== null && $avail['status'] === 'cancelled') {
            return ['ok' => false, 'message' => 'This request was cancelled because the down payment was not paid in time.'];
        }
        $left = $avail ? max(0.0, round((float) $avail['down_payment_amount'] - $this->downPaymentPaid((int) $avail['avail_id']), 2)) : 0.0;

        return ['ok' => false, 'message' => 'Waiting for the ' . $this->money($left) . ' down payment. The service can start once it is paid and verified.'];
    }

    /** Tells the client when their service starts or is completed. */
    public function onServiceStatusChanged(int $serviceId, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus || ! in_array($newStatus, ['ongoing', 'completed'], true)) {
            return;
        }

        $row = db_connect()->table('services s')
            ->select('ph.user_id, p.package_name, sl.service_name, s.plan_holder_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('packages p', 'p.package_id = s.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->where('s.service_id', $serviceId)->get(1)->getRowArray();
        if ($row === null) {
            return;
        }

        $what = $row['package_name'] ?? $row['service_name'] ?? 'your service';
        (new NotificationService())->notify(
            (int) $row['user_id'],
            $newStatus === 'ongoing' ? 'Your service (' . $what . ') has started.' : 'Your service (' . $what . ') has been completed.',
            $newStatus === 'completed' ? 'service_completed' : 'general'
        );
    }

    private function notifyDownPaymentReceived(int $availId): void
    {
        $a = db_connect()->table('package_avails pa')
            ->select('pa.plan_id, pa.down_payment_amount, pa.monthly_instalment, pa.term_months, pa.first_due_date, ph.user_id, p.package_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = pa.plan_holder_id', 'inner')
            ->join('packages p', 'p.package_id = pa.package_id', 'left')
            ->where('pa.avail_id', $availId)->get(1)->getRowArray();
        if ($a === null) {
            return;
        }

        (new NotificationService())->notify(
            (int) $a['user_id'],
            'Your down payment of ' . $this->money((float) $a['down_payment_amount']) . ' for ' . $a['package_name'] . ' was received and verified. Your service can start. Your first instalment of '
                . $this->money((float) $a['monthly_instalment']) . ' is due ' . ($a['first_due_date'] ? date('F j, Y', strtotime((string) $a['first_due_date'])) : 'next month') . '.',
            'payment_approved', false, false, (int) $a['plan_id']
        );
    }
    /* ======================================================================
     * For the client forms and the "My claims" page
     * ==================================================================== */

    /**
     * Everything a claim / avail / service form shows before it is submitted: the exact quote
     * (without and with the burial attire add-on) in a flat shape, and whether the client may apply.
     * The same numbers are used when the request is approved.
     *
     * @param array{package_id?:int, service_list_id?:int, route_id?:int} $item
     *
     * @return array{eligibility:array<string,mixed>, plain:array<string,mixed>, attire:array<string,mixed>}
     */
    public function formQuote(int $planHolderId, array $item): array
    {
        $this->expireDownPayments($planHolderId);
        $out = ['eligibility' => $this->eligibility($planHolderId, (int) ($item['package_id'] ?? 0)), 'plain' => [], 'attire' => []];

        foreach (['plain' => false, 'attire' => true] as $key => $attire) {
            $variant = ['attire' => $attire] + $item;
            $probe   = $this->quoteItem($planHolderId, $variant, null, false);
            if (empty($probe['ok'])) {
                continue;
            }

            $terms = [];
            if ($probe['package_amount_added'] > 0 && ! $probe['upfront']) {
                foreach ($this->config->availTerms() as $t) {
                    $q = $this->quoteItem($planHolderId, $variant, $t, false);
                    if (! empty($q['ok']) && $q['schedule'] !== []) {
                        $terms[] = ['term' => $t, 'monthly' => $q['monthly_instalment'], 'last' => end($q['schedule'])['amount'], 'monthly_total' => $q['monthly_total_due']];
                    }
                }
            }
            $out[$key] = [
                'name' => $probe['package']['name'], 'type' => $probe['type'], 'free' => $probe['free'], 'item_price' => $probe['package']['item_price'],
                'addons_total' => $probe['addons_total'], 'addons_label' => $probe['addons_label'], 'added' => $probe['package_amount_added'],
                'down' => $probe['down_payment_amount'], 'rate' => $probe['down_payment_rate'], 'upfront' => $probe['upfront'], 'financed' => $probe['financed_amount'],
                'terms' => $terms, 'plan_balance' => $probe['plan_balance'], 'plan_monthly' => $probe['plan_monthly'], 'total_after' => $probe['total_balance_after'],
                'down_days' => $probe['down_payment_days'],
            ];
        }

        return $out;
    }

    /**
     * Every request of one plan holder for the My claims page, newest first: type (Claim / Avail /
     * Service), what was asked for, add-ons, status, rejection reason, the terms and waiver, who
     * filed it, and what is still owed on it.
     *
     * @return list<array<string,mixed>>
     */
    public function myRequests(int $planHolderId): array
    {
        $this->expireDownPayments($planHolderId);
        $db = db_connect();
        $hasAvails = $this->tableReady('package_avails');
        $linkSvc   = $this->columnReady('service_applications', 'service_id') ? 'sa.service_id, ' : '';

        $rows = $db->query(
            "SELECT sa.*, p.package_name, p.is_damayan_entitlement, sl.service_name, r.route_name,
                    fu.first_name AS filer_first, fu.last_name AS filer_last, fr.role_name AS filer_role,
                    s.status AS service_status,
                    sb.remaining_balance AS legacy_remaining, sb.status AS legacy_status
               FROM service_applications sa
               LEFT JOIN packages p ON p.package_id = sa.package_id
               LEFT JOIN service_list sl ON sl.service_list_id = sa.service_list_id
               LEFT JOIN service_routes r ON r.route_id = sa.selected_route_id
               LEFT JOIN users fu ON fu.user_id = sa.filed_by_user_id
               LEFT JOIN roles fr ON fr.role_id = fu.role_id
               LEFT JOIN service_balances sb ON sb.application_id = sa.application_id
               LEFT JOIN services s ON s.service_id = COALESCE({$linkSvc}sb.service_id) AND s.deleted_at IS NULL
              WHERE sa.plan_holder_id = ?
              ORDER BY sa.application_id DESC",
            [$planHolderId]
        )->getResultArray();

        $avails = [];
        if ($hasAvails && $rows !== []) {
            $ids = array_map(static fn (array $r): int => (int) $r['application_id'], $rows);
            foreach ($db->table('package_avails pa')
                ->select('pa.*, wb.first_name AS waiver_first, wb.last_name AS waiver_last, sv.status AS avail_service_status')
                ->join('users wb', 'wb.user_id = pa.waived_by', 'left')
                ->join('services sv', 'sv.service_id = pa.service_id', 'left')
                ->whereIn('pa.application_id', $ids)->get()->getResultArray() as $a) {
                $a['down_paid'] = $this->downPaymentPaid((int) $a['avail_id']);
                $avails[(int) $a['application_id']] = $a;
            }
        }

        foreach ($rows as &$row) {
            $av = $avails[(int) $row['application_id']] ?? null;
            $row['avail'] = $av;

            if ((int) ($row['package_id'] ?? 0) <= 0) {
                $row['type_label'] = 'Service';
            } elseif (! empty($row['request_type'])) {
                $row['type_label'] = $row['request_type'] === self::TYPE_AVAIL ? 'Avail' : 'Claim';
            } else {
                $row['type_label'] = (int) ($row['is_damayan_entitlement'] ?? 0) === 1 ? 'Claim' : 'Avail';
            }
            $row['what'] = (string) ($row['package_name'] ?? $row['service_name'] ?? '-') . ($row['route_name'] ? ' - ' . $row['route_name'] : '');
            $row['addons'] = ! empty($row['burial_attire_selected']) ? [['label' => 'Burial Attire Package', 'price' => (float) ($row['burial_attire_price'] ?? ClaimSubmissionService::BURIAL_ATTIRE_PRICE)]] : [];
            $row['old_rule'] = ($row['rule_version'] ?? 'legacy') === 'legacy' && (string) $row['status'] === 'approved';

            // One plain status for the client.
            $svcStatus = (string) ($av['avail_service_status'] ?? $row['service_status'] ?? '');
            if ($row['status'] === 'pending') {
                $row['status_label'] = 'Pending';
            } elseif ($row['status'] === 'rejected') {
                $row['status_label'] = 'Rejected';
            } elseif ($av !== null && $av['status'] === 'cancelled' && ($av['kind'] ?? 'package') !== 'addons') {
                $row['status_label'] = 'Cancelled - no down payment';
            } elseif ($svcStatus === 'ongoing') {
                $row['status_label'] = 'Ongoing';
            } elseif ($svcStatus === 'completed') {
                $row['status_label'] = 'Completed';
            } elseif ($svcStatus === 'cancelled') {
                $row['status_label'] = 'Cancelled';
            } elseif ($av !== null && $av['status'] === 'awaiting_down_payment') {
                $row['status_label'] = 'Approved - waiting for the down payment';
            } else {
                $row['status_label'] = 'Approved';
            }

            $row['addon_cancelled'] = $av !== null && $av['status'] === 'cancelled' && ($av['kind'] ?? 'package') === 'addons';
            $row['amount_added'] = $av !== null && ! $row['addon_cancelled'] ? (float) $av['package_price'] : ($row['old_rule'] ? (float) ($row['legacy_remaining'] ?? 0) : 0.0);
            $row['down_left'] = $av !== null && $av['status'] === 'awaiting_down_payment' ? max(0.0, round((float) $av['down_payment_amount'] - (float) $av['down_paid'], 2)) : 0.0;
        }
        unset($row);

        return $rows;
    }
    /* ======================================================================
     * The two-part picture: plan + package, the same everywhere
     * (client pages, staff pages, the collection list, receipts, the ledger PDF)
     * ==================================================================== */

    /**
     * What a client owes, split into the plan part and the package part. Only meaningful when
     * has_package is true; a client with no avail never sees any of it (every screen checks).
     *
     * @return array{has_package:bool, plan_id:int, plan:array<string,mixed>, package:array<string,mixed>, total:array<string,mixed>, pending_amount:float, pending_count:int}
     */
    public function moneyView(int $planId, ?string $asOf = null): array
    {
        $asOf ??= date('Y-m-d');
        $db    = db_connect();
        $empty = ['has_package' => false, 'plan_id' => $planId, 'plan' => [], 'package' => [], 'total' => [], 'pending_amount' => 0.0, 'pending_count' => 0];
        if ($planId <= 0 || ! $this->tableReady('package_avails')) {
            return $empty;
        }

        $avails = $db->table('package_avails pa')
            ->select('pa.*, p.package_name, sl.service_name')
            ->join('packages p', 'p.package_id = pa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = pa.service_list_id', 'left')
            ->where('pa.plan_id', $planId)
            ->whereIn('pa.status', ['awaiting_down_payment', 'active'])
            ->orderBy('pa.avail_id')
            ->get()->getResultArray();
        if ($avails === []) {
            return $empty;
        }

        $summary = (new ClientPlanSummary())->forPlan($planId) ?? [];
        $planBal = (float) ($summary['remaining_balance'] ?? 0);
        $planMonthly = $planBal > 0.004 ? (float) ($summary['monthly_fee'] ?? 0) : 0.0;
        $planOverdue = (float) ($summary['overdue_amount'] ?? 0);

        $items = [];
        $pkgBalance = 0.0; $pkgMonthly = 0.0; $pkgOverdue = 0.0; $pkgOverdueCount = 0; $downDue = 0.0; $nextDue = null;
        foreach ($avails as $a) {
            $paid = (float) ($db->table('payment_allocations')->selectSum('amount', 't')->where('avail_id', (int) $a['avail_id'])->get()->getRowArray()['t'] ?? 0);
            $balance = max(0.0, round((float) $a['package_price'] - $paid, 2));

            $inst = $db->table('package_avail_instalments')->where('avail_id', (int) $a['avail_id'])->orderBy('number')->get()->getResultArray();
            $next = null; $over = 0.0; $overN = 0;
            foreach ($inst as $i) {
                $left = round((float) $i['amount'] - (float) $i['paid_amount'], 2);
                if ($left <= 0.004) {
                    continue;
                }
                $next ??= $i + ['left' => $left];
                if (strcmp((string) $i['due_date'], $asOf) < 0) {
                    $over += $left;
                    $overN++;
                }
            }
            $dl = $a['status'] === 'awaiting_down_payment' ? max(0.0, round((float) $a['down_payment_amount'] - $this->downPaymentPaid((int) $a['avail_id']), 2)) : 0.0;

            $pkgBalance += $balance; $pkgOverdue += $over; $pkgOverdueCount += $overN; $downDue += $dl;
            if ($next !== null) {
                $pkgMonthly += (float) $next['left'];
                $nextDue = $nextDue === null || strcmp((string) $next['due_date'], $nextDue) < 0 ? (string) $next['due_date'] : $nextDue;
            }
            $items[] = [
                'avail_id' => (int) $a['avail_id'], 'label' => (string) ($a['package_name'] ?? $a['service_name'] ?? 'Package') . ((float) $a['addons_amount'] > 0 && $a['kind'] === 'addons' ? ' (add-on)' : ''),
                'kind' => (string) $a['kind'], 'price' => (float) $a['package_price'], 'balance' => $balance, 'term' => (int) $a['term_months'],
                'instalment' => $next !== null ? (float) $next['left'] : 0.0, 'next_number' => $next !== null ? (int) $next['number'] : 0, 'next_due' => $next !== null ? (string) $next['due_date'] : null,
                'overdue' => round($over, 2), 'overdue_count' => $overN, 'down_due' => $dl, 'down_due_by' => $a['down_payment_due_by'], 'status' => (string) $a['status'],
                'waived' => $a['down_payment_status'] === 'waived',
            ];
        }

        $pending = $db->table('payments')->select('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS t', false)->where('plan_id', $planId)->where('status', 'pending')->get()->getRowArray();

        return [
            'has_package' => true, 'plan_id' => $planId,
            'plan' => ['monthly' => $planMonthly, 'balance' => $planBal, 'overdue' => $planOverdue, 'overdue_count' => (int) ($summary['overdue_count'] ?? 0), 'next_due' => $summary['next_due_date'] ?? null],
            'package' => ['monthly' => round($pkgMonthly, 2), 'balance' => round($pkgBalance, 2), 'overdue' => round($pkgOverdue, 2), 'overdue_count' => $pkgOverdueCount, 'down_due' => round($downDue, 2), 'next_due' => $nextDue, 'items' => $items],
            'total' => ['monthly' => round($planMonthly + $pkgMonthly, 2), 'balance' => round($planBal + $pkgBalance, 2), 'overdue' => round($planOverdue + $pkgOverdue, 2)],
            'pending_amount' => round((float) ($pending['t'] ?? 0), 2), 'pending_count' => (int) ($pending['n'] ?? 0),
        ];
    }

    /**
     * The package part of a plan as it stood on a date, for the overdue report: what is still owed on
     * every package, and the instalments that were due before the date and not paid by it (optionally only
     * those due inside a range). Payments made after the date do not count yet.
     *
     * @return array{has_package:bool, balance:float, overdue_count:int, overdue_amount:float, oldest_due:?string}
     */
    public function packageStandingAsOf(int $planId, string $asOf, string $dueFrom = '', string $dueTo = ''): array
    {
        $none = ['has_package' => false, 'balance' => 0.0, 'overdue_count' => 0, 'overdue_amount' => 0.0, 'oldest_due' => null];
        if (! $this->tableReady('package_avails')) {
            return $none;
        }
        $db = db_connect();
        $avails = $db->table('package_avails')->where('plan_id', $planId)->whereIn('status', ['awaiting_down_payment', 'active', 'settled'])->where('DATE(approved_at) <=', $asOf)->get()->getResultArray();
        if ($avails === []) {
            return $none;
        }
        $paidBy = static function (string $col, int $id) use ($db, $asOf): float {
            return (float) ($db->table('payment_allocations al')->selectSum('al.amount', 't')->join('payments p', 'p.payment_id = al.payment_id', 'inner')
                ->where("al.$col", $id)->where('p.status', 'paid')->where('p.payment_date <=', $asOf)->get()->getRowArray()['t'] ?? 0);
        };
        $out = ['has_package' => true, 'balance' => 0.0, 'overdue_count' => 0, 'overdue_amount' => 0.0, 'oldest_due' => null];
        foreach ($avails as $a) {
            $out['balance'] += max(0.0, round((float) $a['package_price'] - $paidBy('avail_id', (int) $a['avail_id']), 2));
            foreach ($db->table('package_avail_instalments')->where('avail_id', (int) $a['avail_id'])->orderBy('number')->get()->getResultArray() as $i) {
                if (strcmp((string) $i['due_date'], $asOf) >= 0 || ($dueFrom !== '' && strcmp((string) $i['due_date'], $dueFrom) < 0) || ($dueTo !== '' && strcmp((string) $i['due_date'], $dueTo) > 0)) {
                    continue;
                }
                $left = round((float) $i['amount'] - $paidBy('instalment_id', (int) $i['instalment_id']), 2);
                if ($left > 0.004) {
                    $out['overdue_count']++;
                    $out['overdue_amount'] += $left;
                    $out['oldest_due'] = $out['oldest_due'] === null || strcmp((string) $i['due_date'], $out['oldest_due']) < 0 ? (string) $i['due_date'] : $out['oldest_due'];
                }
            }
        }
        $out['balance'] = round($out['balance'], 2);
        $out['overdue_amount'] = round($out['overdue_amount'], 2);

        return $out;
    }

    /**
     * The package part of a collection list row, for many plans at once: what is due on or before
     * the date, what is overdue, and the monthly instalment - so the list and the route sheet show which
     * part is behind.
     *
     * @param list<int> $planIds
     *
     * @return array<int, array{due:float, overdue:float, overdue_count:int, monthly:float, down_due:float}>
     */
    public function collectionParts(array $planIds, string $asOf): array
    {
        if ($planIds === [] || ! $this->tableReady('package_avails')) {
            return [];
        }
        $db  = db_connect();
        $out = [];
        $rows = $db->table('package_avails pa')->select('pa.avail_id, pa.plan_id, pa.status, pa.down_payment_amount')->whereIn('pa.plan_id', $planIds)->whereIn('pa.status', ['awaiting_down_payment', 'active'])->get()->getResultArray();
        foreach ($rows as $a) {
            $pid = (int) $a['plan_id'];
            $out[$pid] ??= ['due' => 0.0, 'overdue' => 0.0, 'overdue_count' => 0, 'monthly' => 0.0, 'down_due' => 0.0];
            if ($a['status'] === 'awaiting_down_payment') {
                $out[$pid]['down_due'] += max(0.0, round((float) $a['down_payment_amount'] - $this->downPaymentPaid((int) $a['avail_id']), 2));
            }
            $first = true;
            foreach ($db->table('package_avail_instalments')->where('avail_id', (int) $a['avail_id'])->orderBy('number')->get()->getResultArray() as $i) {
                $left = round((float) $i['amount'] - (float) $i['paid_amount'], 2);
                if ($left <= 0.004) {
                    continue;
                }
                if ($first) {
                    $out[$pid]['monthly'] += $left;
                    $first = false;
                }
                if (strcmp((string) $i['due_date'], $asOf) <= 0) {
                    $out[$pid]['due'] += $left;
                }
                if (strcmp((string) $i['due_date'], $asOf) < 0) {
                    $out[$pid]['overdue'] += $left;
                    $out[$pid]['overdue_count']++;
                }
            }
        }

        return $out;
    }

    /**
     * Where a payment went, for the receipt: "Plan Oct 2026 ₱240.00", "Instalment 1 of 12 ₱2,916.67",
     * "Down payment ₱15,000.00". Empty for a payment with no allocation rows (every payment of a client
     * without an avail), so those receipts stay exactly as they are.
     *
     * @return list<array{label:string, amount:float}>
     */
    public function appliedTo(int $paymentId): array
    {
        if (! $this->tableReady('payment_allocations')) {
            return [];
        }
        $db   = db_connect();
        $rows = $db->table('payment_allocations a')
            ->select('a.target_type, a.contribution_number, a.amount, a.plan_id, i.number AS inst_no, pa.term_months, pa.kind, p.package_name, sl.service_name')
            ->join('package_avail_instalments i', 'i.instalment_id = a.instalment_id', 'left')
            ->join('package_avails pa', 'pa.avail_id = a.avail_id', 'left')
            ->join('packages p', 'p.package_id = pa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = pa.service_list_id', 'left')
            ->where('a.payment_id', $paymentId)
            ->orderBy('a.allocation_id')
            ->get()->getResultArray();
        if ($rows === []) {
            return [];
        }

        $plan   = $db->table('plans')->where('plan_id', (int) $rows[0]['plan_id'])->get(1)->getRowArray() ?: [];
        $marker = (string) ($plan['contribution_cycle_started_at'] ?? '');
        $anchor = date('Y-m-d', strtotime($marker !== '' ? $marker : (string) ($plan['start_date'] ?? date('Y-m-d'))));

        $out = [];
        foreach ($rows as $r) {
            $what = (string) ($r['package_name'] ?? $r['service_name'] ?? 'package');
            if ($r['target_type'] === 'plan_contribution') {
                $label = 'Plan ' . date('M Y', strtotime(PlanLedgerService::addMonths($anchor, max(0, (int) $r['contribution_number'] - 1))));
            } elseif ($r['target_type'] === 'avail_instalment') {
                $label = 'Instalment ' . (int) $r['inst_no'] . ' of ' . (int) $r['term_months'] . ' (' . $what . ')';
            } else {
                $label = ($r['kind'] === 'addons' ? 'Add-on payment' : 'Down payment') . ' (' . $what . ')';
            }
            $out[] = ['label' => $label, 'amount' => (float) $r['amount']];
        }

        return $out;
    }
    /* ======================================================================
     * Small helpers
     * ==================================================================== */

    private function columnReady(string $table, string $column): bool
    {
        return self::$tableReady[$table . '.' . $column] ??= db_connect()->fieldExists($column, $table);
    }

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
