<?php

namespace App\Controllers;

use App\Config\ValidationRules;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\IdVerificationService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use App\Services\RegistrationWizardService;
use App\Services\SecurityEnhancementService;
use CodeIgniter\HTTP\ResponseInterface;

class PlanHolders extends BaseController
{
    public function register()
    {
        // Only Admin (role 1) and BranchAdmin (role 2) can register plan holders
        $roleId = (int) session('role_id');
        if (!in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized')->with('error', 'You do not have permission to register plan holders.');
        }

        $db = db_connect();
        $requestedTab = (string) $this->request->getGet('tab');
        $activeTab = in_array($requestedTab, ['registration', 'approvals'], true) ? $requestedTab : 'registration';

        $existingUsers = $db->table('users u')
            ->select('u.user_id, u.username, u.email, u.first_name, u.middle_name, u.last_name, u.contact_number')
            ->join('plan_holders ph', 'ph.user_id = u.user_id', 'left')
            ->where('u.role_id', 4)
            ->where('ph.plan_holder_id IS NULL', null, false)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();

        // Approvals tab keeps the legacy tabbed view (its registration panel stays
        // hidden because active_tab=approvals), preserving the verification queue.
        if ($activeTab === 'approvals') {
            $approvalRegistrations = [];
            $hasPendingTable = $this->pendingTableExists();
            $canReviewApprovals = in_array($roleId, [1, 2], true);
            if ($canReviewApprovals && $hasPendingTable) {
                $approvalRegistrations = $db->table('pending_plan_holder_registrations pr')
                    ->select('pr.*, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name, reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name')
                    ->join('users u', 'u.user_id = pr.user_id', 'left')
                    ->join('branches b', 'b.branch_id = pr.branch_id', 'left')
                    ->join('users reviewer', 'reviewer.user_id = pr.reviewed_by', 'left')
                    ->orderBy('pr.pending_registration_id', 'DESC')
                    ->get()
                    ->getResultArray();
            }

            return view('plan_holders/register', [
                'branches' => [],
                'existing_users' => $existingUsers,
                'active_tab' => $activeTab,
                'can_review_approvals' => $canReviewApprovals,
                'has_pending_table' => $hasPendingTable,
                'approval_registrations' => $approvalRegistrations,
                'role_layout' => $this->resolveLayoutView(),
            ]);
        }

        // Registration tab — shared staff-assisted wizard (same 4-step flow as the client).
        $branchQuery = $db->table('branches')
            ->select('branch_id, branch_name')
            ->where('status', 'active');

        if ($roleId === 2) {
            $branchQuery->where('branch_id', (int) session('branch_id'));
        }

        $branches = $branchQuery->orderBy('branch_name', 'ASC')->get()->getResultArray();

        // Coordinator candidates = staff (role 3) and branch admins (role 2).
        $coordinators = $db->table('users')
            ->select('user_id, first_name, middle_name, last_name, name_extension')
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()
            ->getResultArray();

        $program = MembershipService::getProgramInfo();

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
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => null,
            'form_action' => base_url('plan-holders/store'),
            'show_account_mode' => true,
            'is_client' => false,
            'approvals_url' => base_url('plan-holders/register?tab=approvals'),
        ]);
    }

    public function store()
    {
        // Only Admin (role 1) and BranchAdmin (role 2) can register plan holders
        $roleId = (int) session('role_id');
        if (!in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized')->with('error', 'You do not have permission to register plan holders.');
        }

        $mode = (string) $this->request->getPost('registration_mode');

        // Validate registration mode
        if (!in_array($mode, ['existing', 'new'], true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid registration mode selected.');
        }

        $branchId = (int) $this->request->getPost('branch_id');
        $createdBy = (int) session('user_id');

        if ($branchId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Branch selection is required.');
        }

        // Branch admins can only register clients for their own branch.
        if ($roleId === 2) {
            $sessionBranchId = (int) session('branch_id');
            if ($sessionBranchId <= 0 || $branchId !== $sessionBranchId) {
                return redirect()->back()->withInput()->with('error', 'You can only register clients for your own branch.');
            }
        }

        // Add security checks + rate limiting
        $securityService = new SecurityEnhancementService();
        $userId = (int) session('user_id');

        $rateCheck = $securityService->checkRegistrationAttempts("user_{$userId}");
        if (!$rateCheck['allowed']) {
            $securityService->logSecurityEvent('RATE_LIMIT_EXCEEDED', $userId, "Registration attempts exceeded");
            return redirect()->back()->withInput()->with('error', $rateCheck['message']);
        }

        // Resolve the selected program plan (falls back to the active program package).
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
                'admin_' . $branchId
            );

            $userModel = new UserModel();

            // Resolve or create the plan-holder account.
            if ($mode === 'existing') {
                $targetUserId = (int) $this->request->getPost('user_id');
                $existingUser = $userModel->where('user_id', $targetUserId)->where('role_id', 4)->first();
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

                $targetUserId = (int) $userModel->insert([
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

                if ($targetUserId <= 0) {
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
                'user_id' => $targetUserId,
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

            $planHolderModel = new PlanHolderModel();
            $existingHolder = $planHolderModel
                ->where('user_id', $targetUserId)
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
                        ->where('user_id', $targetUserId)
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
            $userModel->update($targetUserId, [
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

            // Clear rate limiting on successful registration
            $securityService->clearRegistrationAttempts("user_{$createdBy}");
            $securityService->logSecurityEvent('REGISTRATION_SUCCESS', $targetUserId, "Registered plan holder (mode: {$mode}) by user {$createdBy}");

            // Notify + log activity
            (new NotificationService())->notify(
                $targetUserId,
                'Your account was registered as a plan holder. The initial payment must be recorded before the membership is activated.',
                'registration_pending'
            );

            (new ActivityLogService())->log(
                $createdBy,
                'created',
                'plan_holder',
                (int) $planHolderId,
                'Registered plan holder from registration wizard',
                null,
                ['branch_id' => $branchId]
            );

            $successRedirect = $roleId === 2 ? '/payment-tracking?tab=initial' : '/admin/payment-monitoring';
            return redirect()->to($successRedirect)->with('success', 'Plan holder registration completed successfully. Record the initial payment to activate the membership.');
        } catch (\Throwable $e) {
            $securityService->recordRegistrationAttempt("user_{$createdBy}");
            return redirect()->back()->withInput()->with('error', 'Plan registration failed: ' . $e->getMessage());
        }
    }

    public function registrationForm()
    {
        $userId = (int) session('user_id');
        $roleId = (int) session('role_id');
        $alreadyRegistered = false;
        $alreadyProfiledWithoutPlan = false;

        if ($roleId !== 4 || $userId <= 0) {
            return redirect()->to('/unauthorized');
        }

        $hasProfile = ((int) session('is_plan_holder') === 1 || $this->hasExistingPlanHolder($userId));
        $hasPlan = $this->hasExistingPlanForUser($userId);

        if ($hasProfile) {
            (new UserModel())->update($userId, ['is_plan_holder' => 1]);
            session()->set('is_plan_holder', 1);
            $alreadyRegistered = $hasPlan;
            $alreadyProfiledWithoutPlan = ! $hasPlan;
        }

        if (! $this->pendingTableExists()) {
            return redirect()->to('/dashboard/plan-holder')->with('error', 'Plan holder registration queue is not available yet. Please run database migrations.');
        }

        $db = db_connect();
        $branches = $db->table('branches')
            ->select('branch_id, branch_name')
            ->where('status', 'active')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();

        $user = (new UserModel())->find($userId);
        $pendingRegistration = $db->table('pending_plan_holder_registrations')
            ->where('user_id', $userId)
            ->orderBy('pending_registration_id', 'DESC')
            ->get()
            ->getRowArray();

        return view('plan_holder/registration', [
            'branches' => $branches,
            'user' => $user,
            'pending_registration' => $pendingRegistration,
            'already_registered' => $alreadyRegistered,
            'already_profiled_without_plan' => $alreadyProfiledWithoutPlan,
            'role_layout' => 'layouts/plan_holder',
        ]);
    }

    public function submitRegistration()
    {
        $userId = (int) session('user_id');
        $roleId = (int) session('role_id');

        if ($roleId !== 4 || $userId <= 0) {
            return redirect()->to('/unauthorized');
        }

        $hasProfile = ((int) session('is_plan_holder') === 1 || $this->hasExistingPlanHolder($userId));
        if ($hasProfile) {
            (new UserModel())->update($userId, ['is_plan_holder' => 1]);
            session()->set('is_plan_holder', 1);

            if ($this->hasExistingPlanForUser($userId)) {
                return redirect()->to('/dashboard/plan-holder')->with('success', 'Your Damayan Burial Program plan is already registered.');
            }

            return redirect()->to('/dashboard/plan-holder')->with('error', 'Your account is already linked to a plan holder profile, but no Damayan Burial Program plan is assigned yet. Please contact your branch admin.');
        }

        if (! $this->pendingTableExists()) {
            return redirect()->to('/dashboard/plan-holder')->with('error', 'Plan holder registration queue is not available yet. Please run database migrations.');
        }

        $rules = [
            'branch_id' => 'required|is_natural_no_zero',
            'address_no' => 'permit_empty|max_length[20]',
            'address_street' => 'permit_empty|max_length[100]',
            'address_barangay' => 'permit_empty|max_length[100]',
            'address_city' => 'permit_empty|max_length[100]',
            'date_of_birth' => 'permit_empty|valid_date[Y-m-d]',
            'place_of_birth' => 'permit_empty|max_length[100]',
            'age' => 'permit_empty|integer',
            'gender' => 'permit_empty|max_length[10]',
            'civil_status' => 'permit_empty|max_length[20]',
            'citizenship' => 'permit_empty|max_length[50]',
            'height' => 'permit_empty|decimal',
            'weight' => 'permit_empty|decimal',
            'spouse_name' => 'permit_empty|max_length[100]',
            'spouse_birthdate' => 'permit_empty|valid_date[Y-m-d]',
            'spouse_occupation' => 'permit_empty|max_length[100]',
            'senior_citizen_id' => 'permit_empty|max_length[50]',
            'organization_affiliation' => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $existingPending = $db->table('pending_plan_holder_registrations')
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->get()
                ->getRowArray();

            if ($existingPending) {
                throw new \RuntimeException('Your registration is already pending approval. Please wait for review.');
            }

            $saved = $db->table('pending_plan_holder_registrations')->insert([
                'user_id' => $userId,
                'branch_id' => (int) $this->request->getPost('branch_id'),
                'address_no' => trim((string) $this->request->getPost('address_no')),
                'address_street' => trim((string) $this->request->getPost('address_street')),
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
                'spouse_name' => trim((string) $this->request->getPost('spouse_name')),
                'spouse_birthdate' => $this->nullablePost('spouse_birthdate'),
                'spouse_occupation' => trim((string) $this->request->getPost('spouse_occupation')),
                'senior_citizen_id' => trim((string) $this->request->getPost('senior_citizen_id')),
                'organization_affiliation' => trim((string) $this->request->getPost('organization_affiliation')),
                'status' => 'pending',
            ]);

            if (! $saved) {
                throw new \RuntimeException('Unable to submit your plan holder registration.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            (new NotificationService())->notify($userId, 'Your plan holder registration was submitted and is pending approval.', 'registration_pending');
            $this->notifyReviewersForPendingRegistration((int) $this->request->getPost('branch_id'), $userId);

            return redirect()->to('/dashboard/plan-holder')->with('success', 'Registration submitted successfully. Please wait for approval.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approvals(): ResponseInterface|string
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized');
        }

        if ($roleId === 1) {
            return redirect()->to('/plan-holders/register?tab=approvals');
        }

        if (! $this->pendingTableExists()) {
            return redirect()->back()->with('error', 'Plan holder registration queue is not available yet. Please run database migrations.');
        }

        $db = db_connect();
        $builder = $db->table('pending_plan_holder_registrations pr')
            ->select('pr.*, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name, reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name')
            ->join('users u', 'u.user_id = pr.user_id', 'left')
            ->join('branches b', 'b.branch_id = pr.branch_id', 'left')
            ->join('users reviewer', 'reviewer.user_id = pr.reviewed_by', 'left')
            ->orderBy('pr.pending_registration_id', 'DESC');

        if ($roleId === 2) {
            $builder->where('pr.branch_id', (int) session('branch_id'));
        }

        $registrations = $builder->get()->getResultArray();

        return view('plan_holders/approvals', [
            'role_layout' => $this->resolveLayoutView(),
            'registrations' => $registrations,
        ]);
    }

    public function approve(int $pendingRegistrationId)
    {
        return $this->handleApprovalDecision($pendingRegistrationId, 'approved');
    }

    public function reject(int $pendingRegistrationId)
    {
        return $this->handleApprovalDecision($pendingRegistrationId, 'rejected');
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

    private function hasExistingPlanHolder(int $userId): bool
    {
        return (new PlanHolderModel())->where('user_id', $userId)->first() !== null;
    }

    private function hasExistingPlanForUser(int $userId): bool
    {
        return db_connect()->table('plans p')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('ph.user_id', $userId)
            ->countAllResults() > 0;
    }

    private function notifyReviewersForPendingRegistration(int $branchId, int $requesterUserId): void
    {
        if ($branchId <= 0 || $requesterUserId <= 0) {
            return;
        }

        $db = db_connect();

        $requester = $db->table('users')
            ->select('first_name, last_name')
            ->where('user_id', $requesterUserId)
            ->get()
            ->getRowArray();

        $requesterName = trim((string) ($requester['first_name'] ?? '') . ' ' . (string) ($requester['last_name'] ?? ''));
        if ($requesterName === '') {
            $requesterName = 'A client';
        }

        $reviewers = $db->table('users')
            ->select('user_id')
            ->groupStart()
                ->where('role_id', 1)
                ->orGroupStart()
                    ->where('role_id', 2)
                    ->where('branch_id', $branchId)
                ->groupEnd()
            ->groupEnd()
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        foreach ($reviewers as $reviewer) {
            $reviewerId = (int) ($reviewer['user_id'] ?? 0);
            if ($reviewerId <= 0) {
                continue;
            }

            (new NotificationService())->notify($reviewerId, $requesterName . ' submitted a plan holder registration request for approval.', 'registration_pending');
        }
    }

    private function handleApprovalDecision(int $pendingRegistrationId, string $decision)
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized');
        }

        if (! $this->pendingTableExists()) {
            return redirect()->back()->with('error', 'Plan holder registration queue is not available yet. Please run database migrations.');
        }

        $db = db_connect();
        $query = $db->table('pending_plan_holder_registrations')
            ->where('pending_registration_id', $pendingRegistrationId);

        if ($roleId === 2) {
            $query->where('branch_id', (int) session('branch_id'));
        }

        $pending = $query->get()->getRowArray();
        if (! $pending) {
            return redirect()->back()->with('error', 'Registration request was not found.');
        }

        if (($pending['status'] ?? 'pending') !== 'pending') {
            return redirect()->back()->with('error', 'This registration request has already been reviewed.');
        }

        $db->transBegin();

        try {
            if ($decision === 'approved') {
                $userId = (int) ($pending['user_id'] ?? 0);

                if (! $this->hasExistingPlanHolder($userId)) {
                    $saved = (new PlanHolderModel())->insert([
                        'user_id' => $userId,
                        'branch_id' => (int) ($pending['branch_id'] ?? 0),
                        'unique_identifier' => $this->generateUniqueIdentifier($userId),
                        'address_no' => (string) ($pending['address_no'] ?? ''),
                        'address_street' => (string) ($pending['address_street'] ?? ''),
                        'address_barangay' => (string) ($pending['address_barangay'] ?? ''),
                        'address_city' => (string) ($pending['address_city'] ?? ''),
                        'date_of_birth' => $pending['date_of_birth'] ?? null,
                        'place_of_birth' => (string) ($pending['place_of_birth'] ?? ''),
                        'age' => $pending['age'] === null ? null : (int) $pending['age'],
                        'gender' => (string) ($pending['gender'] ?? ''),
                        'civil_status' => (string) ($pending['civil_status'] ?? ''),
                        'citizenship' => (string) ($pending['citizenship'] ?? ''),
                        'height' => $pending['height'] ?? null,
                        'weight' => $pending['weight'] ?? null,
                        'spouse_name' => (string) ($pending['spouse_name'] ?? ''),
                        'spouse_birthdate' => $pending['spouse_birthdate'] ?? null,
                        'spouse_occupation' => (string) ($pending['spouse_occupation'] ?? ''),
                        'senior_citizen_id' => (string) ($pending['senior_citizen_id'] ?? ''),
                        'organization_affiliation' => (string) ($pending['organization_affiliation'] ?? ''),
                        'status' => 'active',
                        'is_linked_account' => 1,
                    ]);

                    if (! $saved) {
                        throw new \RuntimeException('Failed to create plan holder record during approval.');
                    }
                }

                (new UserModel())->update($userId, [
                    'is_plan_holder' => 1,
                    'branch_id' => (int) ($pending['branch_id'] ?? 0),
                    'account_status' => 'verified',
                ]);

                (new NotificationService())->notify($userId, 'Your plan holder registration was approved. You can now apply for services and manage payments.', 'registration_pending');
                (new ActivityLogService())->log((int) session('user_id'), 'approved', 'plan_holder', $pendingRegistrationId, 'Approved plan holder registration', ['status' => 'pending'], ['status' => 'approved']);
            } else {
                $rejectionNotes = trim((string) $this->request->getPost('rejection_notes'));
                (new NotificationService())->notify((int) ($pending['user_id'] ?? 0), 'Your plan holder registration was rejected. ' . ($rejectionNotes !== '' ? 'Reason: ' . $rejectionNotes : 'Please update your details and submit again.'), 'registration_pending');
                (new ActivityLogService())->log((int) session('user_id'), 'rejected', 'plan_holder', $pendingRegistrationId, 'Rejected plan holder registration', ['status' => 'pending'], ['status' => 'rejected']);
            }

            $updatePayload = [
                'status' => $decision,
                'reviewed_by' => (int) session('user_id'),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ];

            if ($decision === 'rejected') {
                $updatePayload['rejection_notes'] = trim((string) $this->request->getPost('rejection_notes'));
            }

            $db->table('pending_plan_holder_registrations')
                ->where('pending_registration_id', $pendingRegistrationId)
                ->update($updatePayload);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return redirect()->back()->with('success', 'Registration request ' . $decision . ' successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function pendingTableExists(): bool
    {
        return db_connect()->tableExists('pending_plan_holder_registrations');
    }

    private function generateUniqueIdentifier(int $userId): string
    {
        $planHolderModel = new PlanHolderModel();
        $seed = 'PH-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT) . '-' . date('Ymd');
        $candidate = $seed;
        $counter = 1;

        while ($planHolderModel->where('unique_identifier', $candidate)->first() !== null) {
            $candidate = $seed . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $candidate;
    }
}
