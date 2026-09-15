<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\CycleService;
use App\Services\MembershipService;
use App\Services\NotificationService;

/**
 * ClientServiceController
 *
 * Handles service and package browsing, details, and applications
 * Part of the refactored ClientPortal controller
 *
 * Services & Packages redesign: the Regular Wood Casket package
 * (packages.is_damayan_entitlement = 1) is the standard Damayan Plan
 * Holder entitlement - it shows CLAIM (free, once the ₱14,500 contribution
 * target is fully paid) instead of AVAILABLE. Everything else in this
 * controller (browsing, details, apply forms, submission, document
 * uploads) is the same architecture used before the redesign, just
 * extended with the entitlement/benefit-preview data the new views need.
 */
class ClientServiceController extends BaseController
{
    use ClientPortalTrait;

    /**
     * Flat ₱1,500 optional add-on price used across every package's apply
     * flow, per the brief's own worked example (₱20,000 + ₱1,500 =
     * ₱21,500).
     */
    private const BURIAL_ATTIRE_PRICE = 1500.0;

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
            ->select('service_list_id, service_name, description, base_price, image_path')
            ->where('is_available', 1)
            ->orderBy('service_name', 'ASC')
            ->get()
            ->getResultArray();

        $routesByService = $this->routesGroupedByService(array_column($services, 'service_list_id'));
        foreach ($services as &$svc) {
            $svc['routes'] = $routesByService[(int) $svc['service_list_id']] ?? [];
        }
        unset($svc);

        $packages = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_damayan_entitlement, image_path')
            ->where('is_available', 1)
            ->orderBy('is_damayan_entitlement', 'DESC')
            ->orderBy('base_price', 'ASC')
            ->get()
            ->getResultArray();

        [$canApply, $membership, $planId] = $this->resolveEligibility($access);
        $claimStatus = $this->entitlementClaimStatus($canApply, $membership, $planId);

        foreach ($packages as &$pkg) {
            $isEntitlement = (int) ($pkg['is_damayan_entitlement'] ?? 0) === 1;
            if ($isEntitlement) {
                $eligible = $claimStatus['eligible'];
                $pkg['badge_label'] = $eligible ? 'CLAIM' : 'YOUR ENTITLEMENT';
                $pkg['badge_class'] = $eligible ? 'success' : 'secondary';
                $pkg['action_label'] = 'CLAIM';
                $pkg['can_claim'] = $eligible;
                $pkg['claim_locked_reason'] = $claimStatus['reason'];
            } else {
                $pkg['badge_label'] = 'AVAILABLE';
                $pkg['badge_class'] = 'info';
                $pkg['action_label'] = 'AVAIL PACKAGE';
                $pkg['can_claim'] = false;
                $pkg['claim_locked_reason'] = null;
            }
        }
        unset($pkg);

        return view('client/services', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Services & Packages',
            'page_sub' => 'Browse available funeral services and casket packages.',
            'access' => $access,
            'active_tab' => $activeTab,
            'services' => $services,
            'packages' => $packages,
            'can_apply' => $canApply,
            'membership' => $membership ?? [],
        ]);
    }

    /**
     * Display service details (e.g. Balik Probinsya, with its per-route
     * pricing).
     */
    public function serviceDetails(int $serviceListId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $service = db_connect()->table('service_list')
            ->select('service_list_id, service_name, description, base_price, image_path')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Service not found.');
        }

        $routes = db_connect()->table('service_routes')
            ->where('service_list_id', $serviceListId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('price', 'DESC')
            ->get()
            ->getResultArray();

        [$canApply, $membership] = $this->resolveEligibility($access);

        // Casket benefit note: a qualified Plan Holder's Damayan casket
        // entitlement is covered separately (the Regular Wood Casket
        // claim) - it never reduces this transport service's own route
        // price, it's simply disclosed here so the plan holder knows they
        // won't be asked to pay for a casket again on top of this.
        foreach ($routes as &$route) {
            $route['casket_benefit_covered'] = $canApply;
        }
        unset($route);

        return view('client/service_details', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => (string) ($service['service_name'] ?? 'Service'),
            'show_page_head' => false,
            'access' => $access,
            'service' => $service,
            'routes' => $routes,
            'can_apply' => $canApply,
        ]);
    }

    /**
     * Display package details with inclusions and, where relevant, the
     * Damayan entitlement/benefit information.
     */
    public function packageDetails(int $packageId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable, is_damayan_entitlement, image_path')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Package not found.');
        }

        $inclusions = db_connect()->table('package_items')
            ->select('item_id, item_name, description')
            ->where('package_id', $packageId)
            ->orderBy('item_id', 'ASC')
            ->get()
            ->getResultArray();

        [$canApply, $membership, $planId] = $this->resolveEligibility($access);
        $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;

        $entitlement = null;
        $benefitCredit = 0.0;
        if ($isEntitlement) {
            $claimStatus = $this->entitlementClaimStatus($canApply, $membership, $planId);
            $entitlement = [
                'eligible' => $claimStatus['eligible'],
                'locked_reason' => $claimStatus['reason'],
            ];
        } elseif ($canApply) {
            // Panel damayan credit: exactly TOTAL_CONTRIBUTION, capped so it
            // can never exceed the package price itself.
            $benefitCredit = min((float) $package['base_price'], MembershipService::TOTAL_CONTRIBUTION);
        }

        return view('client/package_details', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => (string) ($package['package_name'] ?? 'Package'),
            'show_page_head' => false,
            'access' => $access,
            'package' => $package,
            'inclusions' => $inclusions,
            'is_entitlement' => $isEntitlement,
            'entitlement' => $entitlement,
            'benefit_credit' => $benefitCredit,
            'can_apply' => $canApply,
            'attire_price' => self::BURIAL_ATTIRE_PRICE,
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
            ->select('service_list_id, service_name, description, base_price, image_path')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->to('/client/service?tab=services')->with('error', 'Service not found.');
        }

        $routes = db_connect()->table('service_routes')
            ->where('service_list_id', $serviceListId)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        [$canApply, $membership] = $this->resolveEligibility($access);

        return view('client/service_apply', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Apply for Service',
            'page_sub' => 'Confirm your service request.',
            'access' => $access,
            'service' => $service,
            'routes' => $routes,
            'can_apply' => $canApply,
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
            ->select('package_id, package_name, description, base_price, is_customizable, is_damayan_entitlement, image_path')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Package not found.');
        }

        [$canApply, $membership, $planId] = $this->resolveEligibility($access);
        $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;

        if ($isEntitlement) {
            $claimStatus = $this->entitlementClaimStatus($canApply, $membership, $planId);
            if (! $claimStatus['eligible']) {
                return redirect()->to('/client/service?tab=packages')
                    ->with('error', (string) $claimStatus['reason']);
            }
        }

        $benefitCredit = (! $isEntitlement && $canApply)
            ? min((float) $package['base_price'], MembershipService::TOTAL_CONTRIBUTION)
            : 0.0;

        return view('client/package_apply', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => $isEntitlement ? 'Claim Regular Casket' : 'Avail Package',
            'page_sub' => $isEntitlement
                ? 'Claim your Damayan entitlement. Staff or an Encoder will review and process your claim.'
                : 'Confirm your package selection. Staff or an Encoder will review and process your application.',
            'access' => $access,
            'package' => $package,
            'is_entitlement' => $isEntitlement,
            'benefit_credit' => $benefitCredit,
            'can_apply' => $canApply,
            'attire_price' => self::BURIAL_ATTIRE_PRICE,
        ]);
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
            ->select('service_list_id, service_name, base_price')
            ->where('service_list_id', $serviceListId)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (! $service) {
            return redirect()->back()->with('error', 'Selected service is unavailable.');
        }

        // Resolve the selected route (if any) - the route price is what's
        // actually owed, the casket benefit note never reduces it.
        $routeId = (int) $this->request->getPost('route_id');
        $applicationAmount = (float) $service['base_price'];
        if ($routeId > 0) {
            $route = db_connect()->table('service_routes')
                ->where('route_id', $routeId)
                ->where('service_list_id', $serviceListId)
                ->get()
                ->getRowArray();

            if (! $route) {
                return redirect()->back()->withInput()->with('error', 'Selected route is invalid.');
            }

            $applicationAmount = (float) $route['price'];
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
                'selected_route_id' => $routeId > 0 ? $routeId : null,
                'application_amount' => $applicationAmount,
            ];

            $db->table('service_applications')->insert($insert);
            $applicationId = (int) $db->insertID();

            // Handle uploaded documents
            $files = $this->request->getFiles('documents');
            if (is_array($files) && count($files) > 0) {
                $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $docModel = new \App\Models\ServiceApplicationDocumentModel();
                foreach ($files as $file) {
                    if (! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $newName = $file->getRandomName();
                    $moved = $file->move($targetDir, $newName);
                    if ($moved) {
                        $docModel->insert([
                            'application_id' => $applicationId,
                            'filename' => $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                            'uploaded_by' => (int) $user['user_id'],
                        ]);
                    }
                }
            }

            (new NotificationService())->notify(
                (int) $user['user_id'],
                'Your application for ' . (string) $service['service_name'] . ' has been submitted.',
                'registration_pending'
            );

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to submit application.');
            }

            $db->transCommit();

            return redirect()->to('/client/service?tab=services')->with('success', 'Service application submitted successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit package application (also the CLAIM path, when $packageId is
     * the Damayan entitlement package).
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
            ->select('package_id, package_name, base_price, is_damayan_entitlement')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->back()->with('error', 'Selected package is unavailable.');
        }

        $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;

        // Re-verify server-side, never trust the CLAIM button's visibility
        // client-side alone - reads from the exact same shared method the
        // frontend disabled state uses (entitlementClaimStatus()), so the
        // two can never disagree.
        [$canApply, $membership, $eligPlanId] = $this->resolveEligibility($access);
        if ($isEntitlement) {
            $claimStatus = $this->entitlementClaimStatus($canApply, $membership, $eligPlanId);
            if (! $claimStatus['eligible']) {
                return redirect()->back()->with('error', (string) $claimStatus['reason']);
            }
        }

        $burialAttireSelected = $this->request->getPost('burial_attire') ? true : false;
        $burialAttirePrice = $burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : null;

        $basePrice = (float) $package['base_price'];
        $damayanBenefitApplied = null;
        if ($isEntitlement) {
            // Fully covered - the whole casket price is the benefit itself.
            $damayanBenefitApplied = $basePrice;
            $applicationAmount = $burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : 0.0;
        } else {
            $damayanBenefitApplied = 0.0;
            if (! empty($membership) && (! empty($membership['can_access_services']))) {
                $damayanBenefitApplied = min($basePrice, MembershipService::TOTAL_CONTRIBUTION);
            }
            $applicationAmount = $basePrice - $damayanBenefitApplied + ($burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : 0.0);
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
                'burial_attire_selected' => $burialAttireSelected ? 1 : 0,
                'burial_attire_price' => $burialAttirePrice,
                'damayan_benefit_applied' => $damayanBenefitApplied,
                'application_amount' => $applicationAmount,
            ];

            $db->table('service_applications')->insert($insert);
            $applicationId = (int) $db->insertID();

            // Handle uploaded documents
            $files = $this->request->getFiles('documents');
            if (is_array($files) && count($files) > 0) {
                $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $docModel = new \App\Models\ServiceApplicationDocumentModel();
                foreach ($files as $file) {
                    if (! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $newName = $file->getRandomName();
                    $moved = $file->move($targetDir, $newName);
                    if ($moved) {
                        $docModel->insert([
                            'application_id' => $applicationId,
                            'filename' => $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                            'uploaded_by' => (int) $user['user_id'],
                        ]);
                    }
                }
            }

            (new NotificationService())->notify(
                (int) $user['user_id'],
                ($isEntitlement ? 'Your claim for ' : 'Your application for ') . (string) $package['package_name'] . ' has been submitted.',
                'registration_pending'
            );

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to submit application.');
            }

            $db->transCommit();

            $message = $isEntitlement ? 'Claim submitted successfully.' : 'Package application submitted successfully.';

            return redirect()->to('/client/service?tab=packages')->with('success', $message);
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Shared can_apply + membership summary resolution (same rule used
     * throughout this controller: active/good-standing plan with at least
     * 2 months paid). Also returns the plan_id, since entitlementClaimStatus()
     * below needs it and every caller already has $access in scope rather
     * than re-deriving it themselves.
     *
     * @return array{0: bool, 1: array|null, 2: int}
     */
    private function resolveEligibility(array $access): array
    {
        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);
        $planId = (int) ($activePlan['plan_id'] ?? 0);

        $membership = $planHolderId > 0 ? (new MembershipService())->getMembershipSummary($planHolderId) : null;

        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= 2;
        } else {
            $canApply = (($access['state'] ?? 'unregistered') === 'active') && $monthsPaid >= 2;
        }

        return [$canApply, $membership, $planId];
    }

    /**
     * The ONE shared CLAIM-eligibility check. services(), packageDetails()
     * and applyPackageForm() (frontend display / form gate) and
     * submitPackageApplication() (authoritative backend rejection) all call
     * this, so they can never disagree.
     *
     * Replaces the old ($canApply && hasFullyPaidContribution()) gate,
     * which wrongly required the ENTIRE ₱14,500 cycle paid off just to
     * claim the entitled package. Correct rule: 2+ verified months in the
     * current cycle - same threshold as availing anything else - and not
     * already claimed in this cycle. "Claimed but still paying it off" is
     * CycleService's 'claimed_owing' state; this method doesn't need to
     * name it, it just asks currentCycle() whether this cycle's
     * entitlement has been claimed yet.
     *
     * @return array{eligible: bool, reason: ?string}
     */
    private function entitlementClaimStatus(bool $canApply, ?array $membership, int $planId): array
    {
        if (! $canApply) {
            // canApply's own formula is (can_access_services && monthsPaid
            // >= 2), so if it's false but access is fine, months < 2 must
            // be why - gives the specific message the spec asks for
            // instead of the generic membership-guard fallback.
            $monthsPaid = (int) ($membership['months_paid'] ?? 0);
            $canAccessServices = is_array($membership) && ! empty($membership['can_access_services']);

            if ($canAccessServices && $monthsPaid < 2) {
                return [
                    'eligible' => false,
                    'reason' => "You need at least 2 paid months to claim your entitled package or avail services. You have paid {$monthsPaid} month(s).",
                ];
            }

            return ['eligible' => false, 'reason' => 'Become an active, eligible Plan Holder to claim this entitlement.'];
        }

        if ($planId <= 0) {
            return ['eligible' => false, 'reason' => 'Become an active, eligible Plan Holder to claim this entitlement.'];
        }

        $cycle = (new CycleService())->currentCycle($planId);
        if ($cycle['entitlement_claimed']) {
            $remaining = number_format((float) $cycle['remaining'], 2);

            return [
                'eligible' => false,
                'reason' => "You've already claimed your entitled package for this cycle. You have ₱{$remaining} remaining on your current ₱14,500 contribution. Once it's fully paid, a new cycle begins and you can claim again after 2 months.",
            ];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * @param array<int, int> $serviceListIds
     * @return array<int, array>
     */
    private function routesGroupedByService(array $serviceListIds): array
    {
        $serviceListIds = array_values(array_unique(array_map('intval', $serviceListIds)));
        if ($serviceListIds === []) {
            return [];
        }

        $rows = db_connect()->table('service_routes')
            ->whereIn('service_list_id', $serviceListIds)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('price', 'DESC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['service_list_id']][] = $row;
        }

        return $grouped;
    }
}
