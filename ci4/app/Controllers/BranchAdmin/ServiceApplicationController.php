<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ClaimAvailService;
use App\Services\ClaimService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ServiceApplicationController extends BaseController
{
    private ClaimService $claimService;

    public function __construct()
    {
        $this->claimService = new ClaimService();
    }

    public function index(): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $search = cs_search_term($this->request->getGet('search'));
        (new ClaimAvailService())->expireDownPayments(null);

        return view('branch_admin/service_package/index', [
            'page_title' => 'Service / Package Management',
            'active_tab' => 'requests',
            'search' => $search,
            'requests' => $this->claimService->getBranchClaims($branchId, $search),
            'role_layout' => 'layouts/branch_admin',
            'page_actions' => '<a class="btn btn-primary btn-sm" href="' . esc(base_url('branch-admin/service-package/file-claim'), 'attr') . '"><i class="ti ti-file-plus" aria-hidden="true"></i> File a claim for a plan holder</a>',
        ]);
    }

    public function approve(int $id)
    {
        $this->ensureBranchAdminAccess();

        // Only used when the request is an avail: the Branch Admin chooses the term and may waive
        // the down payment with a written reason.
        $result = $this->claimService->approve($id, (int) session('branch_id'), (int) session('user_id'), 2, [
            'term'   => $this->request->getPost('term'),
            'waive'  => (string) $this->request->getPost('waive_down_payment') === '1',
            'reason' => (string) $this->request->getPost('waiver_reason'),
        ]);

        if (! $result['ok']) {
            if ($result['message'] === 'Claim not found.') {
                throw PageNotFoundException::forPageNotFound();
            }

            return redirect()->back()->with('error', $result['message']);
        }

        // Right after approving, the Branch Admin plans the service (schedule, family contact, tasks).
        if (! empty($result['service_id'])) {
            return redirect()->to('/branch-admin/service-package/services/' . (int) $result['service_id'] . '/plan')->with('success', $result['message'] . ' Now plan the service.');
        }

        return redirect()->to('/branch-admin/service-package/requests')->with('success', $result['message']);
    }

    public function addNote(int $id)
    {
        $this->ensureBranchAdminAccess();

        $result = $this->claimService->addNote($id, (int) session('branch_id'), (int) session('user_id'), (string) $this->request->getPost('note'));

        return redirect()->to('/branch-admin/service-package/requests/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function reject(int $id)
    {
        $this->ensureBranchAdminAccess();

        $reason = trim((string) $this->request->getPost('rejection_reason'));
        $result = $this->claimService->reject($id, (int) session('branch_id'), (int) session('user_id'), $reason, 2);

        if (! $result['ok']) {
            if ($result['message'] === 'Claim not found.') {
                throw PageNotFoundException::forPageNotFound();
            }

            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->to('/branch-admin/service-package/requests')->with('success', $result['message']);
    }

    public function show(int $id): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $request = $this->claimService->getClaimDetail($id, $branchId);

        if (! $request) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('branch_admin/service_package/request_show', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Claim Details',
            'page_sub' => 'Review the submitted claim and supporting documents before approving or rejecting.',
            'claim_base_path' => '/branch-admin/service-package/requests',
            'request' => $request,
            'documents' => $this->claimService->getClaimDocuments($id),
            'claim_info' => (new ClaimAvailService())->decisionInfo($id, $branchId),
            'notes' => $this->claimService->notesFor($id),
            'viewer_role' => 2,
        ]);
    }

    public function downloadDocument(int $id)
    {
        $this->ensureBranchAdminAccess();

        $document = $this->claimService->getClaimDocumentForBranch($id, (int) session('branch_id'));

        if (! $document) {
            return redirect()->back()->with('error', 'Document not found or you are not authorized to view it.');
        }

        $fullPath = WRITEPATH . ($document['path'] ?? '');
        if (! is_file($fullPath)) {
            return redirect()->back()->with('error', 'Document file is missing.');
        }

        return $this->response->download($fullPath, null)->setFileName($document['original_name']);
    }

    private function ensureBranchAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 2 && $roleName !== 'branch admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
