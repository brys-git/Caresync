<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\ServiceApplicationDocumentModel;
use App\Models\ServiceApplicationModel;
use App\Models\ServiceModel;
use App\Services\ServiceBalanceService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ServiceApplicationController extends BaseController
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

    public function index(): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $requests = $branchId > 0
            ? db_connect()->table('service_applications sa')
                ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.service_list_id, sa.status, sa.created_at, ph.user_id, u.first_name, u.last_name, p.package_name, sl.service_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
                ->where('ph.branch_id', $branchId)
                ->orderBy('sa.created_at', 'DESC')
                ->get()
                ->getResultArray()
            : [];

        return view('branch_admin/service_package/index', [
            'active_tab' => 'requests',
            'requests' => $requests,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function approve(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $request = db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.service_list_id, sa.status, ph.branch_id, ph.user_id, p.base_price AS package_price, p.package_name, sl.base_price AS service_price, sl.service_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('sa.application_id', $id)
            ->get()
            ->getRowArray();

        if (! $request || (int) ($request['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (($request['status'] ?? '') !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be approved.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->serviceApplicationModel->update($id, ['status' => 'approved']);

            $serviceRecordId = (int) $this->serviceModel->insert([
                'plan_holder_id' => (int) $request['plan_holder_id'],
                'branch_id' => $branchId,
                'service_list_id' => (int) ($request['service_list_id'] ?? 0) > 0 ? (int) $request['service_list_id'] : null,
                'package_id' => (int) ($request['package_id'] ?? 0) > 0 ? (int) $request['package_id'] : null,
                'total_cost' => (string) ((int) ($request['service_list_id'] ?? 0) > 0 ? ($request['service_price'] ?? 0) : ($request['package_price'] ?? 0)),
                'service_date' => date('Y-m-d'),
                'service_time' => null,
                'burial_location' => null,
                'assigned_staff' => null,
                'notes' => 'Created from approved service request.',
                'status' => 'pending',
            ]);

            $balanceService = new ServiceBalanceService();
            $balanceId = $balanceService->createBalanceRecord([
                'application_id' => (int) $request['application_id'],
                'plan_holder_id' => (int) $request['plan_holder_id'],
                'branch_id' => $branchId,
                'service_list_id' => (int) ($request['service_list_id'] ?? 0),
                'package_id' => (int) ($request['package_id'] ?? 0),
                'service_type' => (int) ($request['service_list_id'] ?? 0) > 0 ? 'service' : 'package',
                'service_name' => (string) ($request['service_name'] ?? $request['package_name'] ?? 'Selected service'),
                'package_name' => (string) ($request['package_name'] ?? null),
                'package_cost' => (float) ((int) ($request['service_list_id'] ?? 0) > 0 ? ($request['service_price'] ?? 0) : ($request['package_price'] ?? 0)),
            ], [
                'service_id' => $serviceRecordId,
            ]);

            if ($balanceId) {
                $this->notificationModel->insert([
                    'user_id' => (int) $request['user_id'],
                    'message' => 'Your approved service now has a separate funeral balance for beneficiary continuation.',
                    'status' => 'unread',
                ]);
            }

            $this->notificationModel->insert([
                'user_id' => (int) $request['user_id'],
                'message' => 'Your service request has been approved.',
                'status' => 'unread',
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to approve request.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/branch-admin/service-package/requests')->with('success', 'Request approved and moved to services.');
    }

    public function reject(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $request = db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.status, ph.branch_id, ph.user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('sa.application_id', $id)
            ->get()
            ->getRowArray();

        if (! $request || (int) ($request['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->serviceApplicationModel->update($id, ['status' => 'rejected']);

            $this->notificationModel->insert([
                'user_id' => (int) $request['user_id'],
                'message' => 'Your service request has been rejected.',
                'status' => 'unread',
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to reject request.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/branch-admin/service-package/requests')->with('success', 'Request rejected.');
    }

    public function show(int $id): string
    {
        $this->ensureBranchAdminAccess();

        $request = db_connect()->table('service_applications sa')
            ->select('sa.*, ph.branch_id, ph.user_id, u.first_name, u.last_name, p.base_price AS package_price, p.package_name, sl.base_price AS service_price, sl.service_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
            ->where('sa.application_id', $id)
            ->get()
            ->getRowArray();

        if (! $request || (int) ($request['branch_id'] ?? 0) !== (int) session('branch_id')) {
            throw PageNotFoundException::forPageNotFound();
        }

        $documents = [];
        if ((int) ($request['application_id'] ?? 0) > 0) {
            $documents = db_connect()->table('service_application_documents')
                ->where('application_id', (int) $request['application_id'])
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();
        }

        return view('branch_admin/service_package/request_show', [
            'role_layout' => 'layouts/branch_admin',
            'request' => $request,
            'documents' => $documents,
        ]);
    }

    public function downloadDocument(int $id)
    {
        $this->ensureBranchAdminAccess();

        $document = db_connect()->table('service_application_documents')
            ->where('document_id', $id)
            ->get()
            ->getRowArray();

        if (! $document) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $application = db_connect()->table('service_applications')
            ->select('plan_holder_id')
            ->where('application_id', (int) $document['application_id'])
            ->get()
            ->getRowArray();

        if (! $application) {
            return redirect()->back()->with('error', 'Document application not found.');
        }

        $planHolder = db_connect()->table('plan_holders')
            ->select('branch_id')
            ->where('plan_holder_id', (int) $application['plan_holder_id'])
            ->get()
            ->getRowArray();

        if (! $planHolder || (int) ($planHolder['branch_id'] ?? 0) !== (int) session('branch_id')) {
            return redirect()->back()->with('error', 'You are not authorized to view this document.');
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
