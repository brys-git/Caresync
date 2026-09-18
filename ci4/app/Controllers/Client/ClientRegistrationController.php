<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use App\Config\ValidationRules;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\BranchModel;
use App\Models\PlanHolderModel;
use App\Models\PlanModel;
use App\Services\MembershipService;
use App\Services\PsgcService;
use App\Services\GovernmentIdVerificationService;

/**
 * ClientRegistrationController
 * 
 * Handles plan holder registration flow
 * Part of the refactored ClientPortal controller
 * 
 * Uses centralized validation rules to reduce code duplication
 */
class ClientRegistrationController extends BaseController
{
    use ClientPortalTrait;

    /**
     * Display plan information before registration
     */
    public function planInfo(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        if (($access['state'] ?? 'unregistered') === 'active') {
            return redirect()->to('/client/dashboard');
        }

        if (($access['state'] ?? 'unregistered') === 'awaiting_activation') {
            return redirect()->to('/initial-payment');
        }

        $program = MembershipService::getProgramInfo();

        $db = db_connect();

        // The Regular Wood Casket (Damayan entitlement) and its inclusions -
        // same data the Services & Packages page uses, reused here so a
        // prospective plan holder sees accurate figures before registering
        // instead of the old generic/static benefits list.
        $entitlementPackage = $db->table('packages')
            ->select('package_id, package_name, base_price')
            ->where('is_damayan_entitlement', 1)
            ->orderBy('package_id', 'ASC')
            ->get()
            ->getRowArray();

        $inclusions = [];
        if ($entitlementPackage) {
            $inclusions = $db->table('package_items')
                ->select('item_id, item_name, description')
                ->where('package_id', (int) $entitlementPackage['package_id'])
                ->orderBy('item_id', 'ASC')
                ->get()
                ->getResultArray();
        }

        $otherPackages = $db->table('packages')
            ->select('package_id, package_name, base_price')
            ->where('is_damayan_entitlement', 0)
            ->where('is_available', 1)
            ->orderBy('base_price', 'ASC')
            ->get()
            ->getResultArray();

        $services = $db->table('service_list')
            ->select('service_list_id, service_name, description')
            ->where('is_available', 1)
            ->orderBy('service_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('client/plan_info', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Plan Information',
            'page_sub' => 'Review the ' . (string) ($program['name'] ?? 'Damayan Burial Program') . ' details before proceeding.',
            'access' => $access,
            'program' => $program,
            'entitlement_package' => $entitlementPackage,
            'inclusions' => $inclusions,
            'other_packages' => $otherPackages,
            'services' => $services,
            'monthly_fee' => MembershipService::MONTHLY_FEE,
            'total_contribution' => MembershipService::TOTAL_CONTRIBUTION,
        ]);
    }

    /**
     * Display plan registration form
     */
    public function planRegistration(int $planId = 0): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        if (! $user) {
            return redirect()->to('/signin')->with('error', 'You must be logged in to register.');
        }

        if (($access['state'] ?? 'unregistered') === 'active') {
            return redirect()->to('/client/dashboard');
        }

        if (($access['state'] ?? 'unregistered') === 'awaiting_activation') {
            return redirect()->to('/initial-payment');
        }

        $program = MembershipService::getProgramInfo();
        $planId = $planId > 0 ? $planId : (int) ($program['package_id'] ?? 0);

        $branches = (new BranchModel())
            ->orderBy('branch_name', 'ASC')
            ->findAll();

        $currentUser = $access['user'];
        $planHolder = $access['plan_holder'] ?? [];

        $idVerificationService = new GovernmentIdVerificationService();

        return view('client/plan_registration', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => (string) ($program['name'] ?? 'Damayan Burial Program'),
            'page_sub' => 'Plan registration - complete each step, then review before submitting.',
            'access' => $access,
            'program' => $program,
            'plan_id' => $planId,
            'branches' => $branches,
            'plan_holder' => $planHolder,
            'user' => $currentUser,
            'user_email' => $currentUser['email'] ?? '',
            'user_phone' => $currentUser['contact_number'] ?? '',
            'id_types' => $idVerificationService->idTypes(),
            'latest_verification' => $idVerificationService->latestForUser((int) $currentUser['user_id']),
        ]);
    }

    /**
     * Submit plan registration
     */
    public function submitPlanRegistration(int $planId = 0)
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        if (! $user) {
            return redirect()->to('/signin')->with('error', 'You must be logged in to register.');
        }

        if (($access['state'] ?? 'unregistered') === 'active') {
            return redirect()->to('/client/dashboard')->with('info', 'You are already registered.');
        }

        if (($access['state'] ?? 'unregistered') === 'awaiting_activation') {
            return redirect()->to('/initial-payment')->with('info', 'Complete your initial payment to continue.');
        }

        if ($planId <= 0) {
            $planId = (int) $this->request->getPost('plan_id');
        }

        if ($planId <= 0) {
            return redirect()->back()->with('error', 'Selected plan is unavailable.');
        }

        $program = MembershipService::getProgramInfo();
        if ($planId <= 0) {
            $planId = (int) ($program['package_id'] ?? 0);
        }

        if ($planId <= 0) {
            return redirect()->back()->with('error', 'Selected plan is unavailable.');
        }

        // Validate required fields using centralized rules
        $rules = ValidationRules::getPlanRegistrationRules();
        $messages = ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // The wizard's Government ID step only gates "Next" client-side -
        // never trust that alone. Require a real verification attempt to
        // exist for this user, that it actually passed (or needs only a
        // staff look, not a hard mismatch), and that nothing about the
        // applicant's identity changed after it ran (e.g. editing the
        // name/DOB/sex fields post-verification and submitting under a
        // different identity than what was checked).
        $idVerificationService = new GovernmentIdVerificationService();
        $idVerification = $idVerificationService->latestForUser((int) $user['user_id']);

        if ($idVerification === null) {
            return redirect()->back()->withInput()
                ->with('error', 'Please complete the Government ID Verification step before submitting.');
        }

        $submittedIdentity = [
            'first_name'    => trim((string) $this->request->getPost('first_name')),
            'middle_name'   => trim((string) $this->request->getPost('middle_name')),
            'last_name'     => trim((string) $this->request->getPost('last_name')),
            'date_of_birth' => trim((string) $this->request->getPost('date_of_birth')),
            'gender'        => trim((string) $this->request->getPost('gender')),
        ];

        if (! $idVerificationService->isValidForSubmission($idVerification, $submittedIdentity)) {
            $message = (string) ($idVerification['verification_status'] ?? '') === 'failed'
                ? 'Your Government ID does not match the details you entered. Please correct your details or upload the correct ID.'
                : 'You changed your name, birth date, or sex after verifying your ID. Please verify your ID again.';

            return redirect()->back()->withInput()->with('error', $message);
        }

        // PSGC Cloud address validation: the browser only ever sends codes
        // that came from our own /api/address/* endpoints, but a submitted
        // code pair is never trusted at face value - re-verify against
        // PSGC Cloud (via the same cached PsgcService) that the city code
        // is real and the barangay code actually belongs to it. The
        // human-readable names saved below come from PSGC's own data for
        // the matched codes, not from whatever text the browser sent.
        $cityCode = trim((string) $this->request->getPost('city_municipality_code'));
        $barangayCode = trim((string) $this->request->getPost('barangay_code'));

        $psgc = new PsgcService();
        $cities = $psgc->getCities();
        if ($cities === null) {
            return redirect()->back()->withInput()
                ->with('error', 'Unable to verify the selected address right now. Please try again.');
        }

        $cityMatch = null;
        foreach ($cities as $city) {
            if ($city['code'] === $cityCode) {
                $cityMatch = $city;
                break;
            }
        }

        if ($cityMatch === null) {
            return redirect()->back()->withInput()
                ->with('errors', ['city_municipality_code' => 'Please select a valid Town/City from the list.']);
        }

        $barangays = $psgc->getBarangaysForCity($cityCode);
        if ($barangays === null) {
            return redirect()->back()->withInput()
                ->with('error', 'Unable to verify the selected address right now. Please try again.');
        }

        $barangayMatch = null;
        foreach ($barangays as $barangay) {
            if ($barangay['code'] === $barangayCode) {
                $barangayMatch = $barangay;
                break;
            }
        }

        if ($barangayMatch === null) {
            return redirect()->back()->withInput()
                ->with('errors', ['barangay_code' => 'Please select a valid Barangay for the chosen Town/City.']);
        }

        $dateOfBirthInput = $this->nullablePost('date_of_birth');
        if ($dateOfBirthInput !== null && $dateOfBirthInput > date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'Date of birth cannot be in the future.');
        }

        try {
            $db = db_connect();
            $db->transStart();

            $planHolderData = [
                'user_id' => (int) $user['user_id'],
                'id_control_no' => trim((string) $this->request->getPost('id_control_no')),
                'coordinator' => trim((string) $this->request->getPost('coordinator')),
                'application_date' => $this->nullablePost('application_date'),
                'address_no' => trim((string) $this->request->getPost('address_no')),
                'address_street' => trim((string) $this->request->getPost('address_street')),
                'address_barangay' => $barangayMatch['name'],
                'barangay_code' => $barangayMatch['code'],
                'address_city' => $cityMatch['name'],
                'city_municipality_code' => $cityMatch['code'],
                'date_of_birth' => $dateOfBirthInput,
                'place_of_birth' => trim((string) $this->request->getPost('place_of_birth')),
                'age' => cs_age_from_dob($dateOfBirthInput),
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
                'emergency_contact_name' => trim((string) $this->request->getPost('emergency_contact_name')),
                'emergency_contact_number' => trim((string) $this->request->getPost('emergency_contact_number')),
                'emergency_contact_address' => trim((string) $this->request->getPost('emergency_contact_address')),
                'branch_id' => (int) $this->request->getPost('branch_id'),
                'status' => 'inactive',
            ];
            $planHolderData = $this->filterTableData('plan_holders', $planHolderData);

            $planHolderModel = new PlanHolderModel();
            $existingHolder = $planHolderModel
                ->where('user_id', (int) $user['user_id'])
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

                // Some drivers/environments may return 0 insert id even after a successful insert.
                if ($planHolderId <= 0) {
                    $insertedRow = $planHolderModel
                        ->where('user_id', (int) $user['user_id'])
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

            (new \App\Models\UserModel())->update((int) $user['user_id'], [
                'is_plan_holder' => 1,
                'branch_id' => (int) $this->request->getPost('branch_id'),
            ]);

            session()->set('is_plan_holder', 1);
            session()->set('access_state', 'awaiting_activation');

            $beneficiariesInput = $this->request->getPost('beneficiaries');
            $beneficiariesInput = is_array($beneficiariesInput) ? $beneficiariesInput : [];
            $beneficiaries = [];
            $isPrimary = true;
            $allowedRelationships = config(\Config\Beneficiary::class)->relationships;
            // Letters, spaces, and the punctuation real Filipino names use
            // (Dela Cruz, D'Souza, Añonuevo, Jr.-style names minus the comma).
            $namePattern = '/^[\p{L}\s.\'-]+$/u';

            foreach ($beneficiariesInput as $row) {
                $firstName = trim((string) ($row['first_name'] ?? ''));
                $middleName = trim((string) ($row['middle_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));
                $birthday = trim((string) ($row['birthday'] ?? ''));
                $relationship = trim((string) ($row['relationship'] ?? ''));
                $relationshipOther = trim((string) ($row['relationship_other'] ?? ''));

                // Skip completely empty rows.
                if ($firstName === '' && $middleName === '' && $lastName === '' && $birthday === '' && $relationship === '' && $relationshipOther === '') {
                    continue;
                }

                if ($firstName === '' || $lastName === '' || $relationship === '') {
                    throw new \RuntimeException('Each beneficiary needs a first name, last name, and relationship.');
                }

                if (mb_strlen($firstName) > 100 || ! preg_match($namePattern, $firstName)) {
                    throw new \RuntimeException('Beneficiary first name must be 100 characters or fewer and contain only letters.');
                }

                if ($middleName !== '' && (mb_strlen($middleName) > 100 || ! preg_match($namePattern, $middleName))) {
                    throw new \RuntimeException('Beneficiary middle name must be 100 characters or fewer and contain only letters.');
                }

                if (mb_strlen($lastName) > 100 || ! preg_match($namePattern, $lastName)) {
                    throw new \RuntimeException('Beneficiary last name must be 100 characters or fewer and contain only letters.');
                }

                if (! array_key_exists($relationship, $allowedRelationships)) {
                    throw new \RuntimeException('Please select a valid beneficiary relationship.');
                }

                if ($relationship === 'Other') {
                    if ($relationshipOther === '' || mb_strlen($relationshipOther) > 50) {
                        throw new \RuntimeException('Please specify the beneficiary relationship (50 characters or fewer).');
                    }
                    $relationship = $relationshipOther;
                }

                if ($birthday !== '' && $birthday > date('Y-m-d')) {
                    throw new \RuntimeException('Beneficiary birthday cannot be in the future.');
                }

                $beneficiaries[] = $this->filterTableData('beneficiaries', [
                    'plan_holder_id' => $planHolderId,
                    'first_name' => $firstName,
                    'middle_name' => $middleName !== '' ? $middleName : null,
                    'last_name' => $lastName,
                    'name_extension' => null,
                    'date_of_birth' => $birthday !== '' ? $birthday : null,
                    'relationship' => $relationship,
                    'is_primary' => $isPrimary ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $isPrimary = false;
            }

            if (empty($beneficiaries)) {
                throw new \RuntimeException('At least one beneficiary is required. Please provide name and relationship for the first beneficiary.');
            }

            $db->table('beneficiaries')
                ->where('plan_holder_id', $planHolderId)
                ->delete();
            $db->table('beneficiaries')->insertBatch($beneficiaries);

            $planModel = new PlanModel();
            $existingPlan = $planModel
                ->where('plan_holder_id', $planHolderId)
                ->orderBy('plan_id', 'DESC')
                ->first();

            if (! $existingPlan) {
                $packageData = $this->resolvePackageAndVersion();
                $monthlyFee = (float) ($program['monthly_fee'] ?? MembershipService::MONTHLY_FEE);

                $planData = $this->filterTableData('plans', [
                    'plan_holder_id' => $planHolderId,
                    'package_id' => $packageData['package_id'],
                    'monthly_fee' => $monthlyFee,
                    'passbook_fee' => 50,
                    'start_date' => date('Y-m-d'),
                    'status' => 'inactive',
                    'months_paid' => 0,
                    'remaining_balance' => $monthlyFee * 12,
                    'next_due_date' => null,
                    'payment_coverage_until' => null,
                    'membership_state' => 'inactive',
                    'overdue_months' => 0,
                    'version_id' => $packageData['version_id'],
                ]);

                $planModel->insert($planData);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed. Please try again.');
            }

            return redirect()->to('/initial-payment')
                ->with('success', 'Plan registration submitted successfully. Please make your initial payment to activate your membership.');

        } catch (\Throwable $e) {
            log_message('error', 'Plan registration failed: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    /**
     * Keep only columns that exist in the target table.
     */
    private function filterTableData(string $table, array $data): array
    {
        $db = db_connect();
        $fields = $db->getFieldNames($table);

        if (empty($fields)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($fields));
    }
}
