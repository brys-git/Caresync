<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\PlanClosureService;

/**
 * Branch Admin: closure requests of the branch's own clients and the "Close plan" action. The branch is always the
 * session's own (checked again inside PlanClosureService::close()); every rule lives in that service.
 */
class PlanClosureController extends BaseController
{
    public function index(): string
    {
        $branchId = (int) session('branch_id');

        return view('branch_admin/closure_requests', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Closure requests',
            'page_sub' => 'Clients who asked their branch to close their plan.',
            'requests' => (new PlanClosureService())->pendingForBranch($branchId),
            'reasons' => PlanClosureService::REASONS,
        ]);
    }

    public function close(int $planHolderId)
    {
        $svc = new PlanClosureService();
        $result = $svc->close($planHolderId, (int) session('branch_id'), (int) session('user_id'), (string) $this->request->getPost('reason_code'), (string) $this->request->getPost('note'));

        $to = $this->request->getPost('back') === 'requests' && $result['ok'] ? 'branch-admin/closure-requests' : 'branch-admin/client-management/view/' . $planHolderId;

        return redirect()->to(site_url($to))->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
