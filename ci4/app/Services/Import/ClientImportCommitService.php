<?php

namespace App\Services\Import;

use App\Models\ClientImportBatchModel;
use App\Models\ClientImportRecordModel;
use App\Services\ActivityLogService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use App\Services\Import\Normalizers\ClientDateNormalizer;

/**
 * Commits a reviewed, staged batch into the live schema — users, plan_holders,
 * beneficiaries and plans — inside ONE transaction. All-or-nothing:
 *
 *   - Every non-skipped record is re-validated first (name present, valid DOB,
 *     >=1 beneficiary with name + relationship, link target still exists, an
 *     admin decision was made). Any failure aborts the whole batch.
 *   - create_new     : users -> plan_holders -> beneficiaries -> plans
 *   - link_existing  : no writes; the linkage is recorded on the staging row
 *   - skip           : just marked
 *
 * The temporary password is generated at commit time (not staging) so it is as
 * fresh as possible when handed to the client.
 */
class ClientImportCommitService
{
    private const MAX_AGE = 150;

    private ClientImportBatchModel $batchModel;
    private ClientImportRecordModel $recordModel;
    private ImportCredentialService $credentials;
    private ActivityLogService $activityLog;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->batchModel = new ClientImportBatchModel();
        $this->recordModel = new ClientImportRecordModel();
        $this->credentials = new ImportCredentialService();
        $this->activityLog = new ActivityLogService();
        $this->notifications = new NotificationService();
    }

    /**
     * @return array{created: int, linked: int, skipped: int, credentials: array<int, array{record_index: int, full_name: string, username: string, password: string, user_id: int}>}
     *
     * @throws CommitException when validation fails or the transaction aborts.
     */
    public function commitBatch(int $batchId, int $committedBy, int $branchId = 0): array
    {
        $db = db_connect();

        $batch = $this->batchModel->find($batchId);
        if (! $batch) {
            throw new CommitException('Import batch was not found.');
        }
        if ($branchId > 0 && (int) ($batch['branch_id'] ?? 0) !== $branchId) {
            throw new CommitException('This import batch belongs to another branch.');
        }
        if ((string) ($batch['status'] ?? '') !== 'staged') {
            throw new CommitException('This batch has already been ' . ($batch['status'] ?? '') . ' and can no longer be committed.');
        }

        $records = $db->table('client_import_records')
            ->where('import_batch_id', $batchId)
            ->orderBy('source_index', 'ASC')
            ->get()
            ->getResultArray();

        if ($records === []) {
            throw new CommitException('No staged records were found in this batch.');
        }

        $this->validateRecords($records, $db);

        // ---- Commit — single transaction, nothing written before this point ----
        $db->transBegin();

        try {
            $counts = ['created' => 0, 'linked' => 0, 'skipped' => 0];
            $credentials = [];
            $takenUsernames = [];
            $takenEmails = [];
            $now = date('Y-m-d H:i:s');

            foreach ($records as $record) {
                $decision = (string) ($record['admin_decision'] ?? 'pending');
                $recordId = (int) $record['import_record_id'];

                if ($decision === 'skip') {
                    $this->recordModel->update($recordId, [
                        'committed_at' => $now,
                        'committed_by' => $committedBy,
                    ]);
                    $counts['skipped']++;

                    continue;
                }

                if ($decision === 'link_existing') {
                    $target = $db->table('plan_holders')
                        ->select('plan_holder_id, user_id')
                        ->where('plan_holder_id', (int) $record['linked_plan_holder_id'])
                        ->get()
                        ->getRowArray();

                    $this->recordModel->update($recordId, [
                        'linked_user_id' => (int) ($target['user_id'] ?? 0),
                        'linked_plan_holder_id' => (int) $record['linked_plan_holder_id'],
                        'committed_at' => $now,
                        'committed_by' => $committedBy,
                    ]);
                    $counts['linked']++;

                    continue;
                }

                // ---- create_new ----
                $plainPassword = $this->credentials->generatePassword();
                $user = $this->createUser($record, $batch, $takenUsernames, $takenEmails, $plainPassword);
                $planHolderId = $this->createPlanHolder($record, (int) $user['user_id'], $batch);
                $this->createBeneficiaries($record, $planHolderId);
                $planId = $this->createPlan($record, $planHolderId);

                $this->recordModel->update($recordId, [
                    'created_user_id' => (int) $user['user_id'],
                    'created_plan_holder_id' => $planHolderId,
                    'created_plan_id' => $planId,
                    'temp_password_hash' => $this->credentials->hashPassword($plainPassword),
                    'temp_password_plain' => $plainPassword,
                    'committed_at' => $now,
                    'committed_by' => $committedBy,
                ]);

                $counts['created']++;
                $credentials[] = [
                    'record_index' => (int) $record['source_index'],
                    'full_name' => trim((string) $record['first_name'] . ' ' . (string) $record['last_name']),
                    'username' => (string) $user['username'],
                    'password' => $plainPassword,
                    'user_id' => (int) $user['user_id'],
                ];
            }

            $this->batchModel->update($batchId, [
                'status' => 'committed',
                'committed_at' => $now,
                'committed_by' => $committedBy,
                'committed_count' => $counts['created'] + $counts['linked'],
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed while committing the import.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            if ($e instanceof CommitException) {
                throw $e;
            }

            throw new CommitException('Commit failed and was rolled back: ' . $e->getMessage());
        }

        // ---- Post-commit logging + notifications (outside the transaction) ----
        $this->activityLog->log(
            $committedBy,
            'committed',
            'client_import',
            $batchId,
            'Committed import batch #' . $batchId . ' — ' . $counts['created'] . ' created, ' . $counts['linked'] . ' linked, ' . $counts['skipped'] . ' skipped.'
        );

        foreach ($credentials as $credential) {
            $this->notifications->notify(
                (int) $credential['user_id'],
                'Your KAAGAPAY account has been created (username: ' . $credential['username'] . '). Contact your branch to receive your temporary password and complete your first login.',
                'registration_pending'
            );
        }

        return [
            'created' => $counts['created'],
            'linked' => $counts['linked'],
            'skipped' => $counts['skipped'],
            'credentials' => $credentials,
        ];
    }

    /**
     * Blocking validation — any error aborts the whole batch (never insert blindly).
     *
     * @param array<int, array<string, mixed>> $records
     */
    private function validateRecords(array $records, $db): void
    {
        $errors = [];

        foreach ($records as $record) {
            $decision = (string) ($record['admin_decision'] ?? 'pending');
            if ($decision === 'skip') {
                continue;
            }

            $label = '#' . (int) ($record['source_index'] ?? 0) . ' ' . trim((string) ($record['first_name'] ?? '') . ' ' . (string) ($record['last_name'] ?? ''));
            $errs = [];

            if ($decision === 'pending') {
                $errs[] = 'No decision has been made — choose Create, Link or Skip.';
            }

            if ($decision === 'create_new') {
                if (trim((string) ($record['first_name'] ?? '')) === '') {
                    $errs[] = 'First name is required.';
                }
                if (trim((string) ($record['last_name'] ?? '')) === '') {
                    $errs[] = 'Last name is required.';
                }

                $dob = (string) ($record['date_of_birth'] ?? '');
                if ($dob !== '') {
                    if (! $this->isValidDate($dob)) {
                        $errs[] = 'Invalid date of birth "' . $dob . '".';
                    } else {
                        $age = ClientDateNormalizer::ageFrom($dob);
                        if ($age === null || $age > self::MAX_AGE) {
                            $errs[] = 'Date of birth implies an age over ' . self::MAX_AGE . ' — please verify.';
                        }
                    }
                }

                $applicationDate = (string) ($record['application_date'] ?? '');
                if ($applicationDate !== '' && ! $this->isValidDate($applicationDate)) {
                    $errs[] = 'Invalid application date "' . $applicationDate . '".';
                }

                $beneficiaries = $this->decodeJson($record['beneficiaries_json'] ?? '', []);
                if ($beneficiaries === []) {
                    $errs[] = 'At least one beneficiary with a name is required.';
                } else {
                    foreach ($beneficiaries as $index => $beneficiary) {
                        $first = trim((string) ($beneficiary['first_name'] ?? ''));
                        $last = trim((string) ($beneficiary['last_name'] ?? ''));
                        $name = trim($first . ' ' . $last);

                        if ($name === '' || $last === '') {
                            $errs[] = 'Beneficiary #' . ($index + 1) . ' is missing a name (last name is required).';
                        }
                        if (trim((string) ($beneficiary['relationship'] ?? '')) === '') {
                            $errs[] = 'Beneficiary #' . ($index + 1) . ' (' . ($name ?: 'unnamed') . ') is missing a relationship.';
                        }
                    }
                }
            }

            if ($decision === 'link_existing') {
                $targetId = (int) ($record['linked_plan_holder_id'] ?? 0);
                if ($targetId <= 0) {
                    $errs[] = 'Link-existing was chosen but no matching client was selected.';
                } else {
                    $target = $db->table('plan_holders')
                        ->select('plan_holder_id')
                        ->where('plan_holder_id', $targetId)
                        ->get()
                        ->getRowArray();

                    if (! $target) {
                        $errs[] = 'Selected linked client #' . $targetId . ' no longer exists.';
                    }
                }
            }

            if ($errs !== []) {
                $errors[$label] = $errs;
            }
        }

        if ($errors !== []) {
            throw new CommitException('Some records still have unresolved issues — the batch was not committed.', $errors);
        }
    }

    /**
     * @param array<string, mixed> $batch
     * @param array<int, string> $takenUsernames
     * @param array<int, string> $takenEmails
     *
     * @return array{user_id: int, username: string}
     */
    private function createUser(array $record, array $batch, array &$takenUsernames, array &$takenEmails, string $plainPassword): array
    {
        $db = db_connect();

        $username = (string) ($record['temp_username'] ?? '');
        $email = (string) ($record['temp_email'] ?? '');

        // The stored credential may have collided since staging (someone else was
        // created in the meantime) — regenerate defensively.
        if ($username === '' || $this->usernameTaken($username, $takenUsernames)) {
            $username = $this->credentials->generateUsername(
                (string) ($record['first_name'] ?? ''),
                (string) ($record['last_name'] ?? ''),
                $takenUsernames
            );
        }
        if ($email === '' || $this->emailTaken($email, $takenEmails)) {
            $email = $this->credentials->generateEmail($username, $takenEmails);
        }

        $takenUsernames[] = $username;
        $takenEmails[] = $email;

        $mapped = $this->decodeJson($record['mapped_data'] ?? '', []);
        $optional = $mapped['optional'] ?? [];

        $db->table('users')->insert([
            'username' => $username,
            'password_hash' => $this->credentials->hashPassword($plainPassword),
            'email' => $email,
            'first_name' => trim((string) ($record['first_name'] ?? '')),
            'middle_name' => $this->nullIfEmpty($record['middle_name'] ?? ''),
            'last_name' => trim((string) ($record['last_name'] ?? '')),
            'name_extension' => $this->nullIfEmpty($record['name_extension'] ?? ''),
            'contact_number' => $this->nullIfEmpty($optional['contact_number'] ?? ''),
            'role_id' => 4,
            'branch_id' => (int) ($batch['branch_id'] ?? 0),
            'status' => 'active',
            'account_status' => 'verified',
            'is_plan_holder' => 1,
            'must_change_password' => 1,
        ]);

        return [
            'user_id' => (int) $db->insertID(),
            'username' => $username,
        ];
    }

    /**
     * @param array<string, mixed> $batch
     */
    private function createPlanHolder(array $record, int $userId, array $batch): int
    {
        $db = db_connect();
        $mapped = $this->decodeJson($record['mapped_data'] ?? '', []);
        $optional = $mapped['optional'] ?? [];

        $dob = (string) ($record['date_of_birth'] ?? '');

        $db->table('plan_holders')->insert([
            'user_id' => $userId,
            'branch_id' => (int) ($batch['branch_id'] ?? 0),
            'coordinator' => $this->nullIfEmpty($record['coordinator'] ?? ''),
            'application_date' => $this->nullIfEmpty($record['application_date'] ?? ''),
            'address_no' => $this->nullIfEmpty($record['address_no'] ?? ''),
            'address_street' => $this->nullIfEmpty($record['address_street'] ?? ''),
            'address_barangay' => $this->nullIfEmpty($record['address_barangay'] ?? ''),
            'address_city' => $this->nullIfEmpty($record['address_city'] ?? ''),
            'date_of_birth' => $this->nullIfEmpty($dob),
            'age' => $dob !== '' ? ClientDateNormalizer::ageFrom($dob) : null,
            'place_of_birth' => $this->nullIfEmpty($optional['place_of_birth'] ?? ''),
            'gender' => $this->nullIfEmpty($optional['gender'] ?? ''),
            'civil_status' => $this->nullIfEmpty($optional['civil_status'] ?? ''),
            'citizenship' => $this->nullIfEmpty($optional['citizenship'] ?? ''),
            'senior_citizen_id' => $this->nullIfEmpty($optional['senior_citizen_id'] ?? ''),
            'id_control_no' => $this->nullIfEmpty($optional['id_control_no'] ?? ''),
            'emergency_contact_name' => $this->nullIfEmpty($optional['emergency_contact_name'] ?? ''),
            'emergency_contact_number' => $this->nullIfEmpty($optional['emergency_contact_number'] ?? ''),
            'emergency_contact_address' => $this->nullIfEmpty($optional['emergency_contact_address'] ?? ''),
            'status' => 'active',
            'is_linked_account' => 0,
            'unique_identifier' => $this->generateUniqueIdentifier(
                (string) ($record['first_name'] ?? ''),
                (string) ($record['last_name'] ?? '')
            ),
        ]);

        return (int) $db->insertID();
    }

    private function createBeneficiaries(array $record, int $planHolderId): void
    {
        $db = db_connect();
        $beneficiaries = $this->decodeJson($record['beneficiaries_json'] ?? '', []);
        if ($beneficiaries === []) {
            return;
        }

        $rows = [];
        foreach ($beneficiaries as $index => $beneficiary) {
            $rows[] = [
                'plan_holder_id' => $planHolderId,
                'first_name' => trim((string) ($beneficiary['first_name'] ?? '')),
                'middle_name' => $this->nullIfEmpty($beneficiary['middle_name'] ?? ''),
                'last_name' => trim((string) ($beneficiary['last_name'] ?? '')),
                'name_extension' => $this->nullIfEmpty($beneficiary['name_extension'] ?? ''),
                'date_of_birth' => $this->nullIfEmpty($beneficiary['date_of_birth'] ?? ''),
                'relationship' => trim((string) ($beneficiary['relationship'] ?? '')),
                'is_primary' => $index === 0 ? 1 : 0,
                'verification_status' => 'verified',
            ];
        }

        $db->table('beneficiaries')->insertBatch($rows);
    }

    private function createPlan(array $record, int $planHolderId): int
    {
        $db = db_connect();
        $mapped = $this->decodeJson($record['mapped_data'] ?? '', []);
        $planData = $mapped['plan'] ?? [];

        $package = $this->resolvePlanPackage($planData);
        $monthlyFee = (float) ($planData['monthly_fee'] ?? MembershipService::MONTHLY_FEE);
        $startDate = (string) ($record['application_date'] ?? '');
        if ($startDate === '' || ! $this->isValidDate($startDate)) {
            $startDate = date('Y-m-d');
        }

        $db->table('plans')->insert([
            'plan_holder_id' => $planHolderId,
            'package_id' => $package['package_id'],
            'monthly_fee' => $monthlyFee,
            'start_date' => $startDate,
            'status' => (string) ($planData['plan_status'] ?? 'active'),
            'months_paid' => 0,
            'remaining_balance' => number_format($monthlyFee * 12, 2, '.', ''),
            'version_id' => $package['version_id'],
        ]);

        return (int) $db->insertID();
    }

    /**
     * Resolve the membership package + active version for a new plan, mirroring
     * ClientService::resolveMembershipPackage (package must exist — FK on plans).
     *
     * @return array{package_id: int, version_id: int}
     */
    private function resolvePlanPackage(array $planData): array
    {
        $db = db_connect();
        $preferred = (int) ($planData['package_id'] ?? MembershipService::DEFAULT_PACKAGE_ID);

        $package = $db->table('packages')
            ->select('package_id')
            ->where('package_id', $preferred)
            ->get()
            ->getRowArray();

        if (! $package) {
            $package = $db->table('packages')
                ->select('package_id')
                ->where('is_available', 1)
                ->orderBy('package_id', 'ASC')
                ->get()
                ->getRowArray();
        }
        if (! $package) {
            $package = $db->table('packages')
                ->select('package_id')
                ->orderBy('package_id', 'ASC')
                ->get()
                ->getRowArray();
        }
        if (! $package) {
            throw new \RuntimeException('No membership package is configured. Ask an admin to create one first.');
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
        } else {
            $versionId = (int) $version['version_id'];
        }

        return ['package_id' => $packageId, 'version_id' => $versionId];
    }

    /**
     * Format: LASTNAME-FIRSTNAME-<last 6 of unix time> (matches ClientService).
     * Collision-checked against the UNIQUE unique_identifier column.
     */
    private function generateUniqueIdentifier(string $firstName, string $lastName): string
    {
        $db = db_connect();
        $base = strtoupper(preg_replace('/\s+/', '', $lastName)) . '-' . strtoupper(preg_replace('/\s+/', '', $firstName));

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = $base . '-' . substr((string) (time() + $attempt), -6);
            $exists = $db->table('plan_holders')
                ->select('plan_holder_id')
                ->where('unique_identifier', $candidate)
                ->get()
                ->getRowArray();

            if (! $exists) {
                return $candidate;
            }
        }

        return $base . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function usernameTaken(string $username, array $takenUsernames): bool
    {
        if (in_array($username, $takenUsernames, true)) {
            return true;
        }

        return db_connect()->table('users')
            ->select('user_id')
            ->where('username', $username)
            ->get()
            ->getRowArray() !== null;
    }

    private function emailTaken(string $email, array $takenEmails): bool
    {
        if (in_array($email, $takenEmails, true)) {
            return true;
        }

        return db_connect()->table('users')
            ->select('user_id')
            ->where('email', $email)
            ->get()
            ->getRowArray() !== null;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (! $d) {
            return false;
        }

        return $d->format('Y-m-d') === $date;
    }

    /**
     * @param mixed $json
     * @param mixed $default
     * @return mixed
     */
    private function decodeJson($json, $default)
    {
        if (is_array($json)) {
            return $json;
        }
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? $decoded : $default;
    }

    private function nullIfEmpty($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
