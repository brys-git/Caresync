<?php

namespace App\Services;

use Config\CareSync;

/**
 * PlanClosureService - what happens to a plan when a claim is approved and when a plan ends.
 * (Kaagapay's claim policy, confirmed. The numbers and switches are in Config\CareSync and in the constants of
 * ClaimAvailService; nothing here is decided by a controller or a view.)
 *
 *   WAIVER   Approving a CLAIM (or, while ClaimAvailService::AVAIL_WAIVES_PLAN_BALANCE is true, an AVAIL of a
 *            package) waives whatever is left of the plan price in the current cycle: plan_waivers records it,
 *            the cycle is closed at once and a new cycle starts at 0 months (CycleService::closeCycle()).
 *   WHO DIED The plan holder  -> when the service is COMPLETED the plan is closed ('closed_deceased', or
 *                                'closed_deceased_owing' while an avail balance is still being paid), the holder is
 *                                'deceased' and the login is deactivated. No more dues.
 *            A beneficiary    -> that beneficiary is marked deceased (never claimable again); the plan holder
 *                                continues (default) or asks the branch to close the plan.
 *   CLOSING  A Branch Admin closes a plan on the client's request ('closed_by_request'; the holder is 'closed').
 *            A plan with an unpaid avail balance cannot be closed.
 */
class PlanClosureService
{
    /** plans.status values of a plan that has ended. */
    public const CLOSED_STATUSES = ['closed_deceased', 'closed_deceased_owing', 'closed_by_request'];

    public const REASONS = [
        'stopped_after_claim' => 'The client chose to stop after the claim',
        'requested_by_client' => 'Requested by the client',
        'other'               => 'Other',
    ];

    private CareSync $cfg;

    public function __construct(?CareSync $cfg = null)
    {
        $this->cfg = $cfg ?? config('CareSync');
    }

    /** Are the policy tables / columns there? (a database that has not run the migration just keeps the old behaviour) */
    public static function ready(): bool
    {
        static $ready = null;
        $db = db_connect();

        return $ready ??= $db->tableExists('plan_waivers') && $db->tableExists('plan_closure_requests') && $db->fieldExists('deceased_type', 'service_applications') && $db->fieldExists('closed_at', 'plans');
    }

    public static function isClosedStatus(?string $status): bool
    {
        return in_array((string) $status, self::CLOSED_STATUSES, true);
    }

    // ===================================================================== on approval

    /**
     * Called right after a request was approved and saved. Waives the plan balance, closes the cycle, marks a
     * deceased beneficiary, records who answers for an avail balance, and tells the client.
     *
     * @param array<string,mixed> $app     the application row (loadApplication())
     * @param array<string,mixed> $plan    the plan row BEFORE the cycle moves on
     * @param array<string,mixed> $priced  ClaimAvailService::priceItem()
     *
     * @return array{waived:float, closed_cycle:int, beneficiary_died:bool, holder_died:bool}
     */
    public function onRequestApproved(array $app, array $plan, array $priced, int $availId, int $actorUserId): array
    {
        $out = ['waived' => 0.0, 'closed_cycle' => 0, 'beneficiary_died' => false, 'holder_died' => false];
        if (! self::ready()) {
            return $out;
        }

        $db = db_connect();
        $isClaim   = $priced['type'] === ClaimAvailService::TYPE_CLAIM;
        $isPackage = $priced['type'] === ClaimAvailService::TYPE_AVAIL && ($priced['kind'] ?? '') === 'package';
        $waives    = $isClaim || ($isPackage && ClaimAvailService::AVAIL_WAIVES_PLAN_BALANCE);
        $planId    = (int) $plan['plan_id'];
        $now       = date('Y-m-d H:i:s');

        $deceasedType = (string) ($app['deceased_type'] ?? '');
        $out['holder_died']      = $deceasedType === 'plan_holder';
        $out['beneficiary_died'] = $deceasedType === 'beneficiary';

        // Who answers for any avail balance: the claimant.
        if ($availId > 0) {
            [$name, $relationship, $contact] = $this->responsiblePerson($app);
            $db->table('package_avails')->where('avail_id', $availId)->update([
                'responsible_name' => $name !== '' ? mb_substr($name, 0, 150) : null,
                'responsible_relationship' => $relationship !== '' ? mb_substr($relationship, 0, 100) : null,
                'responsible_contact' => $contact !== '' ? mb_substr($contact, 0, 50) : null,
            ]);
        }

        if ($waives) {
            $cycleNo = (int) ($plan['current_cycle_number'] ?? 1);
            $waived  = (new ClaimAvailService())->balances($planId)['plan_balance'];
            if ($waived > 0.004) {
                $db->table('plan_waivers')->insert([
                    'plan_id' => $planId, 'cycle_number' => $cycleNo, 'amount' => round($waived, 2),
                    'reason' => $isClaim ? 'claim' : 'avail', 'application_id' => (int) $app['application_id'],
                    'waived_at' => $now, 'approved_by' => $actorUserId,
                ]);
            }
            (new CycleService())->closeCycle($planId);
            $out['waived'] = round($waived, 2);
            $out['closed_cycle'] = $cycleNo;
        }

        if ($out['beneficiary_died'] && (int) ($app['deceased_beneficiary_id'] ?? 0) > 0) {
            $db->table('beneficiaries')
                ->where('beneficiary_id', (int) $app['deceased_beneficiary_id'])
                ->where('plan_holder_id', (int) $app['plan_holder_id'])
                ->update(['status' => 'deceased', 'deceased_at' => $now]);
        }

        return $out;
    }

    /** @return array{0:string,1:string,2:string} name, relationship, contact of the person who answers for an avail balance */
    private function responsiblePerson(array $app): array
    {
        $db = db_connect();
        $name = trim((string) ($app['claimant_name'] ?? ''));
        $relationship = trim((string) ($app['claimant_relationship'] ?? ''));
        $contact = trim((string) ($app['beneficiary_contact'] ?? ''));

        if ($name === '') {
            // The plan holder filed it themselves: they answer for it.
            $u = $db->table('plan_holders ph')->select('u.first_name, u.middle_name, u.last_name, u.contact_number')->join('users u', 'u.user_id = ph.user_id', 'inner')->where('ph.plan_holder_id', (int) $app['plan_holder_id'])->get(1)->getRowArray() ?: [];
            $name = trim(implode(' ', array_filter([$u['first_name'] ?? '', $u['middle_name'] ?? '', $u['last_name'] ?? ''])));
            $relationship = $relationship !== '' ? $relationship : 'Plan holder';
            $contact = $contact !== '' ? $contact : (string) ($u['contact_number'] ?? '');
        }

        return [$name, $relationship, $contact];
    }

    // ===================================================================== plan holder died: on completion

    /**
     * The service of an approved request was COMPLETED. When the plan holder was the one who died, the plan ends now.
     */
    public function onServiceCompleted(int $serviceId, ?int $actorUserId = null): void
    {
        if (! self::ready() || $serviceId <= 0) {
            return;
        }
        $db = db_connect();
        $app = $db->table('service_applications')->where('service_id', $serviceId)->where('status', 'approved')->orderBy('application_id', 'DESC')->get(1)->getRowArray();
        if (! $app || ($app['deceased_type'] ?? '') !== 'plan_holder') {
            return;
        }

        $claimAvail = new ClaimAvailService();
        $plan = $claimAvail->planFor((int) $app['plan_holder_id']);
        if (! $plan) {
            return;
        }
        $planId = (int) $plan['plan_id'];
        $owing  = $claimAvail->balances($planId);
        $debt   = round($owing['package_balance'] + $owing['legacy_balance'], 2);
        $status = $debt > 0.004 ? 'closed_deceased_owing' : 'closed_deceased';
        $actor  = (int) ($actorUserId ?? session('user_id') ?? 0);

        $db->transBegin();
        try {
            $this->endPlan($plan, $status, 'Plan holder deceased - service completed', $actor, 'deceased');
            // The login of the person who died is switched off (the sign-out filter does the rest).
            $holder = $db->table('plan_holders')->select('user_id')->where('plan_holder_id', (int) $plan['plan_holder_id'])->get(1)->getRowArray();
            if ($holder) {
                $db->table('users')->where('user_id', (int) $holder['user_id'])->update(['status' => 'inactive']);
            }
            (new ActivityLogService())->log($actor, 'updated', 'plan', $planId, 'Plan closed: plan holder deceased (' . $status . ')', ['status' => $plan['status']], ['status' => $status]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Closing the plan failed');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'PlanClosureService::onServiceCompleted failed for service ' . $serviceId . ' (' . get_class($e) . ')');

            return;
        }

        $this->notifyClaimant($app, $debt);
    }

    /** A package balance of a plan kept open for it ('closed_deceased_owing') was paid: close the plan for good. */
    public function settleOwing(int $planId): void
    {
        if (! self::ready() || $planId <= 0) {
            return;
        }
        $db = db_connect();
        $plan = $db->table('plans')->where('plan_id', $planId)->get(1)->getRowArray();
        if (! $plan || $plan['status'] !== 'closed_deceased_owing') {
            return;
        }
        $b = (new ClaimAvailService())->balances($planId);
        if ($b['package_balance'] + $b['legacy_balance'] > 0.004) {
            return;
        }
        $db->table('plans')->where('plan_id', $planId)->update(['status' => 'closed_deceased']);
        (new ActivityLogService())->log((int) (session('user_id') ?? 0), 'updated', 'plan', $planId, 'Plan closed: the avail balance of the deceased plan holder is paid', ['status' => 'closed_deceased_owing'], ['status' => 'closed_deceased']);
    }

    private function notifyClaimant(array $app, float $debt): void
    {
        $db = db_connect();
        $contact = preg_replace('/\D+/', '', (string) ($app['beneficiary_contact'] ?? ''));
        $claimantUser = null;
        if (strlen($contact) >= 10) {
            $claimantUser = $db->query("SELECT user_id FROM users WHERE role_id = 4 AND status = 'active' AND REPLACE(REPLACE(contact_number, ' ', ''), '-', '') LIKE ? LIMIT 1", ['%' . substr($contact, -10)])->getRowArray();
        }
        $message = 'The plan of the late plan holder was closed after the service was completed. No more contributions are due.' . ($debt > 0.004 ? ' The package balance of ' . cs_peso($debt) . ' remains and is still collected.' : '');
        if ($claimantUser) {
            (new NotificationService())->notify((int) $claimantUser['user_id'], $message, 'general');
        } else {
            log_message('info', 'Plan of plan holder #' . (int) $app['plan_holder_id'] . ' closed (deceased); the claimant has no account to notify.');
        }
    }

    // ===================================================================== closing a plan at the client's request

    /** The client asks their branch to close the plan. @return array{ok:bool, message:string} */
    public function requestClosure(int $planHolderId, int $userId, string $reason): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'This is not available yet.'];
        }
        $db = db_connect();
        $holder = $db->table('plan_holders')->where('plan_holder_id', $planHolderId)->where('user_id', $userId)->get(1)->getRowArray();
        $plan = $holder ? (new ClaimAvailService())->planFor($planHolderId) : null;
        if (! $holder || ! $plan || $holder['status'] !== 'active') {
            return ['ok' => false, 'message' => 'Only an active plan can be closed.'];
        }
        if ($db->table('plan_closure_requests')->where('plan_holder_id', $planHolderId)->where('status', 'pending')->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'You already asked your branch to close your plan. It is waiting for the Branch Admin.'];
        }
        $reason = trim($reason);
        $db->table('plan_closure_requests')->insert([
            'plan_id' => (int) $plan['plan_id'], 'plan_holder_id' => $planHolderId, 'branch_id' => (int) $holder['branch_id'],
            'requested_by' => $userId, 'reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null, 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $name = $db->table('users')->select('first_name, last_name')->where('user_id', $userId)->get(1)->getRowArray() ?: [];
        $notify = new NotificationService();
        foreach ($db->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', (int) $holder['branch_id'])->where('status', 'active')->get()->getResultArray() as $ba) {
            $notify->notify((int) $ba['user_id'], trim((string) ($name['first_name'] ?? '') . ' ' . (string) ($name['last_name'] ?? '')) . ' asked to close their plan. Open Closure requests.', 'general');
        }
        (new ActivityLogService())->log($userId, 'created', 'plan_closure_request', (int) $db->insertID(), 'Client asked to close their plan');

        return ['ok' => true, 'message' => 'Your request was sent to your branch. The Branch Admin will contact you. You can cancel it until it is closed.'];
    }

    /** The client cancels a pending request. @return array{ok:bool, message:string} */
    public function cancelRequest(int $requestId, int $userId): array
    {
        $db = db_connect();
        $r = $db->table('plan_closure_requests')->where('request_id', $requestId)->where('requested_by', $userId)->where('status', 'pending')->get(1)->getRowArray();
        if (! $r) {
            return ['ok' => false, 'message' => 'There is no pending request to cancel.'];
        }
        $db->table('plan_closure_requests')->where('request_id', $requestId)->update(['status' => 'cancelled', 'decided_at' => date('Y-m-d H:i:s'), 'decided_by' => $userId]);

        return ['ok' => true, 'message' => 'Your request to close the plan was cancelled. Your plan continues as before.'];
    }

    /** The client's own pending request, or null. @return array<string,mixed>|null */
    public function pendingFor(int $planHolderId): ?array
    {
        if (! self::ready()) {
            return null;
        }

        return db_connect()->table('plan_closure_requests')->where('plan_holder_id', $planHolderId)->where('status', 'pending')->orderBy('request_id', 'DESC')->get(1)->getRowArray() ?: null;
    }

    /** Pending requests of a branch (newest last), with the client's name. @return list<array<string,mixed>> */
    public function pendingForBranch(int $branchId): array
    {
        if (! self::ready() || $branchId <= 0) {
            return [];
        }

        return db_connect()->table('plan_closure_requests r')
            ->select('r.*, ph.unique_identifier, u.first_name, u.last_name, u.contact_number')
            ->join('plan_holders ph', 'ph.plan_holder_id = r.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('r.branch_id', $branchId)->where('r.status', 'pending')->orderBy('r.request_id', 'ASC')->get()->getResultArray();
    }

    public function pendingCount(int $branchId): int
    {
        return self::ready() && $branchId > 0 ? (int) db_connect()->table('plan_closure_requests')->where('branch_id', $branchId)->where('status', 'pending')->countAllResults() : 0;
    }

    /**
     * Can this plan be closed now? An unpaid avail balance (or a request still in progress) says no, with the reason.
     *
     * @return array{ok:bool, message:string, owed:float}
     */
    public function canClose(int $planHolderId): array
    {
        $claimAvail = new ClaimAvailService();
        $plan = $claimAvail->planFor($planHolderId);
        if (! $plan) {
            return ['ok' => false, 'message' => 'This plan holder has no active plan to close.', 'owed' => 0.0];
        }
        $b = $claimAvail->balances((int) $plan['plan_id']);
        $owed = round($b['package_balance'] + $b['legacy_balance'], 2);
        if ($owed > 0.004) {
            return ['ok' => false, 'message' => 'This plan cannot be closed yet: an avail balance of ' . cs_peso($owed) . ' is still unpaid. It can be closed once that balance is paid.', 'owed' => $owed];
        }
        return ['ok' => true, 'message' => '', 'owed' => 0.0];
    }

    /**
     * The Branch Admin closes a plan (own branch only, checked here). After it: no dues, out of the collection lists,
     * route sheets and overdue reports; every record is kept. The client may still sign in read-only
     * (Config\CareSync::$closedAccountsCanLogin) - otherwise the login is switched off.
     *
     * @return array{ok:bool, message:string}
     */
    public function close(int $planHolderId, int $branchId, int $actorUserId, string $reasonCode, string $note): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'Closing plans is not available yet.'];
        }
        $db = db_connect();
        $holder = $db->table('plan_holders')->where('plan_holder_id', $planHolderId)->get(1)->getRowArray();
        if (! $holder || $branchId <= 0 || (int) $holder['branch_id'] !== $branchId) {
            return ['ok' => false, 'message' => 'That plan holder is not in your branch.'];
        }
        if (! isset(self::REASONS[$reasonCode])) {
            return ['ok' => false, 'message' => 'Choose why the plan is being closed.'];
        }
        $note = trim($note);
        if ($reasonCode === 'other' && mb_strlen($note) < 3) {
            return ['ok' => false, 'message' => 'Write the reason when you choose "Other".'];
        }
        if ($holder['status'] !== 'active') {
            return ['ok' => false, 'message' => 'Only an active plan can be closed.'];
        }
        $can = $this->canClose($planHolderId);
        if (! $can['ok']) {
            return ['ok' => false, 'message' => $can['message']];
        }
        $plan = (new ClaimAvailService())->planFor($planHolderId);
        $reason = self::REASONS[$reasonCode] . ($note !== '' ? ': ' . $note : '');
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $this->endPlan($plan, 'closed_by_request', $reason, $actorUserId, 'closed');
            if (! $this->cfg->closedAccountsCanLogin) {
                $db->table('users')->where('user_id', (int) $holder['user_id'])->update(['status' => 'inactive']);
            }
            $db->table('plan_closure_requests')->where('plan_holder_id', $planHolderId)->where('status', 'pending')->update(['status' => 'closed', 'decided_at' => $now, 'decided_by' => $actorUserId]);
            (new ActivityLogService())->log($actorUserId, 'updated', 'plan', (int) $plan['plan_id'], 'Plan closed by the Branch Admin: ' . $reason, ['status' => $plan['status']], ['status' => 'closed_by_request']);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Closing the plan failed');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'PlanClosureService::close failed for plan holder ' . $planHolderId . ' (' . get_class($e) . ')');

            return ['ok' => false, 'message' => 'The plan could not be closed. Nothing was changed - please try again.'];
        }

        (new NotificationService())->notify((int) $holder['user_id'], 'Your plan was closed on ' . date('F j, Y') . '. No more contributions are due. ' . ($this->cfg->closedAccountsCanLogin ? 'You can still sign in to see your ledger and download your receipts.' : ''), 'general');

        return ['ok' => true, 'message' => 'The plan was closed. No more dues; every record is kept.'];
    }

    /** Sets a plan (and its holder) to an ended state and stops every due. */
    private function endPlan(array $plan, string $planStatus, string $reason, int $actorUserId, string $holderStatus): void
    {
        $db = db_connect();
        $db->table('plans')->where('plan_id', (int) $plan['plan_id'])->update([
            'status' => $planStatus, 'closed_at' => date('Y-m-d H:i:s'), 'closed_reason' => mb_substr($reason, 0, 255), 'closed_by' => $actorUserId > 0 ? $actorUserId : null,
            'next_due_date' => null, 'overdue_months' => 0, 'lifecycle_status' => 'closed',
        ]);
        $db->table('plan_holders')->where('plan_holder_id', (int) $plan['plan_holder_id'])->update(['status' => $holderStatus]);
    }

    // ===================================================================== what the screens show

    /**
     * Waivers of a plan, newest first.
     *
     * @return list<array<string,mixed>>
     */
    public function waivers(int $planId): array
    {
        if (! self::ready() || $planId <= 0) {
            return [];
        }

        return db_connect()->table('plan_waivers')->where('plan_id', $planId)->orderBy('waiver_id', 'DESC')->get()->getResultArray();
    }

    public function totalWaived(int $planId): float
    {
        return round(array_sum(array_map(static fn (array $w): float => (float) $w['amount'], $this->waivers($planId))), 2);
    }

    /**
     * The notice a plan holder sees after a BENEFICIARY's claim: the latest such waiver of the plan while the plan
     * is active, or null.
     *
     * @return array{amount:float, waived_at:string, cycle_number:int}|null
     */
    public function beneficiaryClaimNotice(int $planHolderId): ?array
    {
        if (! self::ready()) {
            return null;
        }
        $plan = (new ClaimAvailService())->planFor($planHolderId);
        if (! $plan) {
            return null;
        }
        $row = db_connect()->table('plan_waivers w')
            ->select('w.amount, w.waived_at, w.cycle_number')
            ->join('service_applications sa', 'sa.application_id = w.application_id', 'inner')
            ->where('w.plan_id', (int) $plan['plan_id'])->where('sa.deceased_type', 'beneficiary')
            ->where('w.cycle_number >=', max(1, (int) $plan['current_cycle_number'] - 1))
            ->orderBy('w.waiver_id', 'DESC')->get(1)->getRowArray();

        return $row ? ['amount' => (float) $row['amount'], 'waived_at' => (string) $row['waived_at'], 'cycle_number' => (int) $row['cycle_number']] : null;
    }

    /**
     * The closed state of a plan holder's latest plan (status, label, date, reason), or null while it is open.
     *
     * @return array{status:string, label:string, closed_at:?string, reason:?string}|null
     */
    public function closedInfo(int $planHolderId): ?array
    {
        if (! self::ready() || $planHolderId <= 0) {
            return null;
        }
        $p = db_connect()->table('plans')->select('status, closed_at, closed_reason')->where('plan_holder_id', $planHolderId)->whereIn('status', self::CLOSED_STATUSES)->orderBy('plan_id', 'DESC')->get(1)->getRowArray();

        return $p ? ['status' => (string) $p['status'], 'label' => self::closedLabel((string) $p['status']), 'closed_at' => $p['closed_at'], 'reason' => $p['closed_reason']] : null;
    }

    /** Human label of a plan holder's / plan's closed state, for lists. */
    public static function closedLabel(?string $planStatus): string
    {
        return match ((string) $planStatus) {
            'closed_deceased'       => 'Closed - plan holder deceased',
            'closed_deceased_owing' => 'Closed - deceased, balance still owed',
            'closed_by_request'     => 'Closed by request',
            default                 => '',
        };
    }
}
