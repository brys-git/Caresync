<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use App\Services\IdVerificationService;
use App\Config\ValidationRules;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\BranchModel;
use App\Models\PlanHolderModel;
use App\Models\PlanModel;
use App\Models\UserModel;
use App\Services\MembershipService;

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

        return view('client/plan_info', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'program' => $program,
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

        // Coordinator candidates = real staff (role 3) and branch admins (role 2).
        $coordinators = db_connect()->table('users')
            ->select('user_id, first_name, middle_name, last_name, name_extension')
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('client/plan_registration', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'program' => $program,
            'plan_id' => $planId,
            'branches' => $branches,
            'plan_holder' => $planHolder,
            'user' => $currentUser,
            'user_email' => $currentUser['email'] ?? '',
            'user_phone' => $currentUser['contact_number'] ?? '',
            'coordinators' => $coordinators,
            'id_types' => IdVerificationService::supportedIds(),
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

        // ---- Step 1 validation (centralized rules + conditional spouse) ----
        $rules = ValidationRules::getPlanRegistrationRules();
        $civilStatus = trim((string) $this->request->getPost('civil_status'));
        if ($civilStatus === 'Married') {
            $rules['spouse_first_name'] = 'required|max_length[50]';
            $rules['spouse_last_name'] = 'required|max_length[50]';
        }
        $messages = ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // ---- DOB sanity: not in the future, age <= 150 ----
        $dob = trim((string) $this->request->getPost('date_of_birth'));
        if ($dob !== '') {
            $dobTs = strtotime($dob);
            if ($dobTs !== false && $dobTs > strtotime('today')) {
                return redirect()->back()->withInput()->with('errors', ['date_of_birth' => 'Date of birth cannot be in the future.']);
            }
            if ($dobTs !== false && $dobTs > 0) {
                $ageYears = (int) floor((time() - $dobTs) / (365.25 * 86400));
                if ($ageYears > 150) {
                    return redirect()->back()->withInput()->with('errors', ['date_of_birth' => 'Age cannot exceed 150 years.']);
                }
            }
        }

        // ---- Step 2 beneficiary validation (server-side, before any writes) ----
        $beneficiariesInput = $this->request->getPost('beneficiaries');
        $beneficiariesInput = is_array($beneficiariesInput) ? $beneficiariesInput : [];

        $beneficiaryErrors = [];
        $nonEmptyCount = 0;
        foreach ($beneficiariesInput as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $relationship = trim((string) ($row['relationship'] ?? ''));

            if ($name === '' && $firstName === '' && $lastName === '') {
                continue;
            }
            $nonEmptyCount++;

            if ($firstName === '' && $lastName === '') {
                $beneficiaryErrors[] = 'Beneficiary row ' . ($i + 1) . ': last name and given name are required.';
            }
            if ($relationship === '') {
                $beneficiaryErrors[] = 'Beneficiary row ' . ($i + 1) . ': relationship is required.';
            }
        }
        if ($nonEmptyCount === 0) {
            $beneficiaryErrors[] = 'At least one beneficiary is required.';
        }
        if (! empty($beneficiaryErrors)) {
            return redirect()->back()->withInput()->with('errors', $beneficiaryErrors);
        }

        // ---- Coordinator must be a real, active staff/branch-admin user ----
        $coordinatorUserId = (int) $this->request->getPost('coordinator_user_id');
        $coordinatorUser = db_connect()->table('users')
            ->select('user_id, first_name, middle_name, last_name, name_extension')
            ->where('user_id', $coordinatorUserId)
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if (! $coordinatorUser) {
            return redirect()->back()->withInput()->with('errors', ['coordinator_user_id' => 'Please select a valid coordinator.']);
        }
        $coordinatorName = trim(implode(' ', array_filter([
            (string) ($coordinatorUser['first_name'] ?? ''),
            (string) ($coordinatorUser['middle_name'] ?? ''),
            (string) ($coordinatorUser['last_name'] ?? ''),
        ], static fn (string $value): bool => $value !== '')));

        try {
            // ---- Step 3 government ID verification (Level 1 + Level 2) ----
            // Runs before the DB transaction; moves the file securely on success.
            $idVerification = $this->processIdVerification($user);
            $verificationDocument = $idVerification['document'];

            $db = db_connect();
            $db->transStart();

            $spouseName = trim((string) $this->request->getPost('spouse_name'));
            if ($spouseName === '') {
                $spouseFirstName = trim((string) $this->request->getPost('spouse_first_name'));
                $spouseMiddleName = trim((string) $this->request->getPost('spouse_middle_name'));
                $spouseLastName = trim((string) $this->request->getPost('spouse_last_name'));
                $spouseName = trim(implode(' ', array_filter([$spouseFirstName, $spouseMiddleName, $spouseLastName], static fn ($value): bool => $value !== '')));
            }

            $planHolderData = [
                'user_id' => (int) $user['user_id'],
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
                'civil_status' => $civilStatus,
                'citizenship' => trim((string) $this->request->getPost('citizenship')),
                'height' => $this->nullableDecimalPost('height'),
                'weight' => $this->nullableDecimalPost('weight'),
                'spouse_name' => $spouseName,
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
                $planHolderData['unique_identifier'] = strtoupper(preg_replace('/\s+/', '', (string) $this->request->getPost('last_name')))
                    . '-' . strtoupper(preg_replace('/\s+/', '', (string) $this->request->getPost('first_name')))
                    . '-' . substr((string) time(), -6);

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

            (new UserModel())->update((int) $user['user_id'], [
                'is_plan_holder' => 1,
                'branch_id' => (int) $this->request->getPost('branch_id'),
            ]);

            session()->set('is_plan_holder', 1);
            session()->set('access_state', 'awaiting_activation');

            $beneficiaries = [];
            $isPrimary = true;

            foreach ($beneficiariesInput as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $firstName = trim((string) ($row['first_name'] ?? ''));
                $middleName = trim((string) ($row['middle_name'] ?? ''));
                $lastName = trim((string) ($row['last_name'] ?? ''));
                $birthday = trim((string) ($row['birthday'] ?? $row['date_of_birth'] ?? ''));
                $relationship = trim((string) ($row['relationship'] ?? ''));

                // Skip completely empty rows
                if ($name === '' && $firstName === '' && $middleName === '' && $lastName === '' && $birthday === '' && $relationship === '') {
                    continue;
                }

                // Defense in depth (rows were pre-validated above).
                if ($name === '' && $firstName === '' && $lastName === '') {
                    throw new \RuntimeException('Beneficiary name is required. Please provide at least a first or last name for each beneficiary.');
                }

                if ($relationship === '') {
                    throw new \RuntimeException('Please enter the relationship for each beneficiary.');
                }

                if ($name !== '') {
                    $nameParts = $this->parseBeneficiaryName($name);
                } else {
                    $nameParts = [
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'name_extension' => null,
                    ];
                }

                if ($firstName !== '' || $middleName !== '' || $lastName !== '') {
                    $nameParts['first_name'] = $firstName !== '' ? $firstName : ($nameParts['first_name'] ?? '');
                    $nameParts['middle_name'] = $middleName !== '' ? $middleName : ($nameParts['middle_name'] ?? '');
                    $nameParts['last_name'] = $lastName !== '' ? $lastName : ($nameParts['last_name'] ?? '');
                }

                $beneficiaries[] = $this->filterTableData('beneficiaries', [
                    'plan_holder_id' => $planHolderId,
                    'first_name' => $nameParts['first_name'] ?? '',
                    'middle_name' => $nameParts['middle_name'] ?? '',
                    'last_name' => $nameParts['last_name'] ?? '',
                    'name_extension' => $nameParts['name_extension'] ?? null,
                    'date_of_birth' => $birthday !== '' ? $birthday : null,
                    'relationship' => $relationship !== '' ? $relationship : 'N/A',
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

            // Audit logging for the registration submission.
            helper('activity_log');
            log_activity(
                (int) $user['user_id'],
                'created',
                'plan_holder',
                $planHolderId,
                'Submitted plan registration (coordinator #' . $coordinatorUserId . ', ID ' . $idVerification['type'] . ', match ' . number_format((float) $idVerification['score'], 1) . ')'
            );

            $verificationMessage = $verificationDocument ? ' Your uploaded ID appears consistent and is pending staff verification.' : '';
            return redirect()->to('/initial-payment')
                ->with('success', 'Plan registration submitted successfully. Please make your initial payment to activate your membership.' . $verificationMessage);

        } catch (\Throwable $e) {
            log_message('error', 'Plan registration failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Step 3 — Government ID verification (Level 1 + Level 2).
     *
     * Level 1: real image (MIME + getimagesize + <=2MB + jpg/png), supported ID
     *          type, non-empty ID number with type-appropriate format.
     * Level 2: OCR text captured in the browser is re-scored here server-side
     *          against the applicant's name/DOB/address/ID number.
     *
     * On success the file is stored under writable/uploads (never web-accessible)
     * and the document details are returned for persistence on plan_holders.
     *
     * @throws \RuntimeException with a user-facing message on any failure.
     */
    private function processIdVerification(array $user): array
    {
        $idType = trim((string) $this->request->getPost('id_type'));
        $idNumber = trim((string) $this->request->getPost('id_number'));
        $ocrText = (string) $this->request->getPost('ocr_text');

        $file = $this->request->getFile('valid_id');
        if (! $file instanceof UploadedFile || ! $file->isValid() || $file->hasMoved()) {
            throw new \RuntimeException('Please upload a clear photo of your government ID.');
        }

        // Level 1 — real image, allowed MIME, size limit.
        if ((int) $file->getSize() > 2 * 1024 * 1024) {
            throw new \RuntimeException('The ID image must be 2MB or smaller.');
        }
        $mime = strtolower((string) $file->getMimeType());
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png',
            'image/webp', 'image/heic', 'image/heif',
            'image/tiff', 'image/bmp', 'image/gif'
        ];
        if (! in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('Unsupported image format. Please upload JPG, PNG, WebP, HEIC, TIFF, BMP, or GIF.');
        }
        $imageInfo = @getimagesize($file->getTempName());
        if (! $imageInfo || $imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            throw new \RuntimeException('The uploaded file is not a valid image. Please upload a clear photo of your ID.');
        }
        $extension = strtolower((string) ($file->getClientExtension() ?: 'jpg'));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'tiff', 'tif', 'bmp', 'gif'];
        if (! in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Unsupported image format. Please use JPG, PNG, WebP, HEIC, TIFF, BMP, or GIF.');
        }

        // Level 1 — supported ID type + non-empty, plausible ID number.
        if (! IdVerificationService::isSupportedIdType($idType)) {
            throw new \RuntimeException('The selected ID type is not supported. Please choose a government-issued ID from the list.');
        }
        if ($idNumber === '') {
            throw new \RuntimeException('ID number is required.');
        }
        if (! IdVerificationService::validateIdNumber($idType, $idNumber)) {
            throw new \RuntimeException('The ID number you entered does not match the expected format for ' . IdVerificationService::idTypeLabel($idType) . '. Please re-check the ID number.');
        }

        // Level 2 — OCR text + server-side match.
        if (trim($ocrText) === '') {
            throw new \RuntimeException('The ID was not scanned. Please re-upload your ID and wait for the scan to finish before submitting.');
        }

        $applicant = [
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'middle_name' => trim((string) $this->request->getPost('middle_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'date_of_birth' => $this->nullablePost('date_of_birth'),
            'address' => trim(implode(' ', array_filter([
                (string) $this->request->getPost('address_street'),
                (string) $this->request->getPost('address_barangay'),
                (string) $this->request->getPost('address_city'),
            ], static fn ($value): bool => $value !== ''))),
        ];
        $score = IdVerificationService::verifyMatch($ocrText, $applicant, $idType, $idNumber);

        if ($score < IdVerificationService::MATCH_THRESHOLD) {
            throw new \RuntimeException('The information on your ID does not appear to match the details you provided (match score ' . round($score) . '). Please re-check your details and re-submit.');
        }

        // Secure storage under writable/uploads — never served directly.
        $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'plan_registration_verification' . DIRECTORY_SEPARATOR . (int) $user['user_id'];
        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0777, true) && ! is_dir($uploadDir)) {
            throw new \RuntimeException('Unable to create the upload directory for the verification document.');
        }

        $fileName = 'valid_id_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($uploadDir, $fileName);
        $documentPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        session()->set('verification_document_path', $documentPath);
        session()->set('verification_document_name', $file->getClientName());

        return [
            'path' => $documentPath,
            'type' => $idType,
            'number' => $idNumber,
            'score' => $score,
            'document' => [
                'path' => $documentPath,
                'name' => $file->getClientName(),
            ],
        ];
    }

    /**
     * Keep only columns that exist in the target table.
     */
    protected function filterTableData(string $table, array $data): array
    {
        $db = db_connect();
        $fields = $db->getFieldNames($table);

        if (empty($fields)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($fields));
    }
}
