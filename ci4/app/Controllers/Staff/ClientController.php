<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Config\ValidationRules;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\ClientService;
use App\Services\IdVerificationService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use App\Services\RegistrationWizardService;
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
        $branchIssue = null;

        if ($branchId <= 0) {
            $clients = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $clients = $this->clientService->getClientsByBranch($branchId);
        }

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

        return view('staff/clients/index', [
            'clients' => $clients,
            'program' => MembershipService::getProgramInfo(),
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
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

        return view('staff/clients/edit', [
            'client' => $client,
            'role_layout' => 'layouts/staff',
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

        return redirect()->to('/staff/client/view/' . $id)->with('success', 'Client information updated successfully.');
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

        return view('staff/clients/view', [
            'client' => $client,
            'payments' => $payments,
            'services' => $services,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function create(): string
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = db_connect();
        $program = MembershipService::getProgramInfo();

        // Client accounts (role 4) that do not yet have a plan holder — for the
        // "link existing account" mode of the staff-assisted wizard.
        $existingUsers = $db->table('users u')
            ->select('u.user_id, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number')
            ->join('plan_holders ph', 'ph.user_id = u.user_id', 'left')
            ->where('u.role_id', 4)
            ->where('ph.plan_holder_id IS NULL', null, false)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();

        // Branch dropdown scoped to the session branch.
        $branches = $db->table('branches')
            ->select('branch_id, branch_name')
            ->where('status', 'active')
            ->where('branch_id', $branchId)
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();

        // Coordinator candidates = staff (role 3) and branch admins (role 2).
        $coordinators = $db->table('users')
            ->select('user_id, first_name, middle_name, last_name, name_extension')
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('registration/wizard', [
            'plan_holder' => [],
            'user' => [],
            'beneficiaries' => [],
            'branches' => $branches,
            'coordinators' => $coordinators,
            'id_types' => IdVerificationService::supportedIds(),
            'existing_users' => $existingUsers,
            'program' => $program,
            'plan_id' => (int) ($program['package_id'] ?? 0),
            'role_layout' => 'layouts/staff',
            'page_title' => null,
            'form_action' => base_url('staff/client/store'),
            'show_account_mode' => true,
            'is_client' => false,
        ]);
    }

    public function store()
    {
        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            return redirect()->back()->with('error', 'Branch information is missing.');
        }

        // Account mode: link an existing role-4 account or create a new one.
        $mode = (string) $this->request->getPost('registration_mode');
        if (! in_array($mode, ['existing', 'new'], true)) {
            $mode = 'new';
        }

        $planId = (int) $this->request->getPost('plan_id');
        if ($planId <= 0) {
            $planId = (int) $this->request->getPost('package_id');
        }
        if ($planId <= 0) {
            $planId = (int) (MembershipService::getProgramInfo()['package_id'] ?? 0);
        }
        if ($planId <= 0) {
            return redirect()->back()->with('error', 'Selected plan is unavailable.');
        }

        // Validate required fields using centralized rules (+ conditional spouse).
        $civilStatus = trim((string) $this->request->getPost('civil_status'));
        $rules = ValidationRules::getPlanRegistrationRules();
        $rules = array_merge($rules, RegistrationWizardService::spouseRules($civilStatus));

        if ($mode === 'existing') {
            $rules['user_id'] = 'required|is_natural_no_zero';
        } else {
            $rules['username'] = 'required|is_unique[users.username]|max_length[50]';
            $rules['password'] = 'required|min_length[8]|max_length[72]';
            $rules['password_confirm'] = 'required|matches[password]';
        }

        $messages = ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // DOB sanity (not in the future, age <= 150)
        $dobError = RegistrationWizardService::validateDob(trim((string) $this->request->getPost('date_of_birth')));
        if ($dobError !== null) {
            return redirect()->back()->withInput()->with('errors', ['date_of_birth' => $dobError]);
        }

        // Server-side beneficiary validation
        $beneficiariesInput = $this->request->getPost('beneficiaries');
        $beneficiariesInput = is_array($beneficiariesInput) ? $beneficiariesInput : [];
        $beneficiaryCheck = RegistrationWizardService::validateBeneficiaries($beneficiariesInput);
        if (! empty($beneficiaryCheck['errors'])) {
            return redirect()->back()->withInput()->with('errors', $beneficiaryCheck['errors']);
        }

        // Coordinator must be a real, active staff/branch-admin user
        $coordinatorUserId = (int) $this->request->getPost('coordinator_user_id');
        $coordinatorUser = RegistrationWizardService::resolveCoordinator($coordinatorUserId);
        if (! $coordinatorUser) {
            return redirect()->back()->withInput()->with('errors', ['coordinator_user_id' => 'Please select a valid coordinator.']);
        }
        $coordinatorName = RegistrationWizardService::coordinatorName($coordinatorUser);

        try {
            $db = db_connect();
            $db->transStart();

            // Step 3 government ID verification (Level 1 + Level 2) — server-side re-check.
            $idVerification = RegistrationWizardService::processIdVerification(
                $this->request->getFile('valid_id'),
                [
                    'first_name' => trim((string) $this->request->getPost('first_name')),
                    'middle_name' => trim((string) $this->request->getPost('middle_name')),
                    'last_name' => trim((string) $this->request->getPost('last_name')),
                    'date_of_birth' => trim((string) $this->request->getPost('date_of_birth')),
                    'address' => trim(implode(' ', array_filter([
                        (string) $this->request->getPost('address_street'),
                        (string) $this->request->getPost('address_barangay'),
                        (string) $this->request->getPost('address_city'),
                    ], static fn ($value): bool => $value !== ''))),
                ],
                (string) $this->request->getPost('ocr_text'),
                (string) $this->request->getPost('id_type'),
                (string) $this->request->getPost('id_number'),
                'staff_' . $branchId
            );

            $userModel = new UserModel();

            // Resolve or create the plan-holder account.
            if ($mode === 'existing') {
                $userId = (int) $this->request->getPost('user_id');
                $existingUser = $userModel->where('user_id', $userId)->where('role_id', 4)->first();
                if (! $existingUser) {
                    throw new \RuntimeException('The selected existing account is not a valid plan-holder account.');
                }
                $firstName = trim((string) ($existingUser['first_name'] ?? ''));
                $middleName = trim((string) ($existingUser['middle_name'] ?? ''));
                $lastName = trim((string) ($existingUser['last_name'] ?? ''));
            } else {
                $firstName = trim((string) $this->request->getPost('first_name'));
                $middleName = trim((string) $this->request->getPost('middle_name'));
                $lastName = trim((string) $this->request->getPost('last_name'));

                $userId = (int) $userModel->insert([
                    'username' => trim((string) $this->request->getPost('username')),
                    'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'email' => trim((string) $this->request->getPost('email')),
                    'contact_number' => trim((string) $this->request->getPost('contact_number')),
                    'role_id' => 4, // Plan holder
                    'status' => 'active',
                    'account_status' => 'pending', // pending until initial payment is verified
                ], true);

                if ($userId <= 0) {
                    throw new \RuntimeException('Unable to create user account.');
                }
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
                'coordinator' => $coordinatorName,
                'coordinator_user_id' => $coordinatorUserId,
                'id_document_path' => $idVerification['path'],
                'id_type' => $idVerification['type'],
                'id_number' => $idVerification['number'],
                'id_match_score' => $idVerification['score'],
                'id_verification_status' => 'pending',
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
                $planHolderData['unique_identifier'] = strtoupper(preg_replace('/\s+/', '', $lastName))
                    . '-' . strtoupper(preg_replace('/\s+/', '', $firstName))
                    . '-' . substr((string) time(), -6);

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

            // Insert beneficiaries (validated above) — replaces existing rows.
            RegistrationWizardService::insertBeneficiaries($beneficiaryCheck['rows'], $planHolderId);

            // Create plan record (inactive until the initial payment is verified)
            RegistrationWizardService::createInactivePlan($planHolderId);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed. Please try again.');
            }

            // Notify + log activity
            (new NotificationService())->notify(
                $userId,
                'Your account was registered as a plan holder. The initial payment must be recorded before the membership is activated.',
                'registration_pending'
            );

            (new ActivityLogService())->log(
                (int) session('user_id'),
                'created',
                'plan_holder',
                (int) $planHolderId,
                'Registered plan holder from staff registration wizard',
                null,
                ['branch_id' => $branchId]
            );

            return redirect()->to('/staff/client/view/' . $planHolderId)->with('success', 'Plan holder registered successfully! Record the initial payment to activate the membership.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Plan registration failed: ' . $e->getMessage());
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
