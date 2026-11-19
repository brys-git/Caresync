<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ActivityLogService;
use App\Services\RemittanceService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The Branch Admin's side of remittances (Part E): see what collectors of THIS branch handed in, confirm the cash was
 * received, or report a discrepancy. A remittance of another branch is never shown or changed (every call is scoped to the
 * signed-in Branch Admin's branch).
 */
class RemittanceController extends BaseController
{
    private RemittanceService $service;

    public function __construct()
    {
        $this->service = new RemittanceService();
    }

    public function index(): ResponseInterface|string
    {
        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            return redirect()->to('/unauthorized');
        }
        $status = (string) $this->request->getGet('status');
        $filters = [
            'branch_id' => $branchId,
            'status' => $status,
            'collector_id' => (int) $this->request->getGet('collector_id'),
            'from' => $this->date((string) $this->request->getGet('date_from')),
            'to' => $this->date((string) $this->request->getGet('date_to')),
        ];

        $collectors = db_connect()->table('users')->select('user_id, first_name, last_name')
            ->groupStart()->where('role_id', 5)->orWhere('is_collector', 1)->groupEnd()
            ->where('branch_id', $branchId)->orderBy('first_name')->get()->getResultArray();

        return view('remittances/index', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Remittances',
            'page_sub' => 'Cash your collectors hand over. Confirm it when you receive it, or report what is different.',
            'rows' => $this->service->list($filters),
            'filters' => $filters,
            'collectors' => $collectors,
            'waiting' => $this->service->pendingCount($branchId),
            'rule' => $this->service->ruleText(),
        ]);
    }

    public function show(int $id): ResponseInterface|string
    {
        $branchId = (int) session('branch_id');
        $r = $this->service->find($id);
        if ($r === null || (int) $r['branch_id'] !== $branchId) {
            return redirect()->to('/branch-admin/remittances')->with('error', 'Remittance not found.');
        }

        return view('remittances/show', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Remittance #' . $id,
            'page_sub' => $r['collector_name'] . ' - submitted ' . cs_date($r['submitted_at'], true),
            'r' => $r,
            'lines' => $this->service->lines($id),
            'mode' => 'branch_admin',
            'back_url' => 'branch-admin/remittances',
            'rule' => $this->service->ruleText((float) $r['commission_rate'], (string) $r['commission_applies_to']),
        ]);
    }

    public function confirm(int $id): ResponseInterface
    {
        $result = $this->service->confirm($id, (int) session('branch_id'), (int) session('user_id'));
        if ($result['ok']) {
            (new ActivityLogService())->log((int) session('user_id'), 'updated', 'remittance', $id, $result['message']);
        }

        return redirect()->to('/branch-admin/remittances/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function discrepancy(int $id): ResponseInterface
    {
        $result = $this->service->reportDiscrepancy(
            $id,
            (int) session('branch_id'),
            (int) session('user_id'),
            (float) str_replace(',', '', (string) $this->request->getPost('received_amount')),
            (string) $this->request->getPost('note')
        );
        if ($result['ok']) {
            (new ActivityLogService())->log((int) session('user_id'), 'updated', 'remittance', $id, $result['message']);
        }

        return redirect()->to('/branch-admin/remittances/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function date(string $v): string
    {
        $v = trim($v);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : '';
    }
}
