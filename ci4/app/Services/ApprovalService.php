<?php

namespace App\Services;

use App\Services\MembershipService;
use App\Services\NotificationService;

/**
 * ApprovalService
 * 
 * Handles all approval workflows for:
 * - Initial plan holder registration
 * - Service applications
 * - Package/service creation
 */
class ApprovalService
{
    /**
     * The ONE way a registration (initial) payment activates a plan holder. Registration approvals -> Verify, Plan holders ->
     * Approve and Payment tracking -> Approve all end here, so they all end in exactly the same state:
     * payment 'paid', plan + plan holder active, cycle / ledger / months worked out, ID Control No. assigned, client told.
     *
     * Conditions: the payment is 'paid' and the plan holder is still 'inactive'. Everything happens in one transaction.
     */
    public function approveInitialPayment(int $paymentId): bool
    {
        if ($paymentId <= 0) {
            return false;
        }

        $payment = db_connect()->table('payments pay')
            ->select('pay.*, p.plan_holder_id, ph.status AS holder_status, u.user_id')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('pay.payment_id', $paymentId)
            ->get()
            ->getRowArray();

        if (! $payment || (string) ($payment['status'] ?? '') !== 'paid' || (string) ($payment['holder_status'] ?? '') !== 'inactive') {
            return false;
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $planHolderId = (int) ($payment['plan_holder_id'] ?? 0);
            $planId       = (int) ($payment['plan_id'] ?? 0);
            if ($planHolderId <= 0 || $planId <= 0) {
                throw new \RuntimeException('Invalid plan or plan holder');
            }

            $planModel = new \App\Models\PlanModel();
            $plan      = $planModel->find($planId);
            if (! $plan) {
                throw new \RuntimeException('Plan not found');
            }

            // The plan the client chose keeps its own fee, program and package; defaults only when it truly has none.
            // Coverage dates are worked out by PlanLedgerService from the start date and the payments (sync below).
            $start    = $this->activationStartDate($paymentId);
            $fee      = (float) ($plan['monthly_fee'] ?? 0);
            $package  = $this->resolvePackageAndVersion($fee);
            $planFields = $db->getFieldNames('plans');
            $update = [
                'package_id'                    => (int) ($plan['package_id'] ?? 0) > 0 ? (int) $plan['package_id'] : $package['package_id'],
                'monthly_fee'                   => $fee > 0 ? $fee : MembershipService::MONTHLY_FEE,
                'start_date'                    => $start,
                'status'                        => 'active',
                'contribution_cycle_started_at' => $start,
            ];
            if (in_array('version_id', $planFields, true) && (int) ($plan['version_id'] ?? 0) <= 0) {
                $update['version_id'] = $package['version_id'];
            }
            foreach (['overdue_months' => 0, 'membership_state' => 'active', 'legacy_remaining_balance' => 0] as $col => $val) {
                if (in_array($col, $planFields, true)) {
                    $update[$col] = $val;
                }
            }
            $planModel->update($planId, $update);

            (new CycleService())->afterPaymentVerified($paymentId, $planId);
            (new PlanLedgerService())->sync($planId);
            $this->alignInitialCoverage($paymentId, $start);

            try {
                (new MembershipService())->enforceOneActivePlan($planHolderId);
            } catch (\Throwable $e) {
                log_message('error', 'ApprovalService: enforceOneActivePlan failed for plan_holder_id=' . $planHolderId . ' (' . get_class($e) . ')');
            }

            // PlanHolderModel hands out the ID Control No. when the status becomes 'active'; assign() is idempotent.
            (new \App\Models\PlanHolderModel())->update($planHolderId, ['status' => 'active']);
            (new IdControlService())->assign($planHolderId);

            db_connect()->table('users')
                ->where('user_id', (int) ($payment['user_id'] ?? 0))
                ->update(['is_plan_holder' => 1, 'account_status' => 'verified']);

            $planName = self::planName($plan);
            try {
                (new NotificationService())->notify(
                    (int) ($payment['user_id'] ?? 0),
                    'Your registration has been approved. Your ' . $planName . ' plan is now active.',
                    'registration_approved'
                );
            } catch (\Throwable $e) {
                log_message('error', 'ApprovalService: notification failed for plan_holder_id=' . $planHolderId . ' (' . get_class($e) . ')');
            }

            try {
                (new ActivityLogService())->log(
                    (int) session('user_id'),
                    'approved',
                    'payment',
                    $paymentId,
                    'Approved initial payment and activated plan holder',
                    ['status' => 'inactive'],
                    ['status' => 'active']
                );
            } catch (\Throwable $e) {
                log_message('error', 'ApprovalService: activity log failed for plan_holder_id=' . $planHolderId . ' (' . get_class($e) . ')');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to approve payment');
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'ApprovalService::approveInitialPayment failed for payment_id=' . $paymentId . ' (' . get_class($e) . ': ' . $e->getMessage() . ')');

            return false;
        }
    }

    /**
     * "Verify" / "Approve" on a registration payment: check it, mark it paid and activate the plan holder, all in one transaction.
     * Used by every screen that approves a registration, so none of them can leave a verified payment with an inactive client.
     * Accepts a payment that is still 'pending' or one that is already 'paid' (older data) as long as the holder is inactive.
     *
     * @return array{ok:bool, message:string}
     */
    public function verifyAndActivateInitialPayment(int $paymentId, int $actorUserId, int $actorBranchId): array
    {
        $db = db_connect();
        $payment = $db->table('payments')->where('payment_id', $paymentId)->get(1)->getRowArray();
        if (! $payment) {
            return ['ok' => false, 'message' => 'Payment record not found.'];
        }
        $plan = $db->table('plans')->where('plan_id', (int) $payment['plan_id'])->get(1)->getRowArray();
        $holder = $plan ? $db->table('plan_holders')->where('plan_holder_id', (int) $plan['plan_holder_id'])->get(1)->getRowArray() : null;
        if (! $plan || ! $holder) {
            return ['ok' => false, 'message' => 'Plan or plan holder record not found.'];
        }
        if ($actorBranchId <= 0 || (int) ($holder['branch_id'] ?? 0) !== $actorBranchId || (int) ($payment['branch_id'] ?? 0) !== $actorBranchId) {
            return ['ok' => false, 'message' => 'You can only verify payments within your branch.'];
        }
        if (strtolower((string) ($holder['status'] ?? '')) !== 'inactive') {
            return ['ok' => false, 'message' => 'Only registration payments for inactive plan holders can be verified here.'];
        }
        $status = (string) $payment['status'];
        if (! in_array($status, ['pending', 'paid'], true)) {
            return ['ok' => false, 'message' => 'Only a pending or verified registration payment can be approved.'];
        }
        if ($status === 'pending' && (string) ($payment['payment_method'] ?? '') === 'gcash' && trim((string) ($payment['reference_number'] ?? '')) === '') {
            return ['ok' => false, 'message' => 'GCash payment cannot be verified without a reference number.'];
        }
        if ($status === 'pending' && (int) ($payment['awaiting_remittance'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'This payment was collected by a collector; it is confirmed with the remittance.'];
        }

        $db->transBegin();
        try {
            if ($status === 'pending') {
                $db->table('payments')->where('payment_id', $paymentId)->update([
                    'status'      => 'paid',
                    'verified_by' => $actorUserId,
                    'verified_at' => date('Y-m-d H:i:s'),
                    'received_by' => (int) ($payment['received_by'] ?? 0) > 0 ? (int) $payment['received_by'] : $actorUserId,
                    'remarks'     => 'Registration payment verified by branch admin',
                ]);
            }
            if (! $this->approveInitialPayment($paymentId)) {
                throw new \RuntimeException('Activation failed');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'The approval could not be completed. Nothing was changed - please try again.'];
        }

        return ['ok' => true, 'message' => 'Payment verified and the plan holder is now active.'];
    }

    /**
     * The client's own plan name, e.g. "Damayan 2 plan" - never a fixed program name. Used in every message that names the plan.
     */
    public static function planName(array $plan): string
    {
        $name = trim((string) (MembershipService::programForPlan($plan)['name'] ?? ''));

        return $name !== '' ? $name : 'membership';
    }

    /** The day the first payment was made (never later than today): where the plan's contributions start counting from. */
    private function activationStartDate(int $paymentId): string
    {
        $today = date('Y-m-d');
        $row   = db_connect()->table('payments')->select('payment_date')->where('payment_id', $paymentId)->get()->getRowArray();
        $date  = $row ? date('Y-m-d', strtotime((string) $row['payment_date'])) : $today;

        return $date > $today ? $today : $date;
    }

    /** The first payment covers the first contributions, which start on the plan's start date: re-stamp its coverage from it. */
    private function alignInitialCoverage(int $paymentId, string $startDate): void
    {
        $payment = db_connect()->table('payments')->select('months_covered')->where('payment_id', $paymentId)->get()->getRowArray();
        if (! $payment) {
            return;
        }
        $months = max(1, (int) $payment['months_covered']);
        db_connect()->table('payments')->where('payment_id', $paymentId)->update([
            'coverage_start' => $startDate,
            'coverage_end'   => PlanLedgerService::addDays(PlanLedgerService::addMonths($startDate, $months), -1),
        ]);
    }

    /** @return array{package_id:int, version_id:int} the package (and its active version) used when a plan has none of its own */
    private function resolvePackageAndVersion(float $versionPrice): array
    {
        $db = db_connect();
        $package = $db->table('packages')->select('package_id')->where('is_damayan_entitlement', 1)->orderBy('package_id', 'ASC')->get()->getRowArray()
            ?? $db->table('packages')->select('package_id')->where('package_id', MembershipService::DEFAULT_PACKAGE_ID)->get()->getRowArray()
            ?? $db->table('packages')->select('package_id')->orderBy('package_id', 'ASC')->get()->getRowArray();
        if (! $package) {
            throw new \RuntimeException('No package is configured yet.');
        }
        $packageId = (int) $package['package_id'];
        $version = $db->table('package_versions')->select('version_id')->where('package_id', $packageId)->where('status', 'active')->orderBy('version_id', 'DESC')->get()->getRowArray()
            ?? $db->table('package_versions')->select('version_id')->where('package_id', $packageId)->orderBy('version_id', 'DESC')->get()->getRowArray();
        if (! $version) {
            $db->table('package_versions')->insert([
                'package_id'     => $packageId,
                'price'          => $versionPrice > 0 ? $versionPrice : MembershipService::MONTHLY_FEE,
                'effective_date' => date('Y-m-d'),
                'status'         => 'active',
            ]);
            $version = ['version_id' => (int) $db->insertID()];
        }

        return ['package_id' => $packageId, 'version_id' => (int) $version['version_id']];
    }

    /**
     * Reject initial payment
     * 
     * @param int $paymentId
     * @param string $reason
     * @return bool
     */
    public function rejectInitialPayment(int $paymentId, string $reason = ''): bool
    {
        if ($paymentId <= 0) {
            return false;
        }

        $db = db_connect();
        $db->transBegin();

        try {
            db_connect()->table('payments')
                ->where('payment_id', $paymentId)
                ->update(['status' => 'cancelled', 'remarks' => $reason]);

            // A cancelled payment keeps no allocation slices (ClaimAvailService recomputes the plan).
            $cancelledPlanId = (int) (db_connect()->table('payments')->select('plan_id')->where('payment_id', $paymentId)->get(1)->getRowArray()['plan_id'] ?? 0);
            (new \App\Services\ClaimAvailService())->onPaymentReversed($paymentId, $cancelledPlanId);

            // Log the activity
            helper('activity_log');
            log_activity((int) session('user_id'), 'rejected', 'payment', $paymentId, 'Rejected payment: ' . $reason);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to reject payment');
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }

    /**
     * Approve service application
     * 
     * @param int $applicationId
     * @return bool
     */
    public function approveServiceApplication(int $applicationId): bool
    {
        if ($applicationId <= 0) {
            return false;
        }

        $application = db_connect()->table('service_applications')
            ->where('application_id', $applicationId)
            ->get()
            ->getRowArray();

        if (! $application || (string) ($application['status'] ?? '') !== 'pending') {
            return false;
        }

        $db = db_connect();
        $db->transBegin();

        try {
            db_connect()->table('service_applications')
                ->where('application_id', $applicationId)
                ->update(['status' => 'approved']);

            // Log the activity
            helper('activity_log');
            log_activity((int) session('user_id'), 'approved', 'service_application', $applicationId, 'Approved service application');

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to approve service application');
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }

    /**
     * Approve pending package/service from branch admin
     * 
     * @param string $type 'package' or 'service'
     * @param int $itemId
     * @return bool
     */
    public function approveServiceOffer(string $type, int $itemId): bool
    {
        if ($itemId <= 0 || !in_array($type, ['package', 'service'], true)) {
            return false;
        }

        $table = $type === 'package' ? 'packages' : 'service_list';

        $db = db_connect();
        $db->transBegin();

        try {
            db_connect()->table($table)
                ->where($type === 'package' ? 'package_id' : 'service_list_id', $itemId)
                ->update(['status' => 'approved']);

            // Log the activity
            helper('activity_log');
            log_activity((int) session('user_id'), 'approved', $type, $itemId, 'Approved pending ' . $type);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to approve ' . $type);
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }

    /**
     * Reject pending package/service from branch admin
     * 
     * @param string $type 'package' or 'service'
     * @param int $itemId
     * @param string $reason
     * @return bool
     */
    public function rejectServiceOffer(string $type, int $itemId, string $reason = ''): bool
    {
        if ($itemId <= 0 || !in_array($type, ['package', 'service'], true)) {
            return false;
        }

        $table = $type === 'package' ? 'packages' : 'service_list';

        $db = db_connect();
        $db->transBegin();

        try {
            db_connect()->table($table)
                ->where($type === 'package' ? 'package_id' : 'service_list_id', $itemId)
                ->update(['status' => 'rejected']);

            // Log the activity
            helper('activity_log');
            log_activity((int) session('user_id'), 'rejected', $type, $itemId, 'Rejected pending ' . $type . ': ' . $reason);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to reject ' . $type);
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }
}
