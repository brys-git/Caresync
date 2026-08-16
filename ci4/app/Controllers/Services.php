<?php

namespace App\Controllers;

use App\Models\AddOnModel;
use App\Models\AssignmentModel;
use App\Models\PackageModel;
use App\Models\ServiceCostModel;
use App\Models\ServiceModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;

class Services extends BaseController
{
    /**
     * @var array<string, bool>
     */
    private array $columnExistsCache = [];
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    public function index(): string
    {
        $db = db_connect();
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');
        $packageHasAvailability = $db->tableExists('packages') && $this->tableHasColumn('packages', 'is_available');
        $packageHasStatus = $db->tableExists('packages') && $this->tableHasColumn('packages', 'status');
        $serviceHasAvailability = $db->tableExists('service_list') && $this->tableHasColumn('service_list', 'is_available');

        $serviceBuilder = $db->table('services s')
            ->select("s.*, COALESCE(sl.service_name, '-') AS service_type, p.package_name, ph.unique_identifier, u.first_name, u.last_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name", false)
            ->join('packages p', 'p.package_id = s.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->join('users st', 'st.user_id = s.assigned_staff', 'left')
            ->orderBy('s.service_id', 'DESC');

        if ($roleId === 2 && $branchId > 0) {
            $serviceBuilder->where('s.branch_id', $branchId);
        }

        if ($roleId === 3 && $branchId > 0) {
            $serviceBuilder->groupStart()
                ->where('s.branch_id', $branchId)
                ->orWhere('s.assigned_staff', (int) session('user_id'))
                ->groupEnd();
        }

        $services = $serviceBuilder->get()->getResultArray();

        $planHolderBuilder = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.branch_id, ph.unique_identifier, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->where('ph.status', 'active')
            ->orderBy('u.first_name', 'ASC');

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $planHolderBuilder->where('ph.branch_id', $branchId);
        }

        $planHolders = $planHolderBuilder->get()->getResultArray();

        $packageBuilder = (new PackageModel())->orderBy('package_name', 'ASC');
        if ($packageHasAvailability) {
            $packageBuilder->where('is_available', 1);
        } elseif ($packageHasStatus) {
            $packageBuilder->where('status', 'active');
        }
        $packages = $packageBuilder->findAll();

        $serviceListBuilder = $db->table('service_list')
            ->select('service_list_id, service_name, status')
            ->where('status', 'active')
            ->orderBy('service_name', 'ASC');

        if ($serviceHasAvailability) {
            $serviceListBuilder->where('is_available', 1);
        }

        $serviceList = $serviceListBuilder->get()->getResultArray();

        $staffQuery = $db->table('users')
            ->select('user_id, first_name, last_name, branch_id')
            ->where('role_id', 3)
            ->orderBy('first_name', 'ASC');

        if ($branchId > 0) {
            $staffQuery->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id IS NULL', null, false)
                ->groupEnd();
        }

        $staff = $staffQuery->get()->getResultArray();

        return view('services/index', [
            'services' => $services,
            'plan_holders' => $planHolders,
            'packages' => $packages,
            'service_list' => $serviceList,
            'staff' => $staff,
            'role_layout' => $this->resolveLayoutView(),
        ]);
    }

    public function store()
    {
        $rules = [
            'plan_holder_id' => 'required|is_natural_no_zero',
            'package_id' => 'required|is_natural_no_zero',
            'service_list_id' => 'permit_empty|is_natural_no_zero',
            'service_date' => 'required|valid_date',
            'status' => 'required|in_list[pending,ongoing,completed,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $planHolderId = (int) $this->request->getPost('plan_holder_id');
        $packageId = (int) $this->request->getPost('package_id');
        $planHolder = $db->table('plan_holders')->where('plan_holder_id', $planHolderId)->get()->getRowArray();
        $package = $db->table('packages')->where('package_id', $packageId)->get()->getRowArray();

        if (! $planHolder || ! $package) {
            return redirect()->back()->withInput()->with('error', 'Selected plan holder or package was not found.');
        }

        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');

        if (in_array($roleId, [2, 3], true) && $branchId > 0 && (int) $planHolder['branch_id'] !== $branchId) {
            return redirect()->back()->withInput()->with('error', 'You can only create services for your assigned branch.');
        }

        $serviceModel = new ServiceModel();
        $saved = $serviceModel->insert([
            'plan_holder_id' => $planHolderId,
            'branch_id' => (int) $planHolder['branch_id'],
            'service_list_id' => $this->request->getPost('service_list_id') ? (int) $this->request->getPost('service_list_id') : null,
            'package_id' => $packageId,
            'total_cost' => (string) $package['base_price'],
            'service_date' => (string) $this->request->getPost('service_date'),
            'service_time' => $this->nullablePost('service_time'),
            'burial_location' => $this->nullablePost('burial_location'),
            'assigned_staff' => null,
            'notes' => $this->nullablePost('notes'),
            'status' => (string) $this->request->getPost('status'),
        ], true);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to create service.');
        }

        $this->notificationService->notify((int) $planHolder['user_id'], 'A new service request was created for your account.', 'service_approved');
        $this->activityLogService->log(
            (int) session('user_id'),
            'created',
            'service',
            (int) $saved,
            'Created service request',
            null,
            ['plan_holder_id' => $planHolderId, 'status' => (string) $this->request->getPost('status')]
        );

        return redirect()->to('/services')->with('success', 'Service created successfully.');
    }

    public function assignStaff()
    {
        $rules = [
            'service_id' => 'required|is_natural_no_zero',
            'staff_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceId = (int) $this->request->getPost('service_id');
        $staffId = (int) $this->request->getPost('staff_id');
        $db = db_connect();

        $service = $db->table('services s')
            ->select('s.*, ph.user_id AS plan_holder_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->where('s.service_id', $serviceId)
            ->get()
            ->getRowArray();

        $staff = $db->table('users')->where('user_id', $staffId)->where('role_id', 3)->get()->getRowArray();

        if (! $service || ! $staff) {
            return redirect()->back()->withInput()->with('error', 'Selected service or staff member was not found.');
        }

        $branchId = (int) $service['branch_id'];
        $staffBranchId = $staff['branch_id'] === null ? null : (int) $staff['branch_id'];
        if ($staffBranchId !== null && $staffBranchId !== $branchId) {
            return redirect()->back()->withInput()->with('error', 'Staff member does not belong to this branch.');
        }

        $serviceModel = new ServiceModel();
        $assignmentModel = new AssignmentModel();

        $serviceModel->update($serviceId, ['assigned_staff' => $staffId]);
        $assignmentModel->insert([
            'service_id' => $serviceId,
            'staff_id' => $staffId,
        ]);

        $this->notificationService->notify((int) $service['plan_holder_user_id'], 'A staff member has been assigned to your service.', 'service_approved');
        $this->notificationService->notify($staffId, 'You have been assigned to a service.', 'service_approved');
        $this->activityLogService->log(
            (int) session('user_id'),
            'updated',
            'service',
            $serviceId,
            'Assigned staff to service',
            ['assigned_staff' => null],
            ['assigned_staff' => $staffId]
        );

        return redirect()->to('/services')->with('success', 'Staff assigned to service successfully.');
    }

    public function addCost()
    {
        $rules = [
            'service_id' => 'required|is_natural_no_zero',
            'description' => 'required|max_length[100]',
            'amount' => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceId = (int) $this->request->getPost('service_id');
        $amount = (float) $this->request->getPost('amount');

        $db = db_connect();
        $service = $db->table('services s')
            ->select('s.*, ph.user_id AS plan_holder_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->where('s.service_id', $serviceId)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->withInput()->with('error', 'Service not found.');
        }

        $costModel = new ServiceCostModel();
        $saved = $costModel->insert([
            'service_id' => $serviceId,
            'description' => trim((string) $this->request->getPost('description')),
            'amount' => number_format($amount, 2, '.', ''),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add service cost.');
        }

        $currentTotal = (float) ($service['total_cost'] ?? 0);
        (new ServiceModel())->update($serviceId, ['total_cost' => number_format($currentTotal + $amount, 2, '.', '')]);

        $this->notificationService->notify((int) $service['plan_holder_user_id'], 'A new service cost was added to your service record.', 'service_approved');
        $this->activityLogService->log(
            (int) session('user_id'),
            'updated',
            'service',
            $serviceId,
            'Added service cost',
            null,
            ['amount' => $amount, 'description' => trim((string) $this->request->getPost('description'))]
        );

        return redirect()->to('/services')->with('success', 'Service cost added successfully.');
    }

    public function addAddOn()
    {
        $rules = [
            'service_id' => 'required|is_natural_no_zero',
            'item_name' => 'required|max_length[100]',
            'price' => 'required|decimal|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceId = (int) $this->request->getPost('service_id');
        $price = (float) $this->request->getPost('price');

        $db = db_connect();
        $service = $db->table('services s')
            ->select('s.*, ph.user_id AS plan_holder_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->where('s.service_id', $serviceId)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->withInput()->with('error', 'Service not found.');
        }

        $addOnModel = new AddOnModel();
        $saved = $addOnModel->insert([
            'service_id' => $serviceId,
            'item_name' => trim((string) $this->request->getPost('item_name')),
            'price' => number_format($price, 2, '.', ''),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add service add-on.');
        }

        $currentTotal = (float) ($service['total_cost'] ?? 0);
        (new ServiceModel())->update($serviceId, ['total_cost' => number_format($currentTotal + $price, 2, '.', '')]);

        $this->notificationService->notify((int) $service['plan_holder_user_id'], 'A new service add-on was added to your service record.', 'service_approved');
        $this->activityLogService->log(
            (int) session('user_id'),
            'updated',
            'service',
            $serviceId,
            'Added service add-on',
            null,
            ['item_name' => trim((string) $this->request->getPost('item_name')), 'price' => $price]
        );

        return redirect()->to('/services')->with('success', 'Service add-on added successfully.');
    }

    public function updateStatus()
    {
        $rules = [
            'service_id' => 'required|is_natural_no_zero',
            'status' => 'required|in_list[pending,ongoing,completed,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceId = (int) $this->request->getPost('service_id');
        $status = (string) $this->request->getPost('status');

        $db = db_connect();
        $service = $db->table('services s')
            ->select('s.*, ph.user_id AS plan_holder_user_id, st.user_id AS staff_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->join('users st', 'st.user_id = s.assigned_staff', 'left')
            ->where('s.service_id', $serviceId)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->withInput()->with('error', 'Service not found.');
        }

        (new ServiceModel())->update($serviceId, ['status' => $status]);

        $this->notificationService->notify((int) $service['plan_holder_user_id'], 'Your service status has been updated to ' . $status . '.', 'service_completed');
        if (! empty($service['staff_user_id'])) {
            $this->notificationService->notify((int) $service['staff_user_id'], 'Service status was updated to ' . $status . '.', 'service_completed');
        }

        $this->activityLogService->log(
            (int) session('user_id'),
            'updated',
            'service',
            $serviceId,
            'Updated service status',
            ['status' => $service['status'] ?? null],
            ['status' => $status]
        );

        return redirect()->to('/services')->with('success', 'Service status updated successfully.');
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return 'layouts/admin';
        }

        if ($role === 2) {
            return 'layouts/branch_admin';
        }

        if ($role === 3) {
            return 'layouts/staff';
        }

        return 'layouts/plan_holder';
    }

    protected function nullablePost(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . ':' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $exists = db_connect()->fieldExists($column, $table);
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }
}
