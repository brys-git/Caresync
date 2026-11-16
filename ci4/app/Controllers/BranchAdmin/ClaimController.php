<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ActivityLogService;
use App\Services\ClaimSubmissionService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Branch Admin files the casket claim FOR a plan holder of their own branch
 * (the family often comes to the branch instead of using the website).
 * The rules and the saving are ClaimSubmissionService - the same code the
 * plan holder's own claim form uses.
 */
class ClaimController extends BaseController
{
    private ClaimSubmissionService $claims;

    public function __construct()
    {
        $this->claims = new ClaimSubmissionService();
    }

    /** Step 1: pick the plan holder (own branch only). */
    public function pick()
    {
        $branchId = (int) session('branch_id');
        $search = cs_search_term($this->request->getGet('search'));

        $builder = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, u.contact_number')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('ph.status', 'active')
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->limit(50);

        cs_apply_search($builder, $search, ['u.first_name', 'u.last_name', 'u.contact_number', 'ph.unique_identifier']);

        return view('branch_admin/service_package/file_claim_pick', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'File a claim for a plan holder',
            'page_sub' => 'Choose the plan holder the claim is for.',
            'holders' => $builder->get()->getResultArray(),
            'search' => $search,
        ]);
    }

    /** Step 2: the claim form for one plan holder. */
    public function form(int $planHolderId)
    {
        $holder = $this->holderOr404($planHolderId);
        $package = $this->claims->entitlementPackage();

        if ($package === null) {
            return redirect()->to('/branch-admin/service-package/requests')->with('error', 'The casket package is not set up yet.');
        }

        [$canApply, $membership, $planId] = $this->claims->eligibility($planHolderId);
        $eligibility = $this->claims->entitlementClaimStatus($canApply, $membership, $planId);
        $existing = $this->claims->openClaim($planHolderId, $planId);

        $db = db_connect();
        $branch = $db->table('branches')->select('branch_name, contact_number')->where('branch_id', (int) $holder['branch_id'])->get()->getRowArray();
        $firstBeneficiary = $db->table('beneficiaries')->where('plan_holder_id', $planHolderId)->orderBy('is_primary', 'DESC')->orderBy('beneficiary_id', 'ASC')->get()->getRowArray();

        $membershipService = new \App\Services\MembershipService();
        $activePlan = $membershipService->getActivePlan($planHolderId);

        return view('branch_admin/service_package/file_claim', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'File a claim for a plan holder',
            'page_sub' => 'You are filing this claim on behalf of the plan holder.',
            'holder' => $holder,
            'package' => $package,
            'eligibility' => $eligibility,
            'existing_claim' => $existing,
            'benefit_credit' => (float) ($package['base_price'] ?? 0),
            'attire_price' => ClaimSubmissionService::BURIAL_ATTIRE_PRICE,
            'plan_name' => $membershipService->resolvePackageName((int) ($activePlan['package_id'] ?? 0)),
            'member_name' => trim(implode(' ', array_filter([$holder['first_name'], $holder['middle_name'], $holder['last_name']]))),
            'member_address' => cs_format_address($holder),
            'beneficiary_prefill' => $firstBeneficiary
                ? trim(implode(' ', array_filter([$firstBeneficiary['first_name'] ?? '', $firstBeneficiary['middle_name'] ?? '', $firstBeneficiary['last_name'] ?? '', $firstBeneficiary['name_extension'] ?? ''])))
                : '',
            'branch_name' => (string) ($branch['branch_name'] ?? ''),
            'branch_phone' => (string) ($branch['contact_number'] ?? ''),
            'form_errors' => session()->getFlashdata('form_errors') ?? [],
        ]);
    }

    /** Step 3: save it. */
    public function store(int $planHolderId)
    {
        $holder = $this->holderOr404($planHolderId);
        $package = $this->claims->entitlementPackage();

        if ($package === null) {
            return redirect()->to('/branch-admin/service-package/requests')->with('error', 'The casket package is not set up yet.');
        }

        $result = $this->claims->submit(
            $planHolderId,
            (int) $package['package_id'],
            $this->request->getPost(),
            $this->request->getFiles() ?? [],
            (int) session('user_id'),
            [
                'on_behalf' => true,
                'actor_name' => trim((string) session('first_name') . ' ' . (string) session('last_name')),
                'actor_role' => 'Branch Admin',
            ]
        );

        if (! $result['ok']) {
            if (! empty($result['errors'])) {
                return redirect()->back()->withInput()->with('form_errors', $result['errors']);
            }

            return redirect()->back()->withInput()->with('error', (string) ($result['error'] ?? 'Unable to file the claim right now.'));
        }

        (new ActivityLogService())->log((int) session('user_id'), 'created', 'service_application', (int) $result['application_id'], 'Filed a casket claim for plan holder #' . (int) $holder['plan_holder_id']);

        return redirect()->to('/branch-admin/service-package/requests/' . (int) $result['application_id'])
            ->with('success', 'The claim was filed for the plan holder. They have been notified.');
    }

    /** A plan holder of THIS branch, or "not found". */
    private function holderOr404(int $planHolderId): array
    {
        $holder = db_connect()->table('plan_holders ph')
            ->select('ph.*, u.first_name, u.middle_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.plan_holder_id', $planHolderId)
            ->where('ph.branch_id', (int) session('branch_id'))
            ->get()
            ->getRowArray();

        if (! $holder) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $holder;
    }
}
