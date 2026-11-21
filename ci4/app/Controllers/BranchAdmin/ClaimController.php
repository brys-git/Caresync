<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ActivityLogService;
use App\Services\ClaimAvailService;
use App\Services\ClaimSubmissionService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Branch Admin (and, through Staff\ClaimController, Staff) files the claim FOR a plan holder of their
 * own branch: the family comes to the branch instead of using the website. The claimant is the plan
 * holder or one of the plan holder's REGISTERED beneficiaries, picked from the list (beneficiaries have
 * no account, so this is how they claim). The rules and the saving are ClaimSubmissionService /
 * ClaimAvailService - the same code the plan holder's own form uses, so the eligibility, the quote and
 * the amounts are identical.
 */
class ClaimController extends BaseController
{
    protected ClaimSubmissionService $claims;

    public function __construct()
    {
        $this->claims = new ClaimSubmissionService();
    }

    /* ---- what differs between the two roles ---- */
    protected function roleId(): int { return 2; }
    protected function roleLabel(): string { return 'Branch Admin'; }
    protected function layout(): string { return 'layouts/branch_admin'; }
    protected function fileClaimPath(): string { return 'branch-admin/service-package/file-claim'; }
    protected function requestsPath(): string { return 'branch-admin/service-package/requests'; }
    protected function holderPath(): string { return 'branch-admin/client-management/view'; }

    private function guard(): void
    {
        if ((int) session('role_id') !== $this->roleId()) {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }

    /** Step 1: pick the plan holder (own branch only). */
    public function pick()
    {
        $this->guard();
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
            'role_layout' => $this->layout(),
            'page_title' => 'File a claim for a plan holder',
            'page_sub' => 'Choose the plan holder the claim is for.',
            'holders' => $builder->get()->getResultArray(),
            'search' => $search,
            'file_claim_path' => $this->fileClaimPath(),
        ]);
    }

    /** Step 2: the claim form for one plan holder. */
    public function form(int $planHolderId)
    {
        $this->guard();
        $holder = $this->holderOr404($planHolderId);
        $package = $this->claims->entitlementPackage($planHolderId);

        if ($package === null) {
            return redirect()->to('/' . $this->requestsPath())->with('error', 'The casket package is not set up yet.');
        }

        $claimAvail = new ClaimAvailService();
        $formQuote  = $claimAvail->formQuote($planHolderId, ['package_id' => (int) $package['package_id']]);
        $el         = $formQuote['eligibility'];
        $plan       = $claimAvail->planFor($planHolderId);
        $existing   = $el['code'] === 'open_request' ? $claimAvail->openRequest($planHolderId) : null;

        $db = db_connect();
        $branch = $db->table('branches')->select('branch_name, contact_number')->where('branch_id', (int) $holder['branch_id'])->get()->getRowArray();
        $firstBeneficiary = $db->table('beneficiaries')->where('plan_holder_id', $planHolderId)->orderBy('is_primary', 'DESC')->orderBy('beneficiary_id', 'ASC')->get()->getRowArray();

        $memberName = trim(implode(' ', array_filter([$holder['first_name'], $holder['middle_name'], $holder['last_name']])));
        $claimantOptions = $this->claims->claimantOptions($planHolderId);
        $deceasedOptions = \App\Services\PlanClosureService::ready() ? $this->claims->deceasedOptions($planHolderId, true) : null;

        $membershipService = new \App\Services\MembershipService();

        return view('branch_admin/service_package/file_claim', [
            'role_layout' => $this->layout(),
            'page_title' => 'File a claim for a plan holder',
            'page_sub' => 'You are filing this claim on behalf of the plan holder.',
            'holder' => $holder,
            'package' => $package,
            'eligibility' => ['eligible' => $el['allowed'], 'reason' => $el['message']],
            'form_quote' => $formQuote,
            'claimant_options' => $claimantOptions,
            'deceased_options' => $deceasedOptions,
            'existing_claim' => $existing,
            'attire_price' => ClaimSubmissionService::BURIAL_ATTIRE_PRICE,
            'plan_name' => $membershipService->resolvePackageName((int) ($plan['package_id'] ?? 0)),
            'member_name' => $memberName,
            'member_address' => cs_format_address($holder),
            'beneficiary_prefill' => $firstBeneficiary
                ? trim(implode(' ', array_filter([$firstBeneficiary['first_name'] ?? '', $firstBeneficiary['middle_name'] ?? '', $firstBeneficiary['last_name'] ?? '', $firstBeneficiary['name_extension'] ?? ''])))
                : '',
            'branch_name' => (string) ($branch['branch_name'] ?? ''),
            'branch_phone' => (string) ($branch['contact_number'] ?? ''),
            'form_errors' => session()->getFlashdata('form_errors') ?? [],
            'file_claim_path' => $this->fileClaimPath(),
            'requests_path' => $this->requestsPath(),
            'holder_path' => $this->holderPath(),
        ]);
    }

    /** Step 3: save it. */
    public function store(int $planHolderId)
    {
        $this->guard();
        $holder = $this->holderOr404($planHolderId);
        $package = $this->claims->entitlementPackage($planHolderId);

        if ($package === null) {
            return redirect()->to('/' . $this->requestsPath())->with('error', 'The casket package is not set up yet.');
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
                'actor_role' => $this->roleLabel(),
            ]
        );

        if (! $result['ok']) {
            if (! empty($result['errors'])) {
                return redirect()->back()->withInput()->with('form_errors', $result['errors']);
            }

            return redirect()->back()->withInput()->with('error', (string) ($result['error'] ?? 'Unable to file the claim right now.'));
        }

        (new ActivityLogService())->log((int) session('user_id'), 'created', 'service_application', (int) $result['application_id'], 'Filed a casket claim for plan holder #' . (int) $holder['plan_holder_id']);

        return redirect()->to('/' . $this->requestsPath() . '/' . (int) $result['application_id'])
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
