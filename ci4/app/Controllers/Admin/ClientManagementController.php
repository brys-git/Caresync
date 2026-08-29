<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ClientService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ClientManagementController extends BaseController
{
    private ClientService $clientService;

    public function __construct()
    {
        $this->clientService = new ClientService();
    }

    public function index(): string
    {
        $this->ensureAdminAccess();

        $db = db_connect();
        $branchId = (int) $this->request->getGet('branch_id');
        $status = strtolower(trim((string) $this->request->getGet('status')));
        $search = trim((string) $this->request->getGet('search'));

        $branches = $db->table('branches')
            ->select('branch_id, branch_name')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();

        $latestPlanSubquery = '(SELECT pl1.* FROM plans pl1 JOIN (SELECT plan_holder_id, MAX(plan_id) AS max_plan_id FROM plans GROUP BY plan_holder_id) pl2 ON pl1.plan_id = pl2.max_plan_id)';

        $builder = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.status AS plan_holder_status, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name, p.status AS plan_status, p.monthly_fee, pkg.package_name', false)
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->join($latestPlanSubquery . ' p', 'p.plan_holder_id = ph.plan_holder_id', 'left', false)
            ->join('packages pkg', 'pkg.package_id = p.package_id', 'left')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC');

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $builder->where('ph.status', $status);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->orLike('ph.unique_identifier', $search)
                ->groupEnd();
        }

        $clients = $builder->get()->getResultArray();

        return view('admin/client_management/index', [
            'role_layout' => 'layouts/admin',
            'clients' => $clients,
            'branches' => $branches,
            'filters' => [
                'branch_id' => $branchId,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function view(int $id): string
    {
        $this->ensureAdminAccess();

        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $planId = isset($client['plan']['plan_id']) ? (int) $client['plan']['plan_id'] : null;
        $payments = $this->clientService->getClientPayments($planId);
        $services = $this->clientService->getClientServices($id);

        return view('admin/client_management/view', [
            'client' => $client,
            'payments' => $payments,
            'services' => $services,
            'role_layout' => 'layouts/admin',
        ]);
    }

    public function edit(int $id): string
    {
        $this->ensureAdminAccess();

        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('admin/client_management/edit', [
            'client' => $client,
            'role_layout' => 'layouts/admin',
        ]);
    }

    public function update(int $id)
    {
        $this->ensureAdminAccess();

        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId = (int) $client['user_id'];

        $rules = [
            'first_name' => 'required|max_length[50]',
            'last_name' => 'required|max_length[50]',
            'email' => "required|valid_email|max_length[100]|is_unique[users.email,user_id,{$userId}]",
            'contact_number' => 'permit_empty|max_length[30]',
            'date_of_birth' => 'permit_empty|valid_date',
            'spouse_birthdate' => 'permit_empty|valid_date',
            'age' => 'permit_empty|is_natural',
            'height' => 'permit_empty|decimal',
            'weight' => 'permit_empty|decimal',
            'status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $this->clientService->updateClient($id, [
                'first_name' => trim((string) $this->request->getPost('first_name')),
                'last_name' => trim((string) $this->request->getPost('last_name')),
                'email' => trim((string) $this->request->getPost('email')),
                'contact_number' => trim((string) $this->request->getPost('contact_number')),
                'address_no' => trim((string) $this->request->getPost('address_no')),
                'address_street' => trim((string) $this->request->getPost('address_street')),
                'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
                'address_city' => trim((string) $this->request->getPost('address_city')),
                'date_of_birth' => trim((string) $this->request->getPost('date_of_birth')),
                'place_of_birth' => trim((string) $this->request->getPost('place_of_birth')),
                'age' => trim((string) $this->request->getPost('age')),
                'gender' => trim((string) $this->request->getPost('gender')),
                'civil_status' => trim((string) $this->request->getPost('civil_status')),
                'citizenship' => trim((string) $this->request->getPost('citizenship')),
                'height' => trim((string) $this->request->getPost('height')),
                'weight' => trim((string) $this->request->getPost('weight')),
                'spouse_name' => trim((string) $this->request->getPost('spouse_name')),
                'spouse_birthdate' => trim((string) $this->request->getPost('spouse_birthdate')),
                'spouse_occupation' => trim((string) $this->request->getPost('spouse_occupation')),
                'senior_citizen_id' => trim((string) $this->request->getPost('senior_citizen_id')),
                'organization_affiliation' => trim((string) $this->request->getPost('organization_affiliation')),
                'status' => trim((string) $this->request->getPost('status')),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/admin/client-management/view/' . $id)->with('success', 'Client information updated successfully.');
    }

    private function ensureAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 1 && $roleName !== 'admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
