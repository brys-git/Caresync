<?php

namespace App\Services;

use App\Models\PlanModel;

/**
 * MembershipService
 * 
 * Handles all membership-related business logic.
 * Centralizes the logic for determining membership status and plan activation.
 */
class MembershipService
{
    public const PROGRAM_NAME = 'Damayan Burial Program';
    public const MONTHLY_FEE = 240.0;
    public const TOTAL_CONTRIBUTION = 14500.0;
    public const DEFAULT_PACKAGE_ID = 1;

    public static function getProgramInfo(): array
    {
        $db = db_connect();
        if ($db->tableExists('membership_programs')) {
            $program = $db->table('membership_programs')
                ->select('program_id, program_name, monthly_fee')
                ->where('is_active', 1)
                ->orderBy('program_id', 'ASC')
                ->get()
                ->getRowArray();

            if ($program) {
                return [
                    'id' => (int) ($program['program_id'] ?? 0),
                    'name' => (string) ($program['program_name'] ?? self::PROGRAM_NAME),
                    'monthly_fee' => (float) ($program['monthly_fee'] ?? self::MONTHLY_FEE),
                    'package_id' => self::DEFAULT_PACKAGE_ID,
                ];
            }
        }

        return [
            'name' => self::PROGRAM_NAME,
            'monthly_fee' => self::MONTHLY_FEE,
            'package_id' => self::DEFAULT_PACKAGE_ID,
        ];
    }

    public static function ensureDefaultPackageVersion(): void
    {
        $db = db_connect();

        if (! $db->tableExists('packages') || ! $db->tableExists('package_versions')) {
            return;
        }

        $package = $db->table('packages')
            ->select('package_id')
            ->where('package_id', self::DEFAULT_PACKAGE_ID)
            ->get()
            ->getRowArray();

        if (! $package) {
            return;
        }

        $version = $db->table('package_versions')
            ->select('version_id')
            ->where('package_id', self::DEFAULT_PACKAGE_ID)
            ->where('status', 'active')
            ->orderBy('version_id', 'DESC')
            ->get()
            ->getRowArray();

        if ($version) {
            return;
        }

        $db->table('package_versions')->insert([
            'package_id' => self::DEFAULT_PACKAGE_ID,
            'price' => self::MONTHLY_FEE,
            'effective_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
    }

    public static function ensureMembershipProgram(): void
    {
        $db = db_connect();

        if (! $db->tableExists('membership_programs')) {
            return;
        }

        $exists = $db->table('membership_programs')
            ->select('program_id')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($exists) {
            return;
        }

        $db->table('membership_programs')->insert([
            'program_name' => self::PROGRAM_NAME,
            'monthly_fee' => self::MONTHLY_FEE,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private PlanModel $planModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
    }

    /**
     * Get the active plan for a plan holder
     * 
     * A user is an active member when:
     * - is_plan_holder = 1
     * - plans.status = 'active'
     * 
     * @param int $planHolderId
     * @return array|null
     */
    public function getActivePlan(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        return $this->planModel
            ->where('plan_holder_id', $planHolderId)
            ->where('status', 'active')
            ->orderBy('plan_id', 'DESC')
            ->first();
    }

    /**
     * Get all plans for a plan holder (prioritizing active)
     * 
     * @param int $planHolderId
     * @return array
     */
    public function getPlans(int $planHolderId): array
    {
        if ($planHolderId <= 0) {
            return [];
        }

        return $this->planModel
            ->where('plan_holder_id', $planHolderId)
            ->orderBy("CASE WHEN status = 'active' THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy('plan_id', 'DESC')
            ->findAll();
    }

    /**
     * Check if a user is an active member
     * 
     * @param int $userId
     * @param int $planHolderId
     * @return bool
     */
    public function isActiveMember(int $userId, int $planHolderId): bool
    {
        if ($userId <= 0 || $planHolderId <= 0) {
            return false;
        }

        $user = db_connect()->table('users')
            ->select('is_plan_holder, status')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (! $user || (int) ($user['is_plan_holder'] ?? 0) !== 1 || (string) ($user['status'] ?? '') !== 'active') {
            return false;
        }

        $activePlan = $this->getActivePlan($planHolderId);

        return $activePlan !== null;
    }

    /**
     * Ensure only one active plan per plan holder
     * 
     * @param int $planHolderId
     * @return bool
     */
    public function enforceOneActivePlan(int $planHolderId): bool
    {
        if ($planHolderId <= 0) {
            return false;
        }

        $activePlans = $this->planModel
            ->where('plan_holder_id', $planHolderId)
            ->where('status', 'active')
            ->orderBy('plan_id', 'DESC')
            ->findAll();

        if (count($activePlans) <= 1) {
            return true; // Already compliant
        }

        // Keep only the most recent active plan
        $keepPlanId = (int) ($activePlans[0]['plan_id'] ?? 0);
        if ($keepPlanId <= 0) {
            return false;
        }

        $db = db_connect();
        $db->transBegin();

        try {
            // Deactivate all active plans
            $this->planModel
                ->where('plan_holder_id', $planHolderId)
                ->where('status', 'active')
                ->set(['status' => 'inactive'])
                ->update();

            // Reactivate only the latest one
            $this->planModel->update((int) $keepPlanId, ['status' => 'active']);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to enforce single active plan');
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }

    /**
     * Get membership details for a plan holder
     * 
     * @param int $planHolderId
     * @return array|null
     */
    public function getMembershipDetails(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        $activePlan = $this->getActivePlan($planHolderId);

        if (!$activePlan) {
            return null;
        }

        return [
            'plan_id' => (int) ($activePlan['plan_id'] ?? 0),
            'status' => (string) ($activePlan['status'] ?? 'inactive'),
            'monthly_fee' => (float) ($activePlan['monthly_fee'] ?? 0),
            'months_paid' => (int) ($activePlan['months_paid'] ?? 0),
            'remaining_balance' => (float) ($activePlan['remaining_balance'] ?? 0),
            'start_date' => (string) ($activePlan['start_date'] ?? ''),
            'package_id' => (int) ($activePlan['package_id'] ?? 0),
            'program_name' => self::PROGRAM_NAME,
        ];
    }

    /**
     * Get plans for a branch, prioritizing active records.
     *
     * @param int $branchId
     * @return array
     */
    public function getBranchPlans(int $branchId): array
    {
        $builder = $this->planModel
            ->select('plans.plan_id, plans.monthly_fee, plans.remaining_balance, plans.months_paid, plans.status AS plan_status, plans.start_date, ph.branch_id, ph.unique_identifier, u.first_name, u.last_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = plans.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->orderBy("CASE WHEN plans.status = 'active' THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy('plans.plan_id', 'DESC');

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        return $builder->findAll();
    }

    /**
     * Apply membership coverage from a payment
     * 
     * Called when a payment is approved (initial or advance).
     * Extends the payment_coverage_until date and updates next_due_date.
     * 
     * CORRECTED LOGIC (Phase requirement):
     * next_due_date = payment_coverage_until + 1 day (not +1 month)
     * This ensures the next due date is immediately after coverage ends.
     * 
     * @param int $planId
     * @param int $monthsCovered
     * @return bool
     */
    public function applyMembershipCoverage(int $planId, int $monthsCovered): bool
    {
        if ($planId <= 0 || $monthsCovered <= 0) {
            return false;
        }

        $plan = $this->planModel->find($planId);
        if (!$plan) {
            return false;
        }

        $baseDate = (string) ($plan['payment_coverage_until'] ?? $plan['start_date'] ?? date('Y-m-d'));
        if ($baseDate === '') {
            $baseDate = date('Y-m-d');
        }

        $newCoverageDate = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($baseDate)));
        // CORRECTED: next_due_date = payment_coverage_until + 1 day
        $nextDueDate = date('Y-m-d', strtotime('+1 day', strtotime($newCoverageDate)));

        try {
            $db = db_connect();

            // Prepare update payload but only include fields that exist in the plans table
            $updateData = [
                'overdue_months' => 0,
                'membership_state' => 'active',
                'months_paid' => max(0, (int) ($plan['months_paid'] ?? 0)) + max(1, $monthsCovered),
            ];

            if ($db->fieldExists('payment_coverage_until', 'plans')) {
                $updateData['payment_coverage_until'] = $newCoverageDate;
            }

            if ($db->fieldExists('next_due_date', 'plans')) {
                $updateData['next_due_date'] = $nextDueDate;
            }

            $this->planModel->update($planId, $updateData);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if a member can access services
     * 
     * Service eligibility requires:
     * - Plan status = active
     * - Membership state = active (not delinquent or suspended)
     * - Overdue months <= 2
     * 
     * PHASE REQUIREMENT: Enhanced delinquency validation
     * Service access rules:
     * - membership_state must NOT be 'delinquent' or 'suspended'
     * - overdue_months must be <= 2 (grace period allowed)
     * - 3+ months overdue = automatic delinquency, no service access
     * 
     * @param int $planHolderId
     * @return bool
     */
    public function canAccessServices(int $planHolderId): bool
    {
        if ($planHolderId <= 0) {
            return false;
        }

        $activePlan = $this->getActivePlan($planHolderId);
        if (!$activePlan) {
            return false;
        }

        $state = strtolower((string) ($activePlan['membership_state'] ?? 'active'));
        $overdueMonths = (int) ($activePlan['overdue_months'] ?? 0);

        // CORRECTED: Reject if delinquent or suspended
        if ($state === 'delinquent' || $state === 'suspended') {
            return false;
        }

        // CORRECTED: Grace period is 0-2 months, 3+ months = delinquent
        return $overdueMonths <= 2;
    }

    /**
     * Update membership states based on payment coverage
     * 
     * This method should run daily (via cron or during login).
     * Checks each active plan and updates membership_state based on delinquency.
     * 
     * Rules:
     * - overdue 0-2 months: active
     * - overdue 3-5 months: delinquent
     * - overdue 6+ months: suspended
     * 
     * @return array status counts
     */
    public function updateMembershipStates(): array
    {
        $counts = ['active' => 0, 'delinquent' => 0, 'suspended' => 0, 'updated' => 0];

        $db = db_connect();
        $today = date('Y-m-d');

        try {
            $activePlans = $db->table('plans')
                ->select('plan_id, payment_coverage_until, overdue_months, membership_state')
                ->where('status', 'active')
                ->get()
                ->getResultArray();

            foreach ($activePlans as $plan) {
                $planId = (int) ($plan['plan_id'] ?? 0);
                if ($planId <= 0) {
                    continue;
                }

                $coverageUntil = (string) ($plan['payment_coverage_until'] ?? '');
                if ($coverageUntil === '') {
                    continue;
                }

                // Calculate overdue months
                $overdueMonths = $this->calculateOverdueMonths($coverageUntil, $today);

                // Determine new state
                $newState = 'active';
                if ($overdueMonths >= 6) {
                    $newState = 'suspended';
                } elseif ($overdueMonths >= 3) {
                    $newState = 'delinquent';
                }

                // Update if state changed or overdue months changed
                $oldOverdue = (int) ($plan['overdue_months'] ?? 0);
                $oldState = strtolower((string) ($plan['membership_state'] ?? 'active'));

                if ($newState !== $oldState || $oldOverdue !== $overdueMonths) {
                    $this->planModel->update($planId, [
                        'overdue_months' => $overdueMonths,
                        'membership_state' => $newState,
                    ]);

                    $counts['updated']++;
                }

                $counts[$newState]++;
            }

            return $counts;
        } catch (\Throwable $e) {
            return $counts;
        }
    }

    /**
     * Calculate months overdue
     * 
     * @param string $coverageUntilDate
     * @param string $currentDate
     * @return int
     */
    private function calculateOverdueMonths(string $coverageUntilDate, string $currentDate): int
    {
        try {
            $coverage = new \DateTime($coverageUntilDate);
            $today = new \DateTime($currentDate);

            if ($today <= $coverage) {
                return 0;
            }

            $interval = $coverage->diff($today);
            $months = ($interval->y * 12) + $interval->m;

            return max(0, $months);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get enhanced membership summary for display
     * 
     * @param int $planHolderId
     * @return array|null
     */
    public function getMembershipSummary(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        $activePlan = $this->getActivePlan($planHolderId);
        if (!$activePlan) {
            return null;
        }

        return [
            'plan_id' => (int) ($activePlan['plan_id'] ?? 0),
            'status' => (string) ($activePlan['status'] ?? 'inactive'),
            'membership_state' => (string) ($activePlan['membership_state'] ?? 'active'),
            'monthly_fee' => (float) ($activePlan['monthly_fee'] ?? 0),
            'total_plan_amount' => (float) ($activePlan['total_plan_amount'] ?? self::TOTAL_CONTRIBUTION),
            'months_paid' => (int) ($activePlan['months_paid'] ?? 0),
            'start_date' => (string) ($activePlan['start_date'] ?? ''),
            'next_due_date' => (string) ($activePlan['next_due_date'] ?? ''),
            'payment_coverage_until' => (string) ($activePlan['payment_coverage_until'] ?? ''),
            'overdue_months' => (int) ($activePlan['overdue_months'] ?? 0),
            'package_id' => (int) ($activePlan['package_id'] ?? 0),
            'program_name' => self::PROGRAM_NAME,
            'can_access_services' => $this->canAccessServices($planHolderId),
        ];
    }
}
