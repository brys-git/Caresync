<?php

namespace App\Services;

use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\MembershipService;

class ClientService
{
    public function getClientsByBranch(int $branchId): array
    {
        $db = db_connect();

        $clients = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.user_id, ph.unique_identifier, ph.status AS plan_holder_status, u.first_name, u.last_name, u.email, u.contact_number')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add initial payment status for each client
        foreach ($clients as &$client) {
            $payment = $db->table('payments p')
                ->select('p.status AS initial_payment_status')
                ->join('plans pl', 'pl.plan_id = p.plan_id', 'inner')
                ->where('pl.plan_holder_id', (int) $client['plan_holder_id'])
                ->where('p.status !=', '')
                ->where('p.status IS NOT NULL', null, false)
                ->orderBy('p.payment_date', 'ASC')
                ->orderBy('p.payment_id', 'ASC')
                ->get()
                ->getRowArray();

            $client['initial_payment_status'] = $payment ? $payment['initial_payment_status'] : 'none';
        }

        return $clients;
    }

    public function getClientDetails(int $planHolderId): ?array
    {
        $db = db_connect();

        $client = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.user_id, ph.branch_id, ph.unique_identifier, ph.address_no, ph.address_street, ph.address_barangay, ph.address_city, ph.date_of_birth, ph.place_of_birth, ph.age, ph.gender, ph.civil_status, ph.citizenship, ph.height, ph.weight, ph.spouse_name, ph.spouse_birthdate, ph.spouse_occupation, ph.senior_citizen_id, ph.organization_affiliation, ph.id_control_no, ph.coordinator, ph.application_date, ph.emergency_contact_name, ph.emergency_contact_number, ph.emergency_contact_address, ph.status AS plan_holder_status, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('ph.plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        if (! $client) {
            return null;
        }

        $plan = $db->table('plans p')
            ->select('p.plan_id, p.plan_holder_id, p.package_id, p.monthly_fee, p.start_date, p.status AS plan_status, p.remaining_balance, p.months_paid')
            ->where('p.plan_holder_id', $planHolderId)
            ->orderBy("CASE WHEN p.status = 'active' THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy('p.plan_id', 'DESC')
            ->get()
            ->getRowArray();

        if ($plan) {
            $plan['package_name'] = MembershipService::PROGRAM_NAME;
            $plan['program_name'] = MembershipService::PROGRAM_NAME;
        }

        $beneficiaries = [];
        if ($db->tableExists('beneficiaries')) {
            $beneficiaries = $db->table('beneficiaries')
                ->where('plan_holder_id', $planHolderId)
                ->orderBy('beneficiary_id', 'DESC')
                ->get()
                ->getResultArray();
        }

        $client['plan'] = $plan;
        $client['beneficiaries'] = $beneficiaries;

        return $client;
    }

    public function updateClient(int $planHolderId, array $data): bool
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $client = $db->table('plan_holders')
                ->select('plan_holder_id, user_id')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray();

            if (! $client) {
                throw new \RuntimeException('Client record was not found.');
            }

            $userModel = new UserModel();
            $planHolderModel = new PlanHolderModel();

            $updatedUser = $userModel->update((int) $client['user_id'], [
                'first_name' => (string) ($data['first_name'] ?? ''),
                'last_name' => (string) ($data['last_name'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'contact_number' => (string) ($data['contact_number'] ?? ''),
            ]);

            if (! $updatedUser) {
                throw new \RuntimeException('Failed to update user profile details.');
            }

            $updatedPlanHolder = $planHolderModel->update($planHolderId, [
                'address_no' => (string) ($data['address_no'] ?? ''),
                'address_street' => (string) ($data['address_street'] ?? ''),
                'address_barangay' => (string) ($data['address_barangay'] ?? ''),
                'address_city' => (string) ($data['address_city'] ?? ''),
                'date_of_birth' => $this->nullable($data['date_of_birth'] ?? null),
                'place_of_birth' => (string) ($data['place_of_birth'] ?? ''),
                'age' => $this->nullableInt($data['age'] ?? null),
                'gender' => (string) ($data['gender'] ?? ''),
                'civil_status' => (string) ($data['civil_status'] ?? ''),
                'citizenship' => (string) ($data['citizenship'] ?? ''),
                'height' => $this->nullable($data['height'] ?? null),
                'weight' => $this->nullable($data['weight'] ?? null),
                'spouse_name' => (string) ($data['spouse_name'] ?? ''),
                'spouse_birthdate' => $this->nullable($data['spouse_birthdate'] ?? null),
                'spouse_occupation' => (string) ($data['spouse_occupation'] ?? ''),
                'senior_citizen_id' => (string) ($data['senior_citizen_id'] ?? ''),
                'organization_affiliation' => (string) ($data['organization_affiliation'] ?? ''),
                'status' => (string) ($data['status'] ?? 'active'),
            ]);

            if (! $updatedPlanHolder) {
                throw new \RuntimeException('Failed to update plan holder details.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed while updating client.');
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function getClientPayments(?int $planId): array
    {
        if ($planId === null || $planId <= 0) {
            return [];
        }

        return db_connect()->table('payments pay')
            ->select('pay.payment_date, pay.amount, pay.payment_method, pay.status, rb.first_name AS receiver_first_name, rb.last_name AS receiver_last_name')
            ->join('users rb', 'rb.user_id = pay.received_by', 'left')
            ->where('pay.plan_id', $planId)
            ->orderBy('pay.payment_date', 'DESC')
            ->orderBy('pay.payment_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getClientServices(int $planHolderId): array
    {
        return db_connect()->table('services s')
            ->select("s.service_id, COALESCE(sl.service_name, '-') AS service_type, s.service_date, s.status, s.total_cost, COALESCE(sc.cost_entries, 0) AS cost_entries, COALESCE(ao.add_on_entries, 0) AS add_on_entries", false)
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->join('(SELECT service_id, COUNT(*) AS cost_entries FROM service_costs GROUP BY service_id) sc', 'sc.service_id = s.service_id', 'left', false)
            ->join('(SELECT service_id, COUNT(*) AS add_on_entries FROM add_ons GROUP BY service_id) ao', 'ao.service_id = s.service_id', 'left', false)
            ->where('s.plan_holder_id', $planHolderId)
            ->orderBy('s.service_date', 'DESC')
            ->orderBy('s.service_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Search for an existing user by email
     */
    public function findUserByEmail(string $email): ?array
    {
        return db_connect()->table('users')
            ->where('email', $email)
            ->get()
            ->getRowArray();
    }

    /**
     * Search for an existing user by name
     */
    public function findUserByName(string $firstName, string $lastName): ?array
    {
        return db_connect()->table('users')
            ->where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->get()
            ->getRowArray();
    }

    /**
     * Register a new plan holder
     * Handles two cases:
     * 1. Client has no account - Create user + plan holder + plan
     * 2. Client already has account - Link existing user to plan holder + create plan
     */
    public function registerPlanHolder(array $data, int $branchId): int
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $userModel = new UserModel();
            $planHolderModel = new PlanHolderModel();

            // CASE 1: Check if user already exists
            $existingUser = null;
            if (! empty($data['email'])) {
                $existingUser = $this->findUserByEmail((string) $data['email']);
            }

            if ($existingUser) {
                // CASE 2: User already has account - just create plan holder
                $userId = (int) $existingUser['user_id'];

                // Update user to mark as plan holder
                $userModel->update($userId, [
                    'is_plan_holder' => 1,
                ]);
            } else {
                // CASE 1: Create new user account
                $newUser = [
                    'first_name' => (string) ($data['first_name'] ?? ''),
                    'middle_name' => (string) ($data['middle_name'] ?? ''),
                    'last_name' => (string) ($data['last_name'] ?? ''),
                    'email' => (string) ($data['email'] ?? ''),
                    'contact_number' => (string) ($data['contact_number'] ?? ''),
                    'role_id' => 4, // Plan holder role
                    'is_plan_holder' => 1,
                    'status' => 'active',
                    'account_status' => 'verified',
                ];

                $userId = $userModel->insert($newUser);
                if (! $userId) {
                    throw new \RuntimeException('Failed to create user account.');
                }
            }

            $planHolderData = [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'address_no' => (string) ($data['address_no'] ?? ''),
                'address_street' => (string) ($data['address_street'] ?? ''),
                'address_barangay' => (string) ($data['address_barangay'] ?? ''),
                'address_city' => (string) ($data['address_city'] ?? ''),
                'date_of_birth' => $this->nullable($data['date_of_birth'] ?? null),
                'place_of_birth' => (string) ($data['place_of_birth'] ?? ''),
                'age' => $this->nullableInt($data['age'] ?? null),
                'gender' => (string) ($data['gender'] ?? ''),
                'civil_status' => (string) ($data['civil_status'] ?? ''),
                'citizenship' => (string) ($data['citizenship'] ?? ''),
                'height' => $this->nullable($data['height'] ?? null),
                'weight' => $this->nullable($data['weight'] ?? null),
                'spouse_name' => (string) ($data['spouse_name'] ?? ''),
                'spouse_birthdate' => $this->nullable($data['spouse_birthdate'] ?? null),
                'spouse_occupation' => (string) ($data['spouse_occupation'] ?? ''),
                'senior_citizen_id' => (string) ($data['senior_citizen_id'] ?? ''),
                'organization_affiliation' => (string) ($data['organization_affiliation'] ?? ''),
                'status' => 'active',
                'is_linked_account' => $existingUser ? 1 : 0,
            ];

            $existingPlanHolder = $planHolderModel
                ->where('user_id', $userId)
                ->orderBy('plan_holder_id', 'DESC')
                ->first();

            if ($existingPlanHolder) {
                $planHolderId = (int) $existingPlanHolder['plan_holder_id'];

                $updateData = $planHolderData;
                if (! empty($existingPlanHolder['unique_identifier'])) {
                    $updateData['unique_identifier'] = (string) $existingPlanHolder['unique_identifier'];
                } else {
                    $updateData['unique_identifier'] = $this->generateUniqueIdentifier(
                        (string) ($data['first_name'] ?? ''),
                        (string) ($data['last_name'] ?? '')
                    );
                }

                $updated = $planHolderModel->update($planHolderId, $updateData);
                if (! $updated) {
                    throw new \RuntimeException('Failed to update existing plan holder record.');
                }
            } else {
                $planHolderData['unique_identifier'] = $this->generateUniqueIdentifier(
                    (string) ($data['first_name'] ?? ''),
                    (string) ($data['last_name'] ?? '')
                );

                $planHolderId = $planHolderModel->insert($planHolderData);
                if (! $planHolderId) {
                    throw new \RuntimeException('Failed to create plan holder record.');
                }
            }

            // Create plan only when missing for this plan holder profile.
            $this->createPlanForPlanHolder($planHolderId);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed during registration.');
            }

            $db->transCommit();

            return $planHolderId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Create a plan for a registered plan holder
     */
    private function createPlanForPlanHolder(int $planHolderId): void
    {
        $db = db_connect();

        $existingPlan = $db->table('plans')
            ->select('plan_id')
            ->where('plan_holder_id', $planHolderId)
            ->orderBy('plan_id', 'DESC')
            ->get()
            ->getRowArray();

        if ($existingPlan) {
            return;
        }

        $packageData = $this->resolveMembershipPackage();
        $packageId = (int) $packageData['package_id'];
        $effectiveMonthlyFee = MembershipService::MONTHLY_FEE;
        $initialBalance = $effectiveMonthlyFee * 12;
        $versionId = (int) $packageData['version_id'];

        $planData = [
            'plan_holder_id' => $planHolderId,
            'package_id' => $packageId,
            'monthly_fee' => $effectiveMonthlyFee,
            'start_date' => date('Y-m-d'),
            'status' => 'active',
            'months_paid' => 0,
            'remaining_balance' => number_format($initialBalance, 2, '.', ''),
            'version_id' => $versionId,
        ];

        $inserted = $db->table('plans')->insert($planData);
        if (! $inserted) {
            throw new \RuntimeException('Failed to create plan record.');
        }
    }

    private function resolveMembershipPackage(): array
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

            return [
                'package_id' => $packageId,
                'version_id' => $versionId,
            ];
        }

        return [
            'package_id' => $packageId,
            'version_id' => (int) $version['version_id'],
        ];
    }

    /**
     * Generate a unique identifier for plan holder
     * Format: LASTNAME-FIRSTNAME-TIMESTAMP
     */
    private function generateUniqueIdentifier(string $firstName, string $lastName): string
    {
        $base = strtoupper(str_replace(' ', '', $lastName)) . '-' . strtoupper(str_replace(' ', '', $firstName));
        $timestamp = substr((string) time(), -6);

        return $base . '-' . $timestamp;
    }

    /**
     * Get available packages for plan holder selection
     */
    public function getAvailablePackages(): array
    {
        return db_connect()->table('packages')
            ->select('package_id, package_name, base_price')
            ->where('is_available', 1)
            ->orderBy('package_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
