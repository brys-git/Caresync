<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ActivityLogService;
use App\Services\ClientService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ClientController extends BaseController
{
    private ClientService $clientService;

    public function __construct()
    {
        $this->clientService = new ClientService();
    }

    public function index(): string
    {
        $branchId = (int) session('branch_id');
        $clients = $branchId > 0
            ? $this->clientService->getClientsByBranch($branchId)
            : [];
        
        return view('branch_admin/client_management/index', [
            'holders' => $clients,
            'program' => MembershipService::getProgramInfo(),
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function edit(int $id): string
    {
        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->enforceBranchOwnership($client);

        return view('branch_admin/clients/edit', [
            'client' => $client,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function update(int $id)
    {
        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->enforceBranchOwnership($client);

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

        return redirect()->to('/branch-admin/client/view/' . $id)->with('success', 'Client information updated successfully.');
    }

    public function view(int $id): string
    {
        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->enforceBranchOwnership($client);

        $planId = isset($client['plan']['plan_id']) ? (int) $client['plan']['plan_id'] : null;
        $payments = $this->clientService->getClientPayments($planId);
        $services = $this->clientService->getClientServices($id);

        // Get initial payment (first payment for this plan)
        $initialPayment = null;
        if ($planId) {
            $initialPayment = db_connect()->table('payments')
                ->where('plan_id', $planId)
                ->where('status !=', '')
                ->where('status IS NOT NULL', null, false)
                ->orderBy('payment_date', 'ASC')
                ->orderBy('payment_id', 'ASC')
                ->get()
                ->getRowArray();
        }

        return view('branch_admin/client_management/details', [
            'holder' => $client,
            'initial_payment' => $initialPayment,
            'payments' => $payments,
            'services' => $services,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function create(): string
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $existingUsers = db_connect()->table('users u')
            ->select('u.user_id, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number')
            ->join('plan_holders ph', 'ph.user_id = u.user_id', 'left')
            ->where('u.role_id', 4)
            ->where('ph.plan_holder_id IS NULL', null, false)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('branch_admin/clients/register', [
            'existing_users' => $existingUsers,
            'program' => MembershipService::getProgramInfo(),
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function store()
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            return redirect()->back()->with('error', 'Branch information is missing.');
        }

        $mode = (string) $this->request->getPost('client_account_mode');
        if (! in_array($mode, ['existing', 'new'], true)) {
            $mode = 'existing';
        }

        $rules = [
            'contact_number' => 'permit_empty|max_length[30]',
            'date_of_birth' => 'permit_empty|valid_date',
            'gender' => 'permit_empty|in_list[Male,Female,Other]',
            'civil_status' => 'permit_empty|in_list[Single,Married,Divorced,Widowed]',
            'citizenship' => 'permit_empty|max_length[50]',
            'address_street' => 'permit_empty|max_length[100]',
            'address_barangay' => 'permit_empty|max_length[100]',
            'address_city' => 'permit_empty|max_length[100]',
        ];

        if ($mode === 'existing') {
            $rules['email'] = 'required|valid_email|max_length[100]';
        } else {
            $rules['first_name'] = 'required|max_length[50]';
            $rules['last_name'] = 'required|max_length[50]';
            $rules['email'] = 'required|valid_email|max_length[100]|is_unique[users.email]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $existingUser = null;
            if ($mode === 'existing') {
                $existingUser = $this->clientService->findUserByEmail(trim((string) $this->request->getPost('email')));
                if (! $existingUser) {
                    return redirect()->back()->withInput()->with('error', 'No existing account found for the provided email.');
                }
            }

            $planHolderId = $this->clientService->registerPlanHolder([
                'first_name' => $mode === 'existing' ? trim((string) ($existingUser['first_name'] ?? '')) : trim((string) $this->request->getPost('first_name')),
                'middle_name' => $mode === 'existing' ? trim((string) ($existingUser['middle_name'] ?? '')) : trim((string) $this->request->getPost('middle_name')),
                'last_name' => $mode === 'existing' ? trim((string) ($existingUser['last_name'] ?? '')) : trim((string) $this->request->getPost('last_name')),
                'email' => trim((string) $this->request->getPost('email')),
                'contact_number' => $mode === 'existing' ? trim((string) ($existingUser['contact_number'] ?? '')) : trim((string) $this->request->getPost('contact_number')),
                'date_of_birth' => trim((string) $this->request->getPost('date_of_birth')),
                'place_of_birth' => trim((string) $this->request->getPost('place_of_birth')),
                'age' => trim((string) $this->request->getPost('age')),
                'gender' => trim((string) $this->request->getPost('gender')),
                'civil_status' => trim((string) $this->request->getPost('civil_status')),
                'citizenship' => trim((string) $this->request->getPost('citizenship')),
                'height' => trim((string) $this->request->getPost('height')),
                'weight' => trim((string) $this->request->getPost('weight')),
                'address_no' => trim((string) $this->request->getPost('address_no')),
                'address_street' => trim((string) $this->request->getPost('address_street')),
                'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
                'address_city' => trim((string) $this->request->getPost('address_city')),
                'spouse_name' => trim((string) $this->request->getPost('spouse_name')),
                'spouse_birthdate' => trim((string) $this->request->getPost('spouse_birthdate')),
                'spouse_occupation' => trim((string) $this->request->getPost('spouse_occupation')),
                'senior_citizen_id' => trim((string) $this->request->getPost('senior_citizen_id')),
                'organization_affiliation' => trim((string) $this->request->getPost('organization_affiliation')),
            ], $branchId);

            $targetUserId = 0;
            if ($mode === 'existing') {
                $targetUserId = (int) ($existingUser['user_id'] ?? 0);
            } else {
                $newlyCreated = $this->clientService->findUserByEmail(trim((string) $this->request->getPost('email')));
                $targetUserId = (int) ($newlyCreated['user_id'] ?? 0);
            }

            if ($targetUserId > 0) {
                (new NotificationService())->notify(
                    $targetUserId,
                    'Your account was linked as a plan holder and your Damayan Burial Program plan was registered by branch admin.',
                    'registration_pending'
                );

                (new ActivityLogService())->log(
                    (int) session('user_id'),
                    'created',
                    'plan_holder',
                    (int) $planHolderId,
                    'Registered plan holder from branch admin client form',
                    null,
                    ['branch_id' => $branchId]
                );
            }

            return redirect()->to('/branch-admin/client/view/' . $planHolderId)->with('success', 'Plan holder registered successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approve(int $id)
    {
        $client = $this->clientService->getClientDetails($id);

        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->enforceBranchOwnership($client);

        // Check if client has pending initial payment
        $pendingPayment = db_connect()->table('payments')
            ->select('payment_id, amount, payment_method, reference_number, months_covered')
            ->where('plan_id', (int) $client['plan_id'])
            ->where('status', 'pending')
            ->orderBy('payment_date', 'DESC')
            ->get()
            ->getRowArray();

        if (! $pendingPayment) {
            return redirect()->back()->with('error', 'No pending payment found for approval.');
        }

        try {
            // Update payment status to paid
            db_connect()->table('payments')
                ->where('payment_id', (int) $pendingPayment['payment_id'])
                ->update(['status' => 'paid']);

            // Trigger approval service
            (new \App\Services\ApprovalService())->approveInitialPayment((int) $pendingPayment['payment_id']);

            // Log activity
            (new ActivityLogService())->log(
                (int) session('user_id'),
                'approved',
                'payment',
                (int) $pendingPayment['payment_id'],
                'Approved initial payment for plan holder',
                null,
                ['plan_holder_id' => $id, 'amount' => $pendingPayment['amount']]
            );

            // Notify client
            (new NotificationService())->notify(
                (int) $client['user_id'],
                'Your initial payment has been approved! Your Damayan Burial Program membership is now active.',
                'payment_approved'
            );

            return redirect()->back()->with('success', 'Payment approved and membership activated successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to approve payment: ' . $e->getMessage());
        }
    }

    private function enforceBranchOwnership(array $client): void
    {
        $sessionBranchId = (int) session('branch_id');
        $clientBranchId = (int) ($client['branch_id'] ?? 0);

        if ($sessionBranchId <= 0 || $clientBranchId !== $sessionBranchId) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
