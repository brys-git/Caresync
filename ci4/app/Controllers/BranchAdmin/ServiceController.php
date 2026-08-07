<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Models\ServiceModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ServiceController extends BaseController
{
    private ServiceModel $serviceModel;
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * List ongoing services
     */
    public function index(): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $ongoingServices = $branchId > 0
            ? db_connect()->table('services s')
                ->select('s.service_id, s.plan_holder_id, s.service_list_id, s.package_id, s.application_id, s.service_date, s.service_time, s.burial_location, s.status, s.assigned_staff, s.notes, u.first_name, u.last_name, p.package_name, sl.service_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = s.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
                ->join('users st', 'st.user_id = s.assigned_staff', 'left')
                ->where('s.branch_id', $branchId)
                ->orderBy('s.service_date', 'DESC')
                ->orderBy('s.service_id', 'DESC')
                ->get()
                ->getResultArray()
            : [];

        $staff = $branchId > 0
            ? db_connect()->table('users')
                ->select('user_id, first_name, last_name')
                ->where('role_id', 3)
                ->where('branch_id', $branchId)
                ->orderBy('first_name', 'ASC')
                ->get()
                ->getResultArray()
            : [];

        return view('branch_admin/service_package/index', [
            'active_tab' => 'ongoing',
            'ongoing_services' => $ongoingServices,
            'staff' => $staff,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    /**
     * Show schedule form
     */
    public function create(): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $planHolders = $branchId > 0
            ? db_connect()->table('plan_holders ph')
                ->select('ph.plan_holder_id, u.first_name, u.last_name')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->where('ph.branch_id', $branchId)
                ->where('ph.status', 'active')
                ->orderBy('u.first_name', 'ASC')
                ->get()
                ->getResultArray()
            : [];

        $packages = db_connect()->table('packages')
            ->select('package_id, package_name, base_price')
            ->where('is_available', 1)
            ->orderBy('package_name', 'ASC')
            ->get()
            ->getResultArray();

        $serviceList = db_connect()->table('service_list')
            ->select('service_list_id, service_name, base_price, status')
            ->where('is_available', 1)
            ->where('status', 'active')
            ->orderBy('service_name', 'ASC')
            ->get()
            ->getResultArray();

        $planHolderPackageMap = [];
        $db = db_connect();
        if ($branchId > 0 && $db->tableExists('plans')) {
            $planHolderPackageRows = $db->table('plans p')
                ->select('p.plan_holder_id, p.package_id, pk.package_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
                ->join('packages pk', 'pk.package_id = p.package_id', 'left')
                ->where('ph.branch_id', $branchId)
                ->orderBy('p.plan_holder_id', 'ASC')
                ->orderBy('p.plan_id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($planHolderPackageRows as $row) {
                $planHolderId = (int) ($row['plan_holder_id'] ?? 0);
                if ($planHolderId <= 0 || isset($planHolderPackageMap[$planHolderId])) {
                    continue;
                }
                $planHolderPackageMap[$planHolderId] = [
                    'package_id' => (int) ($row['package_id'] ?? 0),
                    'package_name' => (string) ($row['package_name'] ?? ''),
                ];
            }
        }

        $packageServiceMap = [];
        $packageServiceFields = $db->fieldExists('service_list_id', 'package_services') ? 'service_list_id' : 'service_id';
        $packageServiceRows = $db->table('package_services ps')
            ->select("ps.package_id, sl.service_list_id, sl.service_name, sl.base_price")
            ->join('service_list sl', "sl.service_list_id = ps.{$packageServiceFields}", 'inner')
            ->where('sl.is_available', 1)
            ->where('sl.status', 'active')
            ->orderBy('sl.service_name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($packageServiceRows as $row) {
            $packageId = (int) ($row['package_id'] ?? 0);
            if ($packageId <= 0) continue;
            if (! isset($packageServiceMap[$packageId])) {
                $packageServiceMap[$packageId] = [];
            }
            $packageServiceMap[$packageId][] = [
                'service_list_id' => (int) ($row['service_list_id'] ?? 0),
                'service_name' => (string) ($row['service_name'] ?? ''),
                'base_price' => (float) ($row['base_price'] ?? 0),
            ];
        }

        // Get approved applications for this branch
        $approvedApplications = $branchId > 0
            ? $db->table('service_applications sa')
                ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.service_list_id, u.first_name, u.last_name, p.package_name, sl.service_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
                ->where('ph.branch_id', $branchId)
                ->where('sa.status', 'approved')
                ->orderBy('sa.created_at', 'DESC')
                ->get()
                ->getResultArray()
            : [];

        return view('branch_admin/service_package/index', [
            'active_tab' => 'schedule',
            'plan_holders' => $planHolders,
            'packages' => $packages,
            'service_list' => $serviceList,
            'package_service_map' => $packageServiceMap,
            'plan_holder_package_map' => $planHolderPackageMap,
            'approved_applications' => $approvedApplications,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    /**
     * Save scheduled service
     */
    public function store()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'plan_holder_id' => 'required|is_natural_no_zero',
            'package_id' => 'required|is_natural_no_zero',
            'service_list_id' => 'permit_empty|is_natural_no_zero',
            'service_date' => 'required|valid_date',
            'service_time' => 'permit_empty',
            'burial_location' => 'permit_empty|max_length[255]',
            'notes' => 'permit_empty|max_length[500]',
            'application_id' => 'permit_empty|is_natural',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $planHolderId = (int) $this->request->getPost('plan_holder_id');
        $serviceDate = (string) $this->request->getPost('service_date');
        $serviceTime = trim((string) $this->request->getPost('service_time'));

        // Verify plan holder belongs to this branch
        $planHolder = $db->table('plan_holders')
            ->where('plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        if (! $planHolder || (int) ($planHolder['branch_id'] ?? 0) !== $branchId) {
            return redirect()->back()->withInput()->with('error', 'Invalid plan holder selection.');
        }

        // Verify package is available
        $package = $db->table('packages')
            ->where('package_id', (int) $this->request->getPost('package_id'))
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->back()->withInput()->with('error', 'Selected package is unavailable.');
        }

        // CHECK 1: Duplicate schedule — same plan holder + same date
        $existingService = $db->table('services')
            ->where('plan_holder_id', $planHolderId)
            ->where('service_date', $serviceDate)
            ->whereIn('status', ['pending', 'ongoing'])
            ->get()
            ->getRowArray();

        if ($existingService) {
            return redirect()->back()->withInput()->with('error', 'This plan holder already has a service scheduled for ' . $serviceDate . '.');
        }

        // CHECK 2: Staff conflict — same staff + same date + same time
        $assignedStaff = trim((string) $this->request->getPost('assigned_staff'));
        if ($assignedStaff !== '') {
            $staffConflict = $db->table('services')
                ->where('assigned_staff', (int) $assignedStaff)
                ->where('service_date', $serviceDate)
                ->whereIn('status', ['pending', 'ongoing'])
                ->get()
                ->getRowArray();

            if ($staffConflict) {
                return redirect()->back()->withInput()->with('error', 'The selected staff member is already assigned to another service on ' . $serviceDate . '.');
            }
        }

        $saved = $this->serviceModel->insert([
            'plan_holder_id' => $planHolderId,
            'branch_id' => $branchId,
            'application_id' => $this->request->getPost('application_id') ? (int) $this->request->getPost('application_id') : null,
            'service_list_id' => $this->request->getPost('service_list_id') ? (int) $this->request->getPost('service_list_id') : null,
            'package_id' => (int) $this->request->getPost('package_id'),
            'total_cost' => (string) ($package['base_price'] ?? '0.00'),
            'service_date' => $serviceDate,
            'service_time' => $serviceTime ?: null,
            'burial_location' => trim((string) $this->request->getPost('burial_location')) ?: null,
            'assigned_staff' => $assignedStaff !== '' ? (int) $assignedStaff : null,
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'status' => 'pending',
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to schedule service.');
        }

        $serviceId = (int) $this->serviceModel->insertID();

        // Notify client
        $userId = (int) ($planHolder['user_id'] ?? 0);
        if ($userId > 0) {
            $this->notificationService->notify(
                $userId,
                'Your service has been scheduled for ' . date('F d, Y', strtotime($serviceDate)) . ($serviceTime ? ' at ' . date('h:i A', strtotime($serviceTime)) : '') . '.',
                'service_approved'
            );
        }

        // Notify assigned staff
        if ($assignedStaff !== '') {
            $staffUser = $db->table('users')->where('user_id', (int) $assignedStaff)->get()->getRowArray();
            if ($staffUser) {
                $this->notificationService->notify(
                    (int) $assignedStaff,
                    'You have been assigned a service for ' . date('F d, Y', strtotime($serviceDate)) . '.',
                    'registration_pending'
                );
            }
        }

        // Log activity
        $this->activityLogService->log(
            (int) session('user_id'),
            'created',
            'service',
            $serviceId,
            'Scheduled service for plan holder #' . $planHolderId . ' on ' . $serviceDate
        );

        return redirect()->to('/branch-admin/service-package/ongoing')->with('success', 'Service scheduled successfully.');
    }

    /**
     * Update service status and/or staff assignment
     */
    public function updateStatus(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $service = $this->serviceModel->find($id);

        if (! $service || (int) ($service['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'status' => 'required|in_list[pending,ongoing,completed,cancelled]',
            'assigned_staff' => 'permit_empty|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $newStatus = (string) $this->request->getPost('status');
        $assignedStaff = trim((string) $this->request->getPost('assigned_staff'));

        // CHECK: Staff conflict — same staff + same date + same time
        if ($assignedStaff !== '') {
            $serviceDate = (string) ($service['service_date'] ?? '');
            $staffConflict = db_connect()->table('services')
                ->where('assigned_staff', (int) $assignedStaff)
                ->where('service_date', $serviceDate)
                ->where('service_id !=', $id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->get()
                ->getRowArray();

            if ($staffConflict) {
                return redirect()->back()->with('error', 'The selected staff member is already assigned to another service on ' . $serviceDate . '.');
            }

            // Validate staff belongs to this branch
            $staff = db_connect()->table('users')
                ->where('user_id', (int) $assignedStaff)
                ->where('role_id', 3)
                ->where('branch_id', $branchId)
                ->get()
                ->getRowArray();

            if (! $staff) {
                return redirect()->back()->with('error', 'Assigned staff is invalid for this branch.');
            }
        }

        $updateData = [
            'status' => $newStatus,
            'assigned_staff' => $assignedStaff === '' ? null : (int) $assignedStaff,
        ];

        $updated = $this->serviceModel->update($id, $updateData);

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to update service status.');
        }

        // Notify client of status change
        $planHolderId = (int) ($service['plan_holder_id'] ?? 0);
        if ($planHolderId > 0) {
            $userId = (int) (db_connect()->table('plan_holders')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray()['user_id'] ?? 0);

            if ($userId > 0) {
                $statusMessages = [
                    'ongoing' => 'Your service has started.',
                    'completed' => 'Your service has been completed.',
                    'cancelled' => 'Your service has been cancelled.',
                ];
                if (isset($statusMessages[$newStatus])) {
                    $this->notificationService->notify($userId, $statusMessages[$newStatus], 'service_approved');
                }
            }
        }

        // Notify staff if newly assigned
        if ($assignedStaff !== '' && (int) ($service['assigned_staff'] ?? 0) !== (int) $assignedStaff) {
            $this->notificationService->notify(
                (int) $assignedStaff,
                'You have been assigned a service for ' . date('F d, Y', strtotime((string) ($service['service_date'] ?? ''))) . '.',
                'registration_pending'
            );
        }

        // Log activity
        $this->activityLogService->log(
            (int) session('user_id'),
            $newStatus === 'completed' ? 'completed' : ($newStatus === 'cancelled' ? 'cancelled' : 'updated'),
            'service',
            $id,
            'Updated service #' . $id . ' status to ' . $newStatus
        );

        return redirect()->to('/branch-admin/service-package/ongoing')->with('success', 'Service status updated successfully.');
    }

    /**
     * Reschedule a service
     */
    public function reschedule(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $service = $this->serviceModel->find($id);

        if (! $service || (int) ($service['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! in_array((string) ($service['status'] ?? ''), ['pending', 'ongoing'], true)) {
            return redirect()->back()->with('error', 'Only pending or ongoing services can be rescheduled.');
        }

        $rules = [
            'service_date' => 'required|valid_date',
            'service_time' => 'permit_empty',
            'reschedule_reason' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $newDate = (string) $this->request->getPost('service_date');
        $newTime = trim((string) $this->request->getPost('service_time'));
        $reason = trim((string) $this->request->getPost('reschedule_reason'));

        // CHECK: Staff conflict on new date
        $assignedStaff = (int) ($service['assigned_staff'] ?? 0);
        if ($assignedStaff > 0) {
            $staffConflict = db_connect()->table('services')
                ->where('assigned_staff', $assignedStaff)
                ->where('service_date', $newDate)
                ->where('service_id !=', $id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->get()
                ->getRowArray();

            if ($staffConflict) {
                return redirect()->back()->with('error', 'The assigned staff member is already booked on ' . $newDate . '.');
            }
        }

        $oldDate = (string) ($service['service_date'] ?? '');

        $updated = $this->serviceModel->update($id, [
            'service_date' => $newDate,
            'service_time' => $newTime ?: null,
            'notes' => 'Rescheduled from ' . $oldDate . ': ' . $reason,
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to reschedule service.');
        }

        // Notify client
        $planHolderId = (int) ($service['plan_holder_id'] ?? 0);
        if ($planHolderId > 0) {
            $userId = (int) (db_connect()->table('plan_holders')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray()['user_id'] ?? 0);

            if ($userId > 0) {
                $this->notificationService->notify(
                    $userId,
                    'Your service has been rescheduled from ' . date('F d, Y', strtotime($oldDate)) . ' to ' . date('F d, Y', strtotime($newDate)) . '.',
                    'service_approved'
                );
            }
        }

        // Log activity
        $this->activityLogService->log(
            (int) session('user_id'),
            'updated',
            'service',
            $id,
            'Rescheduled service from ' . $oldDate . ' to ' . $newDate . ': ' . $reason
        );

        return redirect()->to('/branch-admin/service-package/ongoing')->with('success', 'Service rescheduled successfully.');
    }

    /**
     * Cancel a service with reason
     */
    public function cancel(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $service = $this->serviceModel->find($id);

        if (! $service || (int) ($service['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (in_array((string) ($service['status'] ?? ''), ['completed', 'cancelled'], true)) {
            return redirect()->back()->with('error', 'This service cannot be cancelled.');
        }

        $rules = [
            'cancel_reason' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $reason = trim((string) $this->request->getPost('cancel_reason'));

        $updated = $this->serviceModel->update($id, [
            'status' => 'cancelled',
            'notes' => 'Cancelled: ' . $reason,
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to cancel service.');
        }

        // Notify client
        $planHolderId = (int) ($service['plan_holder_id'] ?? 0);
        if ($planHolderId > 0) {
            $userId = (int) (db_connect()->table('plan_holders')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray()['user_id'] ?? 0);

            if ($userId > 0) {
                $this->notificationService->notify(
                    $userId,
                    'Your service scheduled for ' . date('F d, Y', strtotime((string) ($service['service_date'] ?? ''))) . ' has been cancelled. Reason: ' . $reason,
                    'service_rejected'
                );
            }
        }

        // Log activity
        $this->activityLogService->log(
            (int) session('user_id'),
            'cancelled',
            'service',
            $id,
            'Cancelled service: ' . $reason
        );

        return redirect()->to('/branch-admin/service-package/ongoing')->with('success', 'Service cancelled.');
    }

    /**
     * Mark service as completed with completion details
     */
    public function complete(int $id)
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        $service = $this->serviceModel->find($id);

        if (! $service || (int) ($service['branch_id'] ?? 0) !== $branchId) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ((string) ($service['status'] ?? '') !== 'ongoing') {
            return redirect()->back()->with('error', 'Only ongoing services can be marked as completed.');
        }

        $rules = [
            'completion_notes' => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $completionNotes = trim((string) $this->request->getPost('completion_notes'));
        $existingNotes = (string) ($service['notes'] ?? '');
        $finalNotes = $existingNotes !== '' ? $existingNotes . ' | Completed: ' . $completionNotes : 'Completed: ' . $completionNotes;

        $updated = $this->serviceModel->update($id, [
            'status' => 'completed',
            'notes' => $finalNotes,
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to mark service as completed.');
        }

        // Notify client
        $planHolderId = (int) ($service['plan_holder_id'] ?? 0);
        if ($planHolderId > 0) {
            $userId = (int) (db_connect()->table('plan_holders')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray()['user_id'] ?? 0);

            if ($userId > 0) {
                $this->notificationService->notify(
                    $userId,
                    'Your service for ' . date('F d, Y', strtotime((string) ($service['service_date'] ?? ''))) . ' has been completed. Thank you for using CareSync.',
                    'service_approved'
                );
            }
        }

        // Log activity
        $this->activityLogService->log(
            (int) session('user_id'),
            'completed',
            'service',
            $id,
            'Completed service: ' . $completionNotes
        );

        return redirect()->to('/branch-admin/service-package/ongoing')->with('success', 'Service marked as completed.');
    }

    /**
     * Show service history for a plan holder
     */
    public function history(int $planHolderId): string
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');

        $planHolder = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, u.first_name, u.last_name, ph.unique_identifier')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.plan_holder_id', $planHolderId)
            ->where('ph.branch_id', $branchId)
            ->get()
            ->getRowArray();

        if (! $planHolder) {
            throw PageNotFoundException::forPageNotFound();
        }

        $services = db_connect()->table('services s')
            ->select('s.*, p.package_name, sl.service_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name')
            ->join('packages p', 'p.package_id = s.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->join('users st', 'st.user_id = s.assigned_staff', 'left')
            ->where('s.plan_holder_id', $planHolderId)
            ->where('s.branch_id', $branchId)
            ->orderBy('s.service_date', 'DESC')
            ->get()
            ->getResultArray();

        return view('branch_admin/service_package/history', [
            'plan_holder' => $planHolder,
            'services' => $services,
            'role_layout' => 'layouts/branch_admin',
        ]);
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
