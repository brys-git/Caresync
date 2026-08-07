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

        $totalClients = count($clients);
        $activeClients = 0;
        foreach ($clients as $client) {
            if (strtolower((string) ($client['plan_holder_status'] ?? '')) === 'active') {
                $activeClients++;
            }
        }

        $newThisMonth = 0;
        $currentMonth = date('Y-m');
        foreach ($clients as $client) {
            $createdAt = (string) ($client['created_at'] ?? '');
            if ($createdAt !== '' && strpos($createdAt, $currentMonth) === 0) {
                $newThisMonth++;
            }
        }

        return view('branch_admin/client_management/index', [
            'holders' => $clients,
            'program' => MembershipService::getProgramInfo(),
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'new_clients_month' => $newThisMonth,
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

        // Determine if registration can be approved
        $can_approve = false;
        $approval_message = '';
        if ($initialPayment) {
            if (strtolower((string) $initialPayment['status']) === 'verified') {
                $can_approve = true;
            } else {
                $approval_message = 'Initial payment must be verified before approving registration.';
            }
        } else {
            $approval_message = 'No initial payment found. Payment must be processed first.';
        }

        return view('branch_admin/client_management/details', [
            'holder' => $client,
            'initial_payment' => $initialPayment,
            'payments' => $payments,
            'services' => $services,
            'can_approve' => $can_approve,
            'approval_message' => $approval_message,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function registerForm(): string
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = db_connect();

        // Get branches for the dropdown
        $branches = $db->table('branches')
            ->select('branch_id, branch_name')
            ->where('status', 'active')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();

        // Get staff and branch admins for coordinator dropdown (role_id 2 = branch admin, 3 = staff)
        $coordinators = $db->table('users')
            ->select('user_id, first_name, last_name')
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->orderBy('first_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('branch_admin/client/register', [
            'plan_holder' => [],
            'user' => [],
            'beneficiaries' => [],
            'branches' => $branches,
            'coordinators' => $coordinators,
            'program' => MembershipService::getProgramInfo(),
            'plan_id' => 0,
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
        ]);
    }

    public function submitRegister()
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            return redirect()->back()->with('error', 'Branch information is missing.');
        }

        $planId = (int) $this->request->getPost('plan_id');
        if ($planId <= 0) {
            $planId = (int) $this->request->getPost('package_id');
        }

        if ($planId <= 0) {
            return redirect()->back()->with('error', 'Selected plan is unavailable.');
        }

        // Validate required fields using centralized rules
        $rules = \App\Config\ValidationRules::getPlanRegistrationRules();
        $messages = \App\Config\ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $db = db_connect();
            $db->transStart();

            $validIdFile = $this->request->getFile('valid_id');
            $verificationDocument = null;
            if ($validIdFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $validIdFile->isValid() && ! $validIdFile->hasMoved()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                $extension = strtolower($validIdFile->getClientExtension() ?: pathinfo($validIdFile->getClientName(), PATHINFO_EXTENSION));

                if (! in_array($extension, $allowedExtensions, true)) {
                    throw new \RuntimeException('Only JPG, PNG, or PDF files are allowed for the uploaded ID.');
                }

                $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'plan_registration_verification' . DIRECTORY_SEPARATOR . 'branch_admin_' . $branchId;
                if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0777, true) && ! is_dir($uploadDir)) {
                    throw new \RuntimeException('Unable to create the upload directory for the verification document.');
                }

                $fileName = 'valid_id_' . time() . '_' . md5($validIdFile->getClientName() . microtime(true)) . '.' . $extension;
                $validIdFile->move($uploadDir, $fileName);
                $verificationDocument = [
                    'path' => $uploadDir . DIRECTORY_SEPARATOR . $fileName,
                    'name' => $validIdFile->getClientName(),
                ];
            }

            // Create or find user for this plan holder
            $firstName = trim((string) $this->request->getPost('first_name'));
            $lastName = trim((string) $this->request->getPost('last_name'));
            $email = trim((string) $this->request->getPost('email'));
            $contactNumber = trim((string) $this->request->getPost('contact_number'));

            $userModel = new \App\Models\UserModel();
            $existingUser = $userModel->where('email', $email)->first();

            if (! $existingUser) {
                // Create new user
                $userId = $userModel->insert([
                    'first_name' => $firstName,
                    'middle_name' => trim((string) $this->request->getPost('middle_name')),
                    'last_name' => $lastName,
                    'email' => $email,
                    'contact_number' => $contactNumber,
                    'role_id' => 4, // Plan holder
                    'status' => 'active',
                ], true);

                if (! $userId) {
                    throw new \RuntimeException('Unable to create user account.');
                }
            } else {
                $userId = (int) $existingUser['user_id'];
            }

            // Build spouse name
            $spouseName = trim((string) $this->request->getPost('spouse_name'));
            if ($spouseName === '') {
                $spouseFirstName = trim((string) $this->request->getPost('spouse_first_name'));
                $spouseMiddleName = trim((string) $this->request->getPost('spouse_middle_name'));
                $spouseLastName = trim((string) $this->request->getPost('spouse_last_name'));
                $spouseName = trim(implode(' ', array_filter([$spouseFirstName, $spouseMiddleName, $spouseLastName], static fn ($value): bool => $value !== '')));
            }

            // Prepare plan holder data
            $planHolderData = [
                'user_id' => $userId,
                'id_control_no' => trim((string) $this->request->getPost('id_control_no')),
                'coordinator' => trim((string) $this->request->getPost('coordinator')),
                'application_date' => $this->nullablePost('application_date'),
                'address_no' => trim((string) $this->request->getPost('address_no')),
                'address_street' => trim((string) $this->request->getPost('address_street')),
                'address_province' => trim((string) $this->request->getPost('address_province')),
                'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
                'address_city' => trim((string) $this->request->getPost('address_city')),
                'date_of_birth' => $this->nullablePost('date_of_birth'),
                'place_of_birth' => trim((string) $this->request->getPost('place_of_birth')),
                'age' => $this->nullableIntPost('age'),
                'gender' => trim((string) $this->request->getPost('gender')),
                'civil_status' => trim((string) $this->request->getPost('civil_status')),
                'citizenship' => trim((string) $this->request->getPost('citizenship')),
                'height' => $this->nullableDecimalPost('height'),
                'weight' => $this->nullableDecimalPost('weight'),
                'spouse_name' => $spouseName,
                'spouse_birthdate' => $this->nullablePost('spouse_birthdate'),
                'spouse_occupation' => trim((string) $this->request->getPost('spouse_occupation')),
                'senior_citizen_id' => trim((string) $this->request->getPost('senior_citizen_id')),
                'organization_affiliation' => trim((string) $this->request->getPost('organization_affiliation')),
                'branch_id' => $branchId,
                'status' => 'inactive',
            ];

            $planHolderData = $this->filterTableData('plan_holders', $planHolderData);

            $planHolderModel = new \App\Models\PlanHolderModel();
            $existingHolder = $planHolderModel
                ->where('user_id', $userId)
                ->orderBy('plan_holder_id', 'DESC')
                ->first();

            $planHolderId = 0;
            if ($existingHolder) {
                $updated = $planHolderModel->update((int) $existingHolder['plan_holder_id'], $planHolderData);
                if (! $updated) {
                    $dbError = $db->error();
                    $modelErrors = $planHolderModel->errors();
                    throw new \RuntimeException('Unable to update plan holder details. DB: ' . json_encode($dbError) . ' Model: ' . json_encode($modelErrors));
                }

                $planHolderId = (int) $existingHolder['plan_holder_id'];
            } else {
                $inserted = $planHolderModel->insert($planHolderData, true);
                $planHolderId = (int) $inserted;

                if ($planHolderId <= 0) {
                    $insertedRow = $planHolderModel
                        ->where('user_id', $userId)
                        ->orderBy('plan_holder_id', 'DESC')
                        ->first();

                    if ($insertedRow) {
                        $planHolderId = (int) $insertedRow['plan_holder_id'];
                    }
                }

                if ($planHolderId <= 0) {
                    $dbError = $db->error();
                    $modelErrors = $planHolderModel->errors();
                    throw new \RuntimeException('Unable to save plan holder details. DB: ' . json_encode($dbError) . ' Model: ' . json_encode($modelErrors));
                }
            }

            // Update user status
            $userModel->update($userId, [
                'is_plan_holder' => 1,
                'branch_id' => $branchId,
            ]);

            // Process beneficiaries
            $beneficiariesInput = $this->request->getPost('beneficiaries');
            $beneficiariesInput = is_array($beneficiariesInput) ? $beneficiariesInput : [];
            $beneficiaries = [];
            $isPrimary = true;

            foreach ($beneficiariesInput as $row) {
                $firstName = trim((string) ($row['first_name'] ?? ''));
                $middleName = trim((string) ($row['middle_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));
                $birthday = trim((string) ($row['birthday'] ?? $row['date_of_birth'] ?? ''));
                $relationship = trim((string) ($row['relationship'] ?? ''));

                // Skip completely empty rows
                if ($firstName === '' && $middleName === '' && $lastName === '' && $birthday === '' && $relationship === '') {
                    continue;
                }

                if ($firstName === '' && $lastName === '') {
                    throw new \RuntimeException('Beneficiary name is required. Please provide at least a first or last name for each beneficiary.');
                }

                if ($relationship === '') {
                    throw new \RuntimeException('Please enter the relationship for each beneficiary.');
                }

                $beneficiaries[] = [
                    'plan_holder_id' => $planHolderId,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'date_of_birth' => $birthday,
                    'relationship' => $relationship,
                    'is_primary' => $isPrimary ? 1 : 0,
                ];

                $isPrimary = false;
            }

            if (empty($beneficiaries)) {
                throw new \RuntimeException('At least one beneficiary is required.');
            }

            $beneficiaryModel = new \App\Models\BeneficiaryModel();
            $beneficiaryModel->where('plan_holder_id', $planHolderId)->delete();

            foreach ($beneficiaries as $beneficiary) {
                $beneficiaryModel->insert($beneficiary);
            }

            // Create plan record
            $planModel = new \App\Models\PlanModel();
            $existingPlan = $planModel
                ->where('plan_holder_id', $planHolderId)
                ->first();

            if (! $existingPlan) {
                $planModel->insert([
                    'plan_holder_id' => $planHolderId,
                    'package_id' => $planId,
                    'registration_date' => date('Y-m-d'),
                    'status' => 'inactive',
                ], true);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed. Please try again.');
            }

            return redirect()->to('/branch-admin/client/view/' . $planHolderId)->with('success', 'Plan holder registered successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Plan registration failed: ' . $e->getMessage());
        }
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
