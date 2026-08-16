<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
use App\Services\MembershipService;
use App\Services\NotificationService;

/**
 * ClientServiceController
 * 
 * Handles service and package browsing, details, and applications
 * Part of the refactored ClientPortal controller
 */
class ClientServiceController extends BaseController
{
    use ClientPortalTrait;

    /**
     * Display services and packages catalog
     */
    public function services(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $activeTab = (string) $this->request->getGet('tab');
        if (! in_array($activeTab, ['services', 'packages'], true)) {
            $activeTab = 'services';
        }

        $services = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price, is_available')
            ->where('is_available', 1)
            ->orderBy('service_name', 'ASC')
            ->get()
            ->getResultArray();

        $packages = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_available')
            ->orderBy('package_name', 'ASC')
            ->get()
            ->getResultArray();

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        // Get membership information for eligibility display and compute can_apply
        $membershipService = new MembershipService();
        $membership = $planHolderId > 0 ? $membershipService->getMembershipSummary($planHolderId) : null;

        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        return view('client/services', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'active_tab' => $activeTab,
            'services' => $services,
            'packages' => $packages,
            'total_services' => count($services),
            'total_packages' => count($packages),
            'can_apply' => $canApply,
            'membership' => $membership ?? [],
        ]);
    }

    /**
     * Display service details
     */
    public function serviceDetails(int $serviceListId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Service not found.');
        }

        return view('client/service_details', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'service' => $service,
        ]);
    }

    /**
     * Display package details with included services
     */
    public function packageDetails(int $packageId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Package not found.');
        }

        $packageServices = [];
        $db = db_connect();
        if ($db->tableExists('package_services') && $db->tableExists('service_list')) {
            $psFields = $db->getFieldNames('package_services');
            if (in_array('service_list_id', $psFields, true)) {
                $packageServices = $db->table('package_services ps')
                    ->select('sl.service_list_id, sl.service_name, sl.description, sl.base_price')
                    ->join('service_list sl', 'sl.service_list_id = ps.service_list_id', 'inner')
                    ->where('ps.package_id', $packageId)
                    ->orderBy('sl.service_name', 'ASC')
                    ->get()
                    ->getResultArray();
            } elseif (in_array('service_id', $psFields, true)) {
                $packageServices = $db->table('package_services ps')
                    ->select('sl.service_list_id, sl.service_name, sl.description, sl.base_price')
                    ->join('service_list sl', 'sl.service_list_id = ps.service_id', 'inner')
                    ->where('ps.package_id', $packageId)
                    ->orderBy('sl.service_name', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        }

        $variantModel = new \App\Models\PackageVariantModel();
        $inclusionModel = new \App\Models\PackageInclusionModel();
        $addOnModel = new \App\Models\AddOnModel();

        $variants = $variantModel->getActiveVariants($packageId);
        $inclusions = $inclusionModel->getActiveInclusions($packageId);
        $addOns = $addOnModel->getActiveAddOns('optional');

        return view('client/package_details', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'package' => $package,
            'package_services' => $packageServices,
            'variants' => $variants,
            'inclusions' => $inclusions,
            'add_ons' => $addOns,
        ]);
    }

    /**
     * Display Balik Probinsya service application form
     */
    public function applyBalikProbinsyaForm(int $serviceListId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->where('service_name', 'Balik Probinsya')
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Balik Probinsya service not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        $applicationContext = $this->buildApplicationContext($access);

        // Load rates for Balik Probinsya
        $rateModel = new \App\Models\ServiceRateModel();
        $rates = $rateModel->getActiveRates($serviceListId);

        // Check Damayan eligibility
        $isDamayanMember = false;
        if ($planHolderId > 0) {
            $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember($planHolderId);
        }

        return view('client/balik_probinsya_apply', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'service' => $service,
            'can_apply' => $canApply,
            'application_context' => $applicationContext,
            'rates' => $rates,
            'isDamayanMember' => $isDamayanMember,
        ]);
    }

    /**
     * Display service application form
     */
    public function applyServiceForm(int $serviceListId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Service not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        $applicationContext = $this->buildApplicationContext($access);

        return view('client/service_apply', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'service' => $service,
            'can_apply' => $canApply,
            'application_context' => $applicationContext,
        ]);
    }

    /**
     * Display package application form
     */
    public function applyPackageForm(int $packageId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Package not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        $applicationContext = $this->buildApplicationContext($access);

        return view('client/package_apply', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'package' => $package,
            'can_apply' => $canApply,
            'application_context' => $applicationContext,
        ]);
    }

    /**
     * Build display data for the simplified application form.
     *
     * @return array{plan_holder_name:string,plan_holder_address:string,deceased_name_options:array<int,string>}
     */
    private function buildApplicationContext(array $access): array
    {
        $user = is_array($access['user'] ?? null) ? $access['user'] : [];
        $planHolder = is_array($access['plan_holder'] ?? null) ? $access['plan_holder'] : [];
        $planHolderId = (int) ($planHolder['plan_holder_id'] ?? 0);

        $planHolderName = trim(implode(' ', array_filter([
            (string) ($user['first_name'] ?? ''),
            (string) ($user['middle_name'] ?? ''),
            (string) ($user['last_name'] ?? ''),
        ], static fn (string $value): bool => $value !== '')));

        if ($planHolderName === '') {
            $planHolderName = 'Plan Holder';
        }

        $addressParts = array_filter([
            trim((string) ($planHolder['address_no'] ?? '')),
            trim((string) ($planHolder['address_street'] ?? '')),
            trim((string) ($planHolder['address_barangay'] ?? '')),
            trim((string) ($planHolder['address_city'] ?? '')),
        ], static fn (string $value): bool => $value !== '');

        $planHolderAddress = trim(implode(', ', $addressParts));
        if ($planHolderAddress === '') {
            $planHolderAddress = '-';
        }

        $deceasedNameOptions = [$planHolderName];
        if ($planHolderId > 0) {
            $db = db_connect();
            if ($db->tableExists('beneficiaries')) {
                $beneficiaries = $db->table('beneficiaries')
                    ->select('first_name, middle_name, last_name')
                    ->where('plan_holder_id', $planHolderId)
                    ->orderBy('beneficiary_id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($beneficiaries as $beneficiary) {
                    $name = trim(implode(' ', array_filter([
                        (string) ($beneficiary['first_name'] ?? ''),
                        (string) ($beneficiary['middle_name'] ?? ''),
                        (string) ($beneficiary['last_name'] ?? ''),
                    ], static fn (string $value): bool => $value !== '')));

                    if ($name !== '') {
                        $deceasedNameOptions[] = $name;
                    }
                }
            }
        }

        return [
            'plan_holder_name' => $planHolderName,
            'plan_holder_address' => $planHolderAddress,
            'deceased_name_options' => array_values(array_unique($deceasedNameOptions)),
        ];
    }

    /**
     * Submit service application
     */
    public function submitServiceApplication(int $serviceListId)
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        if (($access['state'] ?? 'new') === 'new') {
            return redirect()->to('/plan-info')->with('error', 'You must register as a Plan Holder to apply.');
        }

        $state = (string) ($access['state'] ?? 'new');
        if (! in_array($state, ['approved', 'active'], true)) {
            return redirect()->back()->with('error', 'Access denied. Approval required before requesting services.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);
        if ($monthsPaid < 2) {
            return redirect()->back()->with('error', 'You must complete at least 2 months of payments before requesting services.');
        }

        $planHolder = $access['plan_holder'];
        if (! $planHolder) {
            return redirect()->to('/plan-info')->with('error', 'No plan holder profile found. Please complete registration first.');
        }

        // Check service eligibility based on membership state
        $membershipService = new MembershipService();
        if (! $membershipService->canAccessServices((int) $planHolder['plan_holder_id'])) {
            $membership = $membershipService->getMembershipSummary((int) $planHolder['plan_holder_id']);
            if ($membership && (int) ($membership['overdue_months'] ?? 0) > 2) {
                return redirect()->back()->with('error', 'Your membership is currently delinquent. Please update your monthly contributions to access funeral services.');
            }

            return redirect()->back()->with('error', 'Your membership status does not allow service access at this time.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->with('error', 'Selected service is unavailable.');
        }

        // Check for duplicate pending application
        $existingApp = db_connect()->table('service_applications')
            ->where('plan_holder_id', (int) $planHolder['plan_holder_id'])
            ->where('service_list_id', (int) $serviceListId)
            ->where('status', 'pending')
            ->countAllResults();

        if ($existingApp > 0) {
            return redirect()->back()->with('error', 'You already have a pending application for this service. Please wait for it to be processed.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $insert = [
                'plan_holder_id' => (int) $planHolder['plan_holder_id'],
                'service_list_id' => (int) $serviceListId,
                'status' => 'pending',
                'deceased_name' => trim((string) $this->request->getPost('deceased_name')) ?: null,
                'deceased_date_of_death' => $this->nullablePost('deceased_date_of_death'),
                'deceased_address' => trim((string) $this->request->getPost('deceased_address')) ?: null,
                'relationship_to_deceased' => trim((string) $this->request->getPost('relationship_to_deceased')) ?: null,
                'beneficiary_name' => trim((string) $this->request->getPost('beneficiary_name')) ?: null,
                'beneficiary_contact' => trim((string) $this->request->getPost('beneficiary_contact')) ?: null,
                'application_notes' => trim((string) $this->request->getPost('application_notes')) ?: null,
            ];

            $builder = $db->table('service_applications');
            $ok = $builder->insert($insert);
            $dbError = $db->error() ?? [];
            if (! $ok || (! empty($dbError) && ((int) ($dbError['code'] ?? 0)) !== 0)) {
                log_message('error', 'Failed inserting service application: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error (insert): ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $applicationId = (int) $db->insertID();

            // Handle uploaded documents
            $files = $this->request->getFileMultiple('documents');
            if (is_array($files) && $files !== []) {
                $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $docModel = new \App\Models\ServiceApplicationDocumentModel();
                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $newName = $file->getRandomName();
                    $moved = $file->move($targetDir, $newName);
                    if ($moved) {
                        $docOk = $docModel->insert([
                            'application_id' => $applicationId,
                            'filename' => $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                            'uploaded_by' => (int) $user['user_id'],
                        ]);
                        if (! $docOk) {
                            $docDbErr = $db->error() ?? [];
                            log_message('error', 'Failed inserting service application document: ' . json_encode($docDbErr));
                        }
                    }
                }
            }

            (new NotificationService())->notify(
                (int) $user['user_id'],
                'Your application for ' . (string) $service['service_name'] . ' has been submitted.',
                'service_pending'
            );

            // Notify branch admin(s)
            $planHolderUserId = (int) $planHolder['user_id'];
            $branchId = (int) ($planHolder['branch_id'] ?? 0);
            if ($branchId > 0) {
                $branchAdmins = $db->table('users')
                    ->select('user_id')
                    ->where('branch_id', $branchId)
                    ->whereIn('role_id', [2])
                    ->where('status', 'active')
                    ->get()
                    ->getResultArray();
                $clientName = trim((string) $user['first_name'] ?? '') . ' ' . trim((string) $user['last_name'] ?? '');
                foreach ($branchAdmins as $admin) {
                    (new NotificationService())->notify(
                        (int) $admin['user_id'],
                        'New service application: ' . trim($clientName) . ' applied for ' . (string) $service['service_name'] . '.',
                        'service_pending'
                    );
                }
            }

            if ($db->transStatus() === false) {
                $dbError = $db->error() ?? [];
                log_message('error', 'Service application DB transaction failed: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error: ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $db->transCommit();

            return view('client/service_confirmation', [
                'role_layout' => 'layouts/plan_holder',
                'item_name' => (string) $service['service_name'],
                'item_type' => 'Service',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit Balik Probinsya service application with rate calculation
     */
    public function submitBalikProbinsyaApplication(int $serviceListId)
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        if (($access['state'] ?? 'new') === 'new') {
            return redirect()->to('/plan-info')->with('error', 'You must register as a Plan Holder to apply.');
        }

        $state = (string) ($access['state'] ?? 'new');
        if (! in_array($state, ['approved', 'active'], true)) {
            return redirect()->back()->with('error', 'Access denied. Approval required before requesting services.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);
        if ($monthsPaid < 2) {
            return redirect()->back()->with('error', 'You must complete at least 2 months of payments before requesting services.');
        }

        $planHolder = $access['plan_holder'];
        if (! $planHolder) {
            return redirect()->to('/plan-info')->with('error', 'No plan holder profile found. Please complete registration first.');
        }

        $membershipService = new MembershipService();
        if (! $membershipService->canAccessServices((int) $planHolder['plan_holder_id'])) {
            $membership = $membershipService->getMembershipSummary((int) $planHolder['plan_holder_id']);
            if ($membership && (int) ($membership['overdue_months'] ?? 0) > 2) {
                return redirect()->back()->with('error', 'Your membership is currently delinquent. Please update your monthly contributions to access funeral services.');
            }

            return redirect()->back()->with('error', 'Your membership status does not allow service access at this time.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->where('service_name', 'Balik Probinsya')
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->with('error', 'Selected service is unavailable.');
        }

        // Rate validation
        $origin = trim((string) $this->request->getPost('origin'));
        $destination = trim((string) $this->request->getPost('destination'));
        if ($origin === '' || $destination === '') {
            return redirect()->back()->withInput()->with('error', 'Please select both origin and destination.');
        }

        $rateModel = new \App\Models\ServiceRateModel();
        $rate = $rateModel->getRate($serviceListId, $origin, $destination);
        if (! $rate) {
            return redirect()->back()->withInput()->with('error', 'No rate found for the selected route. Please choose a different combination.');
        }

        $rateAmount = (float) $rate['rate'];

        // Damayan credit
        $damayanCredit = 0.0;
        if ($planHolderId > 0 && (new \App\Services\DamayanService())->isQualifiedMember($planHolderId)) {
            if ($rateAmount > 20000) {
                $damayanCredit = 14500.0;
            }
        }

        $finalAmount = max(0.0, $rateAmount - $damayanCredit);

        // Check for duplicate pending application
        $existingApp = db_connect()->table('service_applications')
            ->where('plan_holder_id', (int) $planHolder['plan_holder_id'])
            ->where('service_list_id', (int) $serviceListId)
            ->where('status', 'pending')
            ->countAllResults();

        if ($existingApp > 0) {
            return redirect()->back()->with('error', 'You already have a pending application for this service. Please wait for it to be processed.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $insert = [
                'plan_holder_id' => (int) $planHolder['plan_holder_id'],
                'service_list_id' => (int) $serviceListId,
                'status' => 'pending',
                'service_type' => 'balik_probinsya',
                'origin' => $origin,
                'destination' => $destination,
                'deceased_name' => trim((string) $this->request->getPost('deceased_name')) ?: null,
                'deceased_date_of_death' => $this->nullablePost('deceased_date_of_death'),
                'deceased_address' => trim((string) $this->request->getPost('deceased_address')) ?: null,
                'relationship_to_deceased' => trim((string) $this->request->getPost('relationship_to_deceased')) ?: null,
                'beneficiary_name' => trim((string) $this->request->getPost('beneficiary_name')) ?: null,
                'beneficiary_contact' => trim((string) $this->request->getPost('beneficiary_contact')) ?: null,
                'application_notes' => trim((string) $this->request->getPost('application_notes')) ?: null,
                'estimated_cost' => number_format($rateAmount, 2, '.', ''),
                'damayan_credit' => $damayanCredit > 0 ? number_format($damayanCredit, 2, '.', '') : null,
                'final_amount' => number_format($finalAmount, 2, '.', ''),
            ];

            $builder = $db->table('service_applications');
            $ok = $builder->insert($insert);
            $dbError = $db->error() ?? [];
            if (! $ok || (! empty($dbError) && ((int) ($dbError['code'] ?? 0)) !== 0)) {
                log_message('error', 'Failed inserting Balik Probinsya application: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error (insert): ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $applicationId = (int) $db->insertID();

            // Handle uploaded documents
            $files = $this->request->getFileMultiple('documents');
            if (is_array($files) && $files !== []) {
                $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $docModel = new \App\Models\ServiceApplicationDocumentModel();
                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $newName = $file->getRandomName();
                    $moved = $file->move($targetDir, $newName);
                    if ($moved) {
                        $docOk = $docModel->insert([
                            'application_id' => $applicationId,
                            'filename' => $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                            'uploaded_by' => (int) $user['user_id'],
                        ]);
                        if (! $docOk) {
                            $docDbErr = $db->error() ?? [];
                            log_message('error', 'Failed inserting Balik Probinsya application document: ' . json_encode($docDbErr));
                        }
                    }
                }
            }

            (new NotificationService())->notify(
                (int) $user['user_id'],
                'Your Balik Probinsya application (' . esc($origin) . ' → ' . esc($destination) . ') has been submitted. Amount due: ₱' . number_format($finalAmount, 2) . '.',
                'service_pending'
            );

            // Notify branch admin(s)
            $branchId = (int) ($planHolder['branch_id'] ?? 0);
            if ($branchId > 0) {
                $branchAdmins = $db->table('users')
                    ->select('user_id')
                    ->where('branch_id', $branchId)
                    ->whereIn('role_id', [2])
                    ->where('status', 'active')
                    ->get()
                    ->getResultArray();
                $clientName = trim((string) $user['first_name'] ?? '') . ' ' . trim((string) $user['last_name'] ?? '');
                foreach ($branchAdmins as $admin) {
                    (new NotificationService())->notify(
                        (int) $admin['user_id'],
                        'New Balik Probinsya application: ' . trim($clientName) . ' applied for ' . esc($origin) . ' → ' . esc($destination) . '.',
                        'service_pending'
                    );
                }
            }

            if ($db->transStatus() === false) {
                $dbError = $db->error() ?? [];
                log_message('error', 'Balik Probinsya application DB transaction failed: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error: ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $db->transCommit();

            return view('client/service_confirmation', [
                'role_layout' => 'layouts/plan_holder',
                'item_name' => (string) $service['service_name'] . ' (' . esc($origin) . ' → ' . esc($destination) . ')',
                'item_type' => 'Service',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit package application
     */
    public function submitPackageApplication(int $packageId)
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        if (($access['state'] ?? 'new') === 'new') {
            return redirect()->to('/plan-info')->with('error', 'You must register as a Plan Holder to apply.');
        }

        $state = (string) ($access['state'] ?? 'new');
        if (! in_array($state, ['approved', 'active'], true)) {
            return redirect()->back()->with('error', 'Access denied. Approval required before requesting services.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);
        if ($monthsPaid < 2) {
            return redirect()->back()->with('error', 'You must complete at least 2 months of payments before requesting services.');
        }

        $planHolder = $access['plan_holder'];
        if (! $planHolder) {
            return redirect()->to('/plan-info')->with('error', 'No plan holder profile found. Please complete registration first.');
        }

        // Check service eligibility based on membership state
        $membershipService = new MembershipService();
        if (! $membershipService->canAccessServices((int) $planHolder['plan_holder_id'])) {
            $membership = $membershipService->getMembershipSummary((int) $planHolder['plan_holder_id']);
            if ($membership && (int) ($membership['overdue_months'] ?? 0) > 2) {
                return redirect()->back()->with('error', 'Your membership is currently delinquent. Please update your monthly contributions to access funeral services.');
            }

            return redirect()->back()->with('error', 'Your membership status does not allow service access at this time.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->back()->with('error', 'Selected package is unavailable.');
        }

        // Check for duplicate pending application
        $existingApp = db_connect()->table('service_applications')
            ->where('plan_holder_id', (int) $planHolder['plan_holder_id'])
            ->where('package_id', (int) $packageId)
            ->where('status', 'pending')
            ->countAllResults();

        if ($existingApp > 0) {
            return redirect()->back()->with('error', 'You already have a pending application for this package. Please wait for it to be processed.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $insert = [
                'plan_holder_id' => (int) $planHolder['plan_holder_id'],
                'package_id' => (int) $packageId,
                'status' => 'pending',
                'deceased_name' => trim((string) $this->request->getPost('deceased_name')) ?: null,
                'deceased_date_of_death' => $this->nullablePost('deceased_date_of_death'),
                'deceased_address' => trim((string) $this->request->getPost('deceased_address')) ?: null,
                'relationship_to_deceased' => trim((string) $this->request->getPost('relationship_to_deceased')) ?: null,
                'beneficiary_name' => trim((string) $this->request->getPost('beneficiary_name')) ?: null,
                'beneficiary_contact' => trim((string) $this->request->getPost('beneficiary_contact')) ?: null,
                'application_notes' => trim((string) $this->request->getPost('application_notes')) ?: null,
            ];

            $builder = $db->table('service_applications');
            $ok = $builder->insert($insert);
            $dbError = $db->error() ?? [];
            if (! $ok || (! empty($dbError) && ((int) ($dbError['code'] ?? 0)) !== 0)) {
                log_message('error', 'Failed inserting package application: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error (insert): ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $applicationId = (int) $db->insertID();

            // Handle uploaded documents
            $files = $this->request->getFileMultiple('documents');
            if (is_array($files) && $files !== []) {
                $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $docModel = new \App\Models\ServiceApplicationDocumentModel();
                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $newName = $file->getRandomName();
                    $moved = $file->move($targetDir, $newName);
                    if ($moved) {
                        $docOk = $docModel->insert([
                            'application_id' => $applicationId,
                            'filename' => $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                            'uploaded_by' => (int) $user['user_id'],
                        ]);
                        if (! $docOk) {
                            $docDbErr = $db->error() ?? [];
                            log_message('error', 'Failed inserting package application document: ' . json_encode($docDbErr));
                        }
                    }
                }
            }

            (new NotificationService())->notify(
                (int) $user['user_id'],
                'Your application for ' . (string) $package['package_name'] . ' has been submitted.',
                'service_pending'
            );

            // Notify branch admin(s)
            $branchId = (int) ($planHolder['branch_id'] ?? 0);
            if ($branchId > 0) {
                $branchAdmins = $db->table('users')
                    ->select('user_id')
                    ->where('branch_id', $branchId)
                    ->whereIn('role_id', [2])
                    ->where('status', 'active')
                    ->get()
                    ->getResultArray();
                $clientName = trim((string) $user['first_name'] ?? '') . ' ' . trim((string) $user['last_name'] ?? '');
                foreach ($branchAdmins as $admin) {
                    (new NotificationService())->notify(
                        (int) $admin['user_id'],
                        'New package application: ' . trim($clientName) . ' applied for ' . (string) $package['package_name'] . '.',
                        'service_pending'
                    );
                }
            }

            if ($db->transStatus() === false) {
                $dbError = $db->error() ?? [];
                log_message('error', 'Package application DB transaction failed: ' . json_encode($dbError));
                $errMsg = 'Failed to submit application.';
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $errMsg .= ' DB error: ' . ($dbError['message'] ?? json_encode($dbError));
                }
                throw new \RuntimeException($errMsg);
            }

            $db->transCommit();

            return view('client/service_confirmation', [
                'role_layout' => 'layouts/plan_holder',
                'item_name' => (string) $package['package_name'],
                'item_type' => 'Package',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display Balik Probinsya detail page
     * Dedicated SEO-friendly route: /client/services/balik-probinsya
     */
    public function balikProbinsyaDetail(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price')
            ->where('service_name', 'Balik Probinsya')
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Balik Probinsya service not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        // Load rates for Balik Probinsya
        $rateModel = new \App\Models\ServiceRateModel();
        $rates = $rateModel->getActiveRates($service['service_list_id']);

        // Check Damayan eligibility
        $isDamayanMember = false;
        if ($planHolderId > 0) {
            $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember($planHolderId);
        }

        return view('client/balik_probinsya_detail', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'service' => $service,
            'can_apply' => $canApply,
            'rates' => $rates,
            'isDamayanMember' => $isDamayanMember,
        ]);
    }

    /**
     * Display Wood Casket package detail page
     * Dedicated SEO-friendly route: /client/packages/wood-casket
     */
    public function woodCasketDetail(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable')
            ->where('package_name', 'Wood Casket')
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Wood Casket package not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        $variantModel = new \App\Models\PackageVariantModel();
        $inclusionModel = new \App\Models\PackageInclusionModel();
        $addOnModel = new \App\Models\AddOnModel();

        $variants = $variantModel->getActiveVariants($package['package_id']);
        $inclusions = $inclusionModel->getActiveInclusions($package['package_id']);
        $addOns = $addOnModel->getActiveAddOns('optional');

        // Check Damayan eligibility
        $isDamayanMember = false;
        if ($planHolderId > 0) {
            $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember($planHolderId);
        }

        return view('client/package_wood_casket_detail', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'package' => $package,
            'can_apply' => $canApply,
            'variants' => $variants,
            'inclusions' => $inclusions,
            'add_ons' => $addOns,
            'isDamayanMember' => $isDamayanMember,
        ]);
    }

    /**
     * Display Metal Casket package detail page
     * Dedicated SEO-friendly route: /client/packages/metal-casket
     */
    public function metalCasketDetail(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable')
            ->where('package_name', 'Metal Casket')
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Metal Casket package not found.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;
        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        $variantModel = new \App\Models\PackageVariantModel();
        $inclusionModel = new \App\Models\PackageInclusionModel();
        $addOnModel = new \App\Models\AddOnModel();

        $variants = $variantModel->getActiveVariants($package['package_id']);
        $inclusions = $inclusionModel->getActiveInclusions($package['package_id']);
        $addOns = $addOnModel->getActiveAddOns('optional');

        // Check Damayan eligibility
        $isDamayanMember = false;
        if ($planHolderId > 0) {
            $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember($planHolderId);
        }

        return view('client/package_metal_casket_detail', [
            'role_layout' => 'layouts/plan_holder',
            'access' => $access,
            'package' => $package,
            'can_apply' => $canApply,
            'variants' => $variants,
            'inclusions' => $inclusions,
            'add_ons' => $addOns,
            'isDamayanMember' => $isDamayanMember,
        ]);
    }
}
