<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;

class Branches extends BaseController
{
    public function index(): string
    {
        $branchModel = new BranchModel();
        $userModel = new UserModel();

        $branches = $branchModel->orderBy('branch_name', 'ASC')->findAll();
        $users = $userModel
            ->select('user_id, username, first_name, last_name, role_id, branch_id')
            ->whereIn('role_id', [2, 3, 4])
            ->orderBy('first_name', 'ASC')
            ->findAll();

        return view('branches/index', [
            'branches' => $branches,
            'users' => $users,
            'role_layout' => $this->resolveLayoutView(),
        ]);
    }

    public function store()
    {
        $rules = [
            'branch_name' => 'required|max_length[100]',
            'address_barangay' => 'required|max_length[100]',
            'address_city' => 'required|max_length[100]',
            'address_province' => 'required|max_length[100]',
            'status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $branchModel = new BranchModel();
        $saved = $branchModel->insert([
            'branch_name' => trim((string) $this->request->getPost('branch_name')),
            'address_street' => trim((string) $this->request->getPost('address_street')),
            'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
            'address_city' => trim((string) $this->request->getPost('address_city')),
            'address_province' => trim((string) $this->request->getPost('address_province')),
            'contact_number' => trim((string) $this->request->getPost('contact_number')),
            'manager_first_name' => trim((string) $this->request->getPost('manager_first_name')),
            'manager_middle_name' => trim((string) $this->request->getPost('manager_middle_name')),
            'manager_last_name' => trim((string) $this->request->getPost('manager_last_name')),
            'manager_extension' => trim((string) $this->request->getPost('manager_extension')),
            'manager_position' => trim((string) $this->request->getPost('manager_position')),
            'date_established' => $this->nullablePost('date_established'),
            'status' => (string) $this->request->getPost('status'),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add branch.');
        }

        return redirect()->to('/branches')->with('success', 'Branch added successfully.');
    }

    public function assignUser()
    {
        $rules = [
            'user_id' => 'required|is_natural_no_zero',
            'branch_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userId = (int) $this->request->getPost('user_id');
        $branchId = (int) $this->request->getPost('branch_id');

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Selected user was not found.');
        }

        $updated = $userModel->update($userId, ['branch_id' => $branchId]);
        if (! $updated) {
            return redirect()->back()->withInput()->with('error', 'Failed to assign user to branch.');
        }

        (new NotificationService())->notify($userId, 'Your account has been assigned to a branch.', 'general');
        (new ActivityLogService())->log((int) session('user_id'), 'updated', 'branch_assignment', $userId, 'Assigned user to branch', ['branch_id' => null], ['branch_id' => $branchId]);

        return redirect()->to('/branches')->with('success', 'User assigned to branch successfully.');
    }

    public function activity(): string
    {
        $roleId = (int) session('role_id');
        $userBranchId = (int) session('branch_id');
        $db = db_connect();

        $sql = "
            SELECT
                b.branch_id,
                b.branch_name,
                b.status AS branch_status,
                (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.branch_id) AS user_count,
                (SELECT COUNT(*) FROM plan_holders ph WHERE ph.branch_id = b.branch_id) AS plan_holder_count,
                (SELECT COUNT(*) FROM services s WHERE s.branch_id = b.branch_id) AS service_total,
                (SELECT COUNT(*) FROM services s WHERE s.branch_id = b.branch_id AND s.status = 'pending') AS service_pending,
                (SELECT COUNT(*) FROM services s WHERE s.branch_id = b.branch_id AND s.status = 'ongoing') AS service_ongoing,
                (SELECT COUNT(*) FROM services s WHERE s.branch_id = b.branch_id AND s.status = 'completed') AS service_completed,
                (SELECT COUNT(*) FROM services s WHERE s.branch_id = b.branch_id AND s.status = 'cancelled') AS service_cancelled
            FROM branches b
        ";

        if ($roleId === 2 && $userBranchId > 0) {
            $sql .= ' WHERE b.branch_id = ' . $db->escape($userBranchId);
        }

        $sql .= ' ORDER BY b.branch_name ASC';

        $activityRows = $db->query($sql)->getResultArray();

        return view('branches/activity', [
            'activity_rows' => $activityRows,
            'role_layout' => $this->resolveLayoutView(),
        ]);
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

    private function nullablePost(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }
}
