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

            foreach ($beneficiariesInput as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $birthday = trim((string) ($row['birthday'] ?? ''));
                $relationship = trim((string) ($row['relationship'] ?? ''));

                // Skip completely empty rows
                if ($name === '' && $birthday === '' && $relationship === '') {
                    continue;
                }

                // If any field is filled, require at least name and relationship
                if ($name === '' || $relationship === '') {
                    throw new \RuntimeException('All beneficiary fields must be filled if you provide any information. Please fill Name and Relationship for each beneficiary.');
                }

                $nameParts = $this->parseBeneficiaryName($name);

                $beneficiaries[] = $this->filterTableData('beneficiaries', [
                    'plan_holder_id' => $planHolderId,
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'name_extension' => $nameParts['name_extension'],
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
