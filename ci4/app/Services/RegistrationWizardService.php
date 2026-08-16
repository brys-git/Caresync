<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * RegistrationWizardService
 *
 * Shared building blocks for the staff-assisted plan-holder registration
 * wizard (branch admin, staff, and system admin). Extracted from
 * Client\ClientRegistrationController so every registration entry point
 * behaves identically to the client flow:
 *
 *  - coordinator assignment persisted as plan_holders.coordinator_user_id
 *  - Married => spouse required (frontend + backend)
 *  - government-ID verification (Level 1 supported ID / Level 2 OCR match)
 *  - server-side beneficiary validation + insertion
 *  - DOB sanity (not in the future, age <= 150)
 *
 * Honest-labeling contract: ID verification only establishes "appears
 * consistent" — it can never prove authenticity. Staff confirmation remains
 * the authority (plan_holders.id_verification_status).
 */
class RegistrationWizardService
{
    /**
     * Resolve a coordinator candidate. Returns the user row ONLY if the
     * given id is a real, active staff (role 3) or branch admin (role 2).
     */
    public static function resolveCoordinator(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return db_connect()->table('users')
            ->select('user_id, first_name, middle_name, last_name, name_extension')
            ->where('user_id', $userId)
            ->whereIn('role_id', [2, 3])
            ->where('status', 'active')
            ->get()
            ->getRowArray();
    }

    /** Full display name of a coordinator user row. */
    public static function coordinatorName(array $user): string
    {
        return trim(implode(' ', array_filter([
            (string) ($user['first_name'] ?? ''),
            (string) ($user['middle_name'] ?? ''),
            (string) ($user['last_name'] ?? ''),
        ], static fn (string $value): bool => $value !== '')));
    }

    /**
     * Extra server validation rules when the applicant is Married.
     */
    public static function spouseRules(string $civilStatus): array
    {
        if ($civilStatus !== 'Married') {
            return [];
        }

        return [
            'spouse_first_name' => 'required|max_length[50]',
            'spouse_last_name' => 'required|max_length[50]',
        ];
    }

    /**
     * DOB sanity check. Returns an error string, or null when acceptable.
     */
    public static function validateDob(string $dob): ?string
    {
        if ($dob === '') {
            return null;
        }

        $ts = strtotime($dob);
        if ($ts === false) {
            return null;
        }

        if ($ts > strtotime('today')) {
            return 'Date of birth cannot be in the future.';
        }

        if ($ts > 0) {
            $ageYears = (int) floor((time() - $ts) / (365.25 * 86400));
            if ($ageYears > 150) {
                return 'Age cannot exceed 150 years.';
            }
        }

        return null;
    }

    /**
     * Server-side beneficiary validation (mirrors the client flow).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{errors: list<string>, rows: array<int, array<string, mixed>>}
     */
    public static function validateBeneficiaries(array $rows): array
    {
        $errors = [];
        $cleaned = [];
        $nonEmptyCount = 0;

        foreach ($rows as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $middleName = trim((string) ($row['middle_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $birthday = trim((string) ($row['birthday'] ?? $row['date_of_birth'] ?? ''));
            $relationship = trim((string) ($row['relationship'] ?? ''));

            if ($name === '' && $firstName === '' && $middleName === '' && $lastName === '' && $birthday === '' && $relationship === '') {
                continue;
            }
            $nonEmptyCount++;

            if ($name === '' && $firstName === '' && $lastName === '') {
                $errors[] = 'Beneficiary row ' . ($i + 1) . ': last name and given name are required.';
            }
            if ($relationship === '') {
                $errors[] = 'Beneficiary row ' . ($i + 1) . ': relationship is required.';
            }

            $cleaned[] = [
                'name' => $name,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'birthday' => $birthday,
                'relationship' => $relationship,
            ];
        }

        if ($nonEmptyCount === 0) {
            $errors[] = 'At least one beneficiary is required.';
        }

        return ['errors' => $errors, 'rows' => $cleaned];
    }

    /**
     * Government ID verification — Level 1 (supported, real image) + Level 2
     * (OCR text matches the applicant server-side).
     *
     * On success moves the file under
     * writable/uploads/plan_registration_verification/{subdir}/ (never
     * web-accessible) and returns the id_* fields for persistence.
     *
     * @param array{first_name?:string,middle_name?:string,last_name?:string,date_of_birth?:string,address?:string} $applicant
     * @return array{path:string,type:string,number:string,score:float}
     * @throws \RuntimeException with a user-facing message when any check fails
     */
    public static function processIdVerification(
        ?UploadedFile $file,
        array $applicant,
        string $ocrText,
        string $idType,
        string $idNumber,
        string $subdir
    ): array {
        if (! $file instanceof UploadedFile || ! $file->isValid() || $file->hasMoved()) {
            throw new \RuntimeException('Please upload a clear photo of the government ID.');
        }

        // Level 1 — real image, allowed MIME, size limit.
        if ((int) $file->getSize() > 2 * 1024 * 1024) {
            throw new \RuntimeException('The ID image must be 2MB or smaller.');
        }
        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
            throw new \RuntimeException('Only JPG or PNG images are accepted for the ID. Please upload a photo of the ID.');
        }
        $imageInfo = @getimagesize($file->getTempName());
        if (! $imageInfo || $imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            throw new \RuntimeException('The uploaded file is not a valid image. Please upload a clear photo of the ID.');
        }
        $extension = strtolower((string) ($file->getClientExtension() ?: 'jpg'));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new \RuntimeException('Only JPG or PNG images are accepted for the ID.');
        }

        // Level 1 — supported ID type + non-empty, plausible ID number.
        if (! IdVerificationService::isSupportedIdType($idType)) {
            throw new \RuntimeException('The selected ID type is not supported. Please choose a government-issued ID from the list.');
        }
        if ($idNumber === '') {
            throw new \RuntimeException('ID number is required.');
        }
        if (! IdVerificationService::validateIdNumber($idType, $idNumber)) {
            throw new \RuntimeException('The ID number entered does not match the expected format for ' . IdVerificationService::idTypeLabel($idType) . '. Please re-check the ID number.');
        }

        // Level 2 — OCR text + server-side match.
        if (trim($ocrText) === '') {
            throw new \RuntimeException('The ID was not scanned. Please re-upload the ID and wait for the scan to finish before submitting.');
        }

        $score = IdVerificationService::verifyMatch($ocrText, $applicant, $idType, $idNumber);
        if ($score < IdVerificationService::MATCH_THRESHOLD) {
            throw new \RuntimeException('The information on the ID does not appear to match the details provided (match score ' . round($score) . '). Please re-check the details and re-submit.');
        }

        // Secure storage under writable/uploads — never served directly.
        $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'plan_registration_verification' . DIRECTORY_SEPARATOR . $subdir;
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
        ];
    }

    /**
     * Insert beneficiary rows for a plan holder (replaces existing rows).
     * Intended to run inside the caller's DB transaction.
     *
     * @param array<int, array<string, mixed>> $rows cleaned rows from validateBeneficiaries()
     */
    public static function insertBeneficiaries(array $rows, int $planHolderId): void
    {
        $db = db_connect();
        $db->table('beneficiaries')
            ->where('plan_holder_id', $planHolderId)
            ->delete();

        $isPrimary = true;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $middleName = trim((string) ($row['middle_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $birthday = trim((string) ($row['birthday'] ?? ''));
            $relationship = trim((string) ($row['relationship'] ?? ''));

            if ($name !== '') {
                $nameParts = self::parseBeneficiaryName($name);
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

            $db->table('beneficiaries')->insert([
                'plan_holder_id' => $planHolderId,
                'first_name' => (string) ($nameParts['first_name'] ?? ''),
                'middle_name' => (string) ($nameParts['middle_name'] ?? ''),
                'last_name' => (string) ($nameParts['last_name'] ?? ''),
                'name_extension' => $nameParts['name_extension'] ?? null,
                'date_of_birth' => $birthday !== '' ? $birthday : null,
                'relationship' => $relationship !== '' ? $relationship : 'N/A',
                'is_primary' => $isPrimary ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $isPrimary = false;
        }
    }

    /**
     * Resolve the configured package and its active version (mirrors
     * ClientPortalTrait::resolvePackageAndVersion()). Falls back to the
     * first package / latest version when the defaults are missing.
     *
     * @return array{package_id:int, version_id:int}
     */
    public static function resolvePackageAndVersion(): array
    {
        $db = db_connect();

        $package = $db->table('packages')
            ->select('package_id')
            ->where('package_id', MembershipService::DEFAULT_PACKAGE_ID)
            ->get()
            ->getRowArray();

        if (! $package) {
            $package = $db->table('packages')
                ->select('package_id')
                ->orderBy('package_id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (! $package) {
            throw new \RuntimeException('No package is configured yet. Please ask admin to create a package first.');
        }

        $packageId = (int) $package['package_id'];
        $version = $db->table('package_versions')
            ->select('version_id')
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->orderBy('version_id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $version) {
            $version = $db->table('package_versions')
                ->select('version_id')
                ->where('package_id', $packageId)
                ->orderBy('version_id', 'DESC')
                ->get()
                ->getRowArray();
        }

        if (! $version) {
            $db->table('package_versions')->insert([
                'package_id' => $packageId,
                'price' => MembershipService::MONTHLY_FEE,
                'effective_date' => date('Y-m-d'),
                'status' => 'active',
            ]);

            $versionId = (int) $db->insertID();
            if ($versionId <= 0) {
                throw new \RuntimeException('No package version is configured yet.');
            }

            return ['package_id' => $packageId, 'version_id' => $versionId];
        }

        return [
            'package_id' => $packageId,
            'version_id' => (int) $version['version_id'],
        ];
    }

    /**
     * Insert the inactive plan record for a newly registered plan holder,
     * mirroring the client flow exactly so the membership awaits initial-payment
     * verification (status=inactive, membership_state=inactive).
     */
    public static function createInactivePlan(int $planHolderId): void
    {
        $packageData = self::resolvePackageAndVersion();
        $monthlyFee = (float) (MembershipService::getProgramInfo()['monthly_fee'] ?? MembershipService::MONTHLY_FEE);

        $planModel = new \App\Models\PlanModel();
        $existingPlan = $planModel
            ->where('plan_holder_id', $planHolderId)
            ->first();

        if ($existingPlan) {
            return;
        }

        $planModel->insert([
            'plan_holder_id' => $planHolderId,
            'package_id' => $packageData['package_id'],
            'monthly_fee' => $monthlyFee,
            'passbook_fee' => 50,
            'start_date' => date('Y-m-d'),
            'status' => 'inactive',
            'months_paid' => 0,
            'remaining_balance' => $monthlyFee * 12,
            'membership_state' => 'inactive',
            'overdue_months' => 0,
            'version_id' => $packageData['version_id'],
        ], true);
    }

    /**
     * Split a combined beneficiary name ("Last, First Middle" / "First Middle Last")
     * into parts. Mirrors ClientPortalTrait::parseBeneficiaryName().
     *
     * @return array{first_name:string,middle_name:string,last_name:string,name_extension:?string}
     */
    private static function parseBeneficiaryName(string $name): array
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', $name));
        if ($cleaned === '') {
            return [
                'first_name' => '-',
                'middle_name' => '',
                'last_name' => '',
                'name_extension' => null,
            ];
        }

        $parts = explode(' ', $cleaned);
        $extension = null;
        $extensions = ['JR', 'SR', 'II', 'III', 'IV'];

        $last = strtoupper($parts[count($parts) - 1]);
        if (in_array($last, $extensions, true)) {
            $extension = array_pop($parts);
        }

        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => '',
                'last_name' => '',
                'name_extension' => $extension,
            ];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [
            'first_name' => $firstName,
            'middle_name' => '',
            'last_name' => $lastName,
            'name_extension' => $extension,
        ];
    }
}
