<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\PlanModel;
use App\Models\ServiceApplicationModel;
use App\Models\ServiceModel;

/**
 * Panel brief, section 5: "Claim Availed Plan" - a plan holder applies to
 * avail their package's (or an individual service's) benefit, and the claim
 * is processed by staff or an Encoder, never self-approved by the plan
 * holder. The submission side of this already existed as "service
 * applications" (Client\ClientServiceController::submitServiceApplication /
 * submitPackageApplication, always inserted with status='pending' - no
 * self-approval path exists). What was missing was staff-level approval:
 * only Branch Admin could approve/reject (BranchAdmin\
 * ServiceApplicationController), matching section 6's "Encoder/Staff...
 * processes claims" only partially.
 *
 * This service holds the approve/reject logic shared by both roles, pulled
 * out of BranchAdmin\ServiceApplicationController so Staff's copy isn't a
 * second, drifting implementation of the same transaction.
 */
class ClaimService
{
    private ServiceApplicationModel $serviceApplicationModel;
    private ServiceModel $serviceModel;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->serviceApplicationModel = new ServiceApplicationModel();
        $this->serviceModel = new ServiceModel();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Claims (service_applications) for a branch, with the plan holder's
     * name and a single "what's being claimed" label that falls back to the
     * individual service name when the claim isn't for a whole package.
     */
    /**
     * @param string $search optional search-box text: plan holder name, plan
     *                       number, or the package / service being claimed
     */
    public function getBranchClaims(int $branchId, string $search = ''): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $builder = db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.service_list_id, sa.status, sa.request_type, sa.rejection_reason, sa.created_at, ph.unique_identifier, u.first_name, u.last_name, p.package_name, sl.service_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('ph.branch_id', $branchId);

        cs_apply_search($builder, $search, ['u.first_name', 'u.last_name', 'ph.unique_identifier', 'p.package_name', 'sl.service_name']);

        $rows = $builder
            ->orderBy('sa.created_at', 'DESC')
            ->orderBy('sa.application_id', 'DESC')
            ->get()
            ->getResultArray();

        // Claim (the plan's own package) or avail (any other package), for the label and for which
        // buttons the list may show: an avail needs the Branch Admin's review page.
        $planPackage = [];
        $holderIds = array_values(array_unique(array_map(static fn (array $r): int => (int) $r['plan_holder_id'], $rows)));
        if ($holderIds !== []) {
            foreach (db_connect()->table('plans')->select('plan_holder_id, package_id')->whereIn('plan_holder_id', $holderIds)->where('status', 'active')->orderBy('plan_id', 'ASC')->get()->getResultArray() as $pl) {
                $planPackage[(int) $pl['plan_holder_id']] = (int) $pl['package_id'];
            }
        }

        foreach ($rows as &$row) {
            $row['claim_label'] = (string) ($row['package_name'] ?? $row['service_name'] ?? '-');
            if ((int) ($row['package_id'] ?? 0) <= 0) {
                $row['request_kind'] = 'service';
            } elseif (! empty($row['request_type'])) {
                $row['request_kind'] = (string) $row['request_type'];
            } else {
                $own = $planPackage[(int) $row['plan_holder_id']] ?? 0;
                $row['request_kind'] = $own > 0 && $own !== (int) $row['package_id'] ? 'avail' : 'claim';
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    /**
     * A note on a request, for the Branch Admin to see (Staff cannot decide an avail, so they leave
     * a note instead). Internal: never shown to the client.
     *
     * @return array{ok: bool, message: string}
     */
    public function addNote(int $applicationId, int $branchId, int $userId, string $note): array
    {
        $note = trim($note);
        if (mb_strlen($note) < 3) {
            return ['ok' => false, 'message' => 'Write the note first.'];
        }
        if (mb_strlen($note) > 1000) {
            return ['ok' => false, 'message' => 'Keep the note under 1,000 characters.'];
        }
        if ($this->findClaimForBranch($applicationId, $branchId) === null) {
            return ['ok' => false, 'message' => 'Claim not found.'];
        }

        db_connect()->table('service_application_notes')->insert(['application_id' => $applicationId, 'user_id' => $userId, 'note' => $note]);

        return ['ok' => true, 'message' => 'Note added for the Branch Admin.'];
    }

    /** @return list<array<string,mixed>> */
    public function notesFor(int $applicationId): array
    {
        if (! db_connect()->tableExists('service_application_notes')) {
            return [];
        }

        return db_connect()->table('service_application_notes n')
            ->select('n.note, n.created_at, u.first_name, u.last_name, r.role_name')
            ->join('users u', 'u.user_id = n.user_id', 'left')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->where('n.application_id', $applicationId)
            ->orderBy('n.note_id', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Approves a pending request. The rules (claim vs avail, who may approve what, the quote,
     * the down payment, the link to its service) live in ClaimAvailService; this stays as the
     * branch-scoped entry point the two controllers call.
     *
     * @param array{term?:int|string, waive?:bool, reason?:string} $options term / waiver for an avail
     *
     * @return array{ok: bool, message: string}
     */
    public function approve(int $applicationId, int $branchId, int $processedByUserId, int $roleId = 2, array $options = []): array
    {
        return (new ClaimAvailService())->approve($applicationId, $branchId, $processedByUserId, $roleId, $options);
    }

    /**
     * Rejects a pending request; the reason is required and shown to the client.
     *
     * @return array{ok: bool, message: string}
     */
    public function reject(int $applicationId, int $branchId, int $processedByUserId, string $reason = '', int $roleId = 2): array
    {
        return (new ClaimAvailService())->reject($applicationId, $branchId, $processedByUserId, $roleId, $reason);
    }
    /**
     * Full detail for a single claim (for a review/detail page), scoped to
     * the reviewer's branch.
     */
    public function getClaimDetail(int $applicationId, int $branchId): ?array
    {
        if ($applicationId <= 0 || $branchId <= 0) {
            return null;
        }

        $request = db_connect()->table('service_applications sa')
            ->select('sa.*, ph.branch_id, ph.user_id, u.first_name, u.last_name, p.base_price AS package_price, p.package_name, sl.base_price AS service_price, sl.service_name, fb.first_name AS filer_first, fb.last_name AS filer_last, fr.role_name AS filer_role')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('users fb', 'fb.user_id = sa.filed_by_user_id', 'left')
            ->join('roles fr', 'fr.role_id = fb.role_id', 'left')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('sa.application_id', $applicationId)
            ->get()
            ->getRowArray();

        if (! $request || (int) ($request['branch_id'] ?? 0) !== $branchId) {
            return null;
        }

        return $request;
    }

    public function getClaimDocuments(int $applicationId): array
    {
        if ($applicationId <= 0) {
            return [];
        }

        return db_connect()->table('service_application_documents')
            ->where('application_id', $applicationId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * A single claim document, scoped to the reviewer's branch - or null if
     * it doesn't exist or belongs to a different branch.
     */
    public function getClaimDocumentForBranch(int $documentId, int $branchId): ?array
    {
        if ($documentId <= 0 || $branchId <= 0) {
            return null;
        }

        $document = db_connect()->table('service_application_documents')
            ->where('document_id', $documentId)
            ->get()
            ->getRowArray();

        if (! $document) {
            return null;
        }

        $application = db_connect()->table('service_applications')
            ->select('plan_holder_id')
            ->where('application_id', (int) $document['application_id'])
            ->get()
            ->getRowArray();

        if (! $application) {
            return null;
        }

        $planHolder = db_connect()->table('plan_holders')
            ->select('branch_id')
            ->where('plan_holder_id', (int) $application['plan_holder_id'])
            ->get()
            ->getRowArray();

        if (! $planHolder || (int) ($planHolder['branch_id'] ?? 0) !== $branchId) {
            return null;
        }

        return $document;
    }

    /**
     * Entitlement cycle model (replaces the old
     * resetContributionCycleIfEntitlementClaim(), which zeroed
     * months_paid the instant ANY entitlement claim was approved - even
     * one approved before the plan holder had finished paying off the
     * current plan-price cycle, silently destroying that progress). Every
     * approved claim - entitlement or not - is stamped with the cycle it
     * was approved in, for a consistent audit trail alongside
     * payments.cycle_number. Only an entitlement-package (Regular Wood
     * Casket) claim can actually progress a cycle, so
     * CycleService::closeCycleIfSettled() is only checked for that case -
     * it's a no-op unless this claim is also the thing that completes an
     * already-fully-paid cycle, and never touches months_paid directly
     * itself.
     */
    private function stampClaimCycle(int $applicationId, int $packageId, int $planHolderId): void
    {
        if ($planHolderId <= 0) {
            return;
        }

        $planModel = new PlanModel();
        $activePlan = $planModel
            ->where('plan_holder_id', $planHolderId)
            ->where('status', 'active')
            ->orderBy('plan_id', 'DESC')
            ->first();

        if (! $activePlan) {
            return;
        }

        $planId = (int) $activePlan['plan_id'];
        $cycleNumber = (int) ($activePlan['current_cycle_number'] ?? 1);

        if ($applicationId > 0) {
            db_connect()->table('service_applications')
                ->where('application_id', $applicationId)
                ->update(['cycle_number' => $cycleNumber]);
        }

        if ($packageId <= 0) {
            return;
        }

        $package = db_connect()->table('packages')
            ->select('is_damayan_entitlement')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package || (int) ($package['is_damayan_entitlement'] ?? 0) !== 1) {
            return;
        }

        $planModel->update($planId, [
            'last_damayan_claim_at' => date('Y-m-d H:i:s'),
        ]);

        (new CycleService())->closeCycleIfSettled($planId);
    }

    private function findClaimForBranch(int $applicationId, int $branchId): ?array
    {
        if ($applicationId <= 0 || $branchId <= 0) {
            return null;
        }

        $request = db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.service_list_id, sa.status, ph.branch_id, ph.user_id, p.base_price AS package_price, p.package_name, sl.base_price AS service_price, sl.service_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('sa.application_id', $applicationId)
            ->get()
            ->getRowArray();

        if (! $request || (int) ($request['branch_id'] ?? 0) !== $branchId) {
            return null;
        }

        return $request;
    }
}
