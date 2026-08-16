<?php

namespace App\Services;

use App\Models\PlanHolderModel;
use App\Models\PlanModel;
use App\Models\ServiceModel;
use App\Services\MembershipService;

/**
 * DamayanService
 *
 * Encapsulates all Damayan Burial Program business logic:
 * - Eligibility verification (qualified active member)
 * - Benefit activation upon qualified member death
 * - Contribution waiver (remaining balance waived on death)
 * - Benefit credit application on funeral package selection
 * - Upgrade amount calculation for higher-priced packages
 *
 * CRITICAL BUSINESS RULES (from requirements):
 * 1. Damayan benefit value = PHP 14,500 (FIXED, never calculated from paid amount)
 * 2. Standard Damayan package value = PHP 20,000 (the entitlement, family pays PHP 0)
 * 3. Member's contribution history and Damayan benefit are SEPARATE values
 * 4. Remaining unpaid contributions are WAIVED on qualified death (family pays PHP 0)
 * 5. For higher-priced packages: PHP 14,500 benefit credit is deducted from package price
 * 6. Non-Damayan members: no benefit, no credit, no waiver, full price applies
 */
class DamayanService
{
    public const BENEFIT_VALUE = 14500.0;
    public const STANDARD_PACKAGE_VALUE = 20000.0;
    public const MINIMUM_QUALIFYING_MONTHS = 2;

    private PlanHolderModel $planHolderModel;
    private PlanModel $planModel;
    private ServiceModel $serviceModel;
    private MembershipService $membershipService;

    public function __construct()
    {
        $this->planHolderModel = new PlanHolderModel();
        $this->planModel = new PlanModel();
        $this->serviceModel = new ServiceModel();
        $this->membershipService = new MembershipService();
    }

    /**
     * Determine if a plan holder is a qualified Damayan member.
     *
     * A qualified Damayan member:
     * - Has an active plan (is_plan_holder=1, plans.status='active')
     * - Membership state is 'active' (not delinquent/suspended)
     * - Has paid at least MINIMUM_QUALIFYING_MONTHS (2) monthly contributions
     * - Is NOT already marked deceased
     *
     * @param int $planHolderId
     * @return bool
     */
    public function isQualifiedMember(int $planHolderId): bool
    {
        if ($planHolderId <= 0) {
            return false;
        }

        $holder = $this->planHolderModel->find($planHolderId);
        if (! $holder) {
            return false;
        }

        // Deceased members are no longer "qualifying" — they're now beneficiaries
        if ((int) ($holder['is_deceased'] ?? 0) === 1) {
            return false;
        }

        $plan = $this->membershipService->getActivePlan($planHolderId);
        if (! $plan) {
            return false;
        }

        $state = strtolower((string) ($plan['membership_state'] ?? 'inactive'));
        if ($state !== 'active') {
            return false;
        }

        $monthsPaid = (int) ($plan['months_paid'] ?? 0);
        if ($monthsPaid < self::MINIMUM_QUALIFYING_MONTHS) {
            return false;
        }

        return true;
    }

    /**
     * Get current membership/contribution summary for a plan holder.
     *
     * Returns SEPARATE values:
     * - monthly_fee, months_paid, amount_paid, remaining_balance (contribution tracking)
     * - damayan_benefit_value (PHP 14,500 fixed — separate from contributions)
     * - standard_package_value (PHP 20,000 fixed — separate from benefit)
     *
     * @param int $planHolderId
     * @return array|null
     */
    public function getMembershipContributionSummary(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        $plan = $this->membershipService->getActivePlan($planHolderId);
        if (! $plan) {
            return null;
        }

        $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
        $monthsPaid = (int) ($plan['months_paid'] ?? 0);
        $amountPaid = round($monthlyFee * $monthsPaid, 2);
        $remainingBalance = (float) ($plan['remaining_balance'] ?? 0);

        return [
            'plan_id' => (int) ($plan['plan_id'] ?? 0),
            'monthly_fee' => $monthlyFee,
            'months_paid' => $monthsPaid,
            'amount_paid' => $amountPaid,
            'remaining_balance' => $remainingBalance,
            'damayan_benefit_value' => self::BENEFIT_VALUE,
            'standard_package_value' => self::STANDARD_PACKAGE_VALUE,
            'is_qualified' => $this->isQualifiedMember($planHolderId),
        ];
    }

    /**
     * Activate Damayan benefits upon qualified member death.
     *
     * This:
     * 1. Marks the plan holder as deceased
     * 2. Records date_of_death
     * 3. Activates Damayan benefits
     * 4. WAIVES the remaining contribution balance (family pays PHP 0)
     *
     * @param int $planHolderId
     * @param string $dateOfDeath (YYYY-MM-DD)
     * @param int $processedBy (user_id)
     * @param string|null $waiverReason
     * @return array ['success' => bool, 'error' => string|null, 'waived_amount' => float]
     */
    public function activateBenefitsOnDeath(
        int $planHolderId,
        string $dateOfDeath,
        int $processedBy,
        ?string $waiverReason = null
    ): array {
        if ($planHolderId <= 0) {
            return ['success' => false, 'error' => 'Invalid plan holder ID.', 'waived_amount' => 0.0];
        }

        if (! $this->isQualifiedMember($planHolderId)) {
            return ['success' => false, 'error' => 'Plan holder is not a qualified Damayan member.', 'waived_amount' => 0.0];
        }

        $plan = $this->membershipService->getActivePlan($planHolderId);
        $remainingBalance = (float) ($plan['remaining_balance'] ?? 0);

        $db = db_connect();
        $db->transBegin();

        try {
            // Mark plan holder as deceased and activate benefits
            $this->planHolderModel->update($planHolderId, [
                'is_deceased' => 1,
                'date_of_death' => $dateOfDeath,
                'damayan_benefit_activated' => 1,
                'damayan_benefit_activation_date' => date('Y-m-d H:i:s'),
                'waived_contribution_amount' => number_format($remainingBalance, 2, '.', ''),
                'waiver_date' => date('Y-m-d'),
                'waiver_reason' => $waiverReason ?? 'Qualified Damayan member death — remaining contributions waived per KAAGAPAY policy.',
                'status' => 'inactive',
            ]);

            // Mark plan as completed (membership ends on death)
            $this->planModel->update((int) ($plan['plan_id'] ?? 0), [
                'status' => 'completed',
                'membership_state' => 'completed',
                'remaining_balance' => 0.00, // Waived
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to activate Damayan benefits.');
            }

            $db->transCommit();

            return [
                'success' => true,
                'error' => null,
                'waived_amount' => $remainingBalance,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();

            return ['success' => false, 'error' => $e->getMessage(), 'waived_amount' => 0.0];
        }
    }

    /**
     * Calculate Damayan benefit application for a selected funeral package.
     *
     * Business rules:
     * - If package price <= STANDARD_PACKAGE_VALUE (PHP 20,000): standard entitlement, family pays PHP 0
     * - If package price > STANDARD_PACKAGE_VALUE: PHP 14,500 benefit credit applied,
     *   upgrade_amount = package_price - 14,500, family pays upgrade_amount
     * - Non-Damayan: no benefit, full price applies
     *
     * @param float $packagePrice
     * @param bool $isDamayanEligible
     * @return array [
     *   'is_damayan_eligible' => bool,
     *   'damayan_benefit_credit' => float,
     *   'standard_entitlement' => bool,
     *   'upgrade_amount' => float,
     *   'final_amount_due' => float,
     * ]
     */
    public function calculateBenefitApplication(float $packagePrice, bool $isDamayanEligible): array
    {
        $packagePrice = max(0.0, $packagePrice);

        if (! $isDamayanEligible) {
            // Non-Damayan: full price, no benefit
            return [
                'is_damayan_eligible' => false,
                'damayan_benefit_credit' => 0.0,
                'standard_entitlement' => false,
                'upgrade_amount' => 0.0,
                'final_amount_due' => $packagePrice,
            ];
        }

        if ($packagePrice <= self::STANDARD_PACKAGE_VALUE) {
            // Standard Damayan entitlement — family pays PHP 0
            return [
                'is_damayan_eligible' => true,
                'damayan_benefit_credit' => 0.0, // No credit needed; standard package IS the entitlement
                'standard_entitlement' => true,
                'upgrade_amount' => 0.0,
                'final_amount_due' => 0.0,
            ];
        }

        // Higher-priced package: PHP 14,500 benefit credit applied
        $benefitCredit = self::BENEFIT_VALUE;
        $upgradeAmount = round($packagePrice - $benefitCredit, 2);
        $finalAmountDue = max(0.0, $upgradeAmount);

        return [
            'is_damayan_eligible' => true,
            'damayan_benefit_credit' => $benefitCredit,
            'standard_entitlement' => false,
            'upgrade_amount' => $upgradeAmount,
            'final_amount_due' => $finalAmountDue,
        ];
    }

    /**
     * Apply Damayan benefit to a service record.
     *
     * @param int $serviceId
     * @param float $packagePrice
     * @param bool $isDamayanEligible
     * @return bool
     */
    public function applyBenefitToService(int $serviceId, float $packagePrice, bool $isDamayanEligible): bool
    {
        if ($serviceId <= 0) {
            return false;
        }

        $calculation = $this->calculateBenefitApplication($packagePrice, $isDamayanEligible);

        return (bool) $this->serviceModel->update($serviceId, [
            'damayan_eligible' => $calculation['is_damayan_eligible'] ? 1 : 0,
            'damayan_benefit_credit' => number_format($calculation['damayan_benefit_credit'], 2, '.', ''),
            'upgrade_amount' => number_format($calculation['upgrade_amount'], 2, '.', ''),
            'final_amount_due' => number_format($calculation['final_amount_due'], 2, '.', ''),
        ]);
    }
}
