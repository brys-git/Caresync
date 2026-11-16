<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\ClaimSubmissionService;
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
    private const BURIAL_ATTIRE_PRICE = ClaimSubmissionService::BURIAL_ATTIRE_PRICE;

    private const MIN_MONTHS_PAID = 2;

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
            $activeTab = 'packages';
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
            $svc['image_url'] = $this->existingImageUrl($svc['image_path'] ?? null);
        }
        unset($svc);

        $packages = db_connect()->table('packages')
            ->select('package_id, package_name, description, base_price, is_damayan_entitlement, image_path')
            ->where('is_available', 1)
            ->orderBy('base_price', 'ASC')
            ->get()
            ->getResultArray();

        $inclusionNames = [];
        if ($packages !== []) {
            $itemRows = db_connect()->table('package_items')
                ->select('package_id, item_name')
                ->whereIn('package_id', array_column($packages, 'package_id'))
                ->orderBy('item_id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($itemRows as $row) {
                $inclusionNames[(int) $row['package_id']][] = (string) $row['item_name'];
            }
        }

        [$canApply, $membership, $planId] = $this->resolveEligibility($access);
        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);

        $entitlement = null;
        $gridPackages = [];
        foreach ($packages as $pkg) {
            $pkg['image_url'] = $this->existingImageUrl($pkg['image_path'] ?? null);
            $pkg['inclusion_names'] = $inclusionNames[(int) $pkg['package_id']] ?? [];
            if ((int) ($pkg['is_damayan_entitlement'] ?? 0) === 1) {
                $entitlement ??= $pkg;
                continue;
            }
            $gridPackages[] = $pkg;
        }

        $openClaim = $entitlement && $planHolderId > 0 ? $this->openClaim($planHolderId, $planId) : null;
        $claimStatus = $entitlement ? $this->entitlementClaimStatus($canApply, $membership, $planId) : ['eligible' => false, 'reason' => null];

        return view('client/services', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Request a service',
            'show_page_head' => false,
            'access' => $access,
            'active_tab' => $activeTab,
            'services' => $services,
            'packages' => $gridPackages,
            'entitlement' => $entitlement,
            'entitlement_claim' => $openClaim,
            'claim_eligible' => (bool) $claimStatus['eligible'],
            'claim_reason' => $claimStatus['reason'],
            'eligibility' => $this->catalogEligibility($canApply, $membership),
            'can_apply' => $canApply,
            'membership' => $membership ?? [],
        ]);
    }

    /**
     * Plain-language eligibility for the Request a service header pill, and
     * the reason every Avail/Apply/Claim button is disabled when it's false.
     *
     * @return array{eligible: bool, label: string, detail: string, reason: ?string, months_paid: int, months_required: int}
     */
    private function catalogEligibility(bool $canApply, ?array $membership): array
    {
        $monthsPaid = (int) ($membership['months_paid'] ?? 0);
        $state = strtolower((string) ($membership['membership_state'] ?? 'inactive'));
        $required = self::MIN_MONTHS_PAID;

        if ($canApply) {
            return [
                'eligible' => true,
                'label' => 'You can apply',
                'detail' => ucfirst($state) . ' · ' . $monthsPaid . ' ' . ($monthsPaid === 1 ? 'month' : 'months') . ' paid (' . $required . ' required)',
                'reason' => null,
                'months_paid' => $monthsPaid,
                'months_required' => $required,
            ];
        }

        if ($membership === null || $membership === []) {
            $reason = 'You need an active plan before you can request services.';
        } elseif (! empty($membership['service_block_reason'])) {
            // The plan's own status says why (on hold / forfeited) - shown instead of hiding the button.
            $reason = (string) $membership['service_block_reason'];
        } elseif ($state !== 'active') {
            $reason = 'Membership ' . $state . '. Contact your branch.';
        } elseif ($monthsPaid < $required) {
            $more = $required - $monthsPaid;
            $reason = 'Not yet eligible · ' . $more . ' more ' . ($more === 1 ? 'month' : 'months') . ' needed';
        } else {
            $reason = 'Your membership needs attention. Contact your branch.';
        }

        return [
            'eligible' => false,
            'label' => $state !== 'active' && $membership !== null && $membership !== [] ? 'Membership ' . $state : 'Not yet eligible',
            'detail' => $reason,
            'reason' => $reason,
            'months_paid' => $monthsPaid,
            'months_required' => $required,
        ];
    }

    /**
     * URL for a catalog image only when the file is actually on disk, so a
     * missing photo renders a placeholder instead of a broken <img> and its
     * alt text.
     */
    private function existingImageUrl(?string $path): string
    {
        return cs_image_url($path);
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
            'page_sub' => 'Service details and pricing.',
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
            'page_sub' => 'Package details, inclusions, and pricing.',
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
            'page_actions' => '<a class="btn btn-ghost" href="' . esc(site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)), 'attr') . '">Back</a>',
            'access' => $access,
            'service' => $service,
            'routes' => $routes,
            'selected_route_id' => (int) $this->request->getGet('route'),
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

        $eligibility = ['eligible' => $canApply, 'reason' => null];
        if ($isEntitlement) {
            $eligibility = $this->entitlementClaimStatus($canApply, $membership, $planId);
        } elseif (! $canApply) {
            $eligibility = ['eligible' => false, 'reason' => 'You need an active plan with at least 2 paid months to avail packages.'];
        }

        $planHolder = $access['plan_holder'] ?? [];
        $planHolderId = (int) ($planHolder['plan_holder_id'] ?? 0);
        $activePlan = $planHolderId > 0 ? $this->activePlan($planHolderId) : null;
        $user = $access['user'] ?? [];
        $firstBeneficiary = $planHolderId > 0
            ? db_connect()->table('beneficiaries')
                ->select('first_name, middle_name, last_name, name_extension')
                ->where('plan_holder_id', $planHolderId)
                ->orderBy('is_primary', 'DESC')
                ->orderBy('beneficiary_id', 'ASC')
                ->get(1)
                ->getRowArray()
            : null;
        $branch = ! empty($planHolder['branch_id'])
            ? db_connect()->table('branches')
                ->select('branch_name, contact_number')
                ->where('branch_id', (int) $planHolder['branch_id'])
                ->get()
                ->getRowArray()
            : null;

        $benefitCredit = (! $isEntitlement && $canApply)
            ? min((float) $package['base_price'], MembershipService::TOTAL_CONTRIBUTION)
            : 0.0;

        return view('client/package_apply', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => $isEntitlement ? 'Claim Regular Casket' : 'Avail Package',
            'show_page_head' => false,
            'access' => $access,
            'package' => $package,
            'is_entitlement' => $isEntitlement,
            'benefit_credit' => $benefitCredit,
            'can_apply' => $canApply,
            'attire_price' => self::BURIAL_ATTIRE_PRICE,
            'eligibility' => $eligibility,
            'existing_claim' => $isEntitlement && $planHolderId > 0 ? $this->openClaim($planHolderId, $planId) : null,
            'plan_name' => (new MembershipService())->resolvePackageName((int) ($activePlan['package_id'] ?? 0)),
            'member_name' => trim(implode(' ', array_filter([$user['first_name'] ?? '', $user['middle_name'] ?? '', $user['last_name'] ?? '']))),
            'member_address' => cs_format_address($planHolder),
            'beneficiary_prefill' => $firstBeneficiary
                ? trim(implode(' ', array_filter([$firstBeneficiary['first_name'], $firstBeneficiary['middle_name'], $firstBeneficiary['last_name'], $firstBeneficiary['name_extension']])))
                : '',
            'branch_name' => (string) ($branch['branch_name'] ?? ''),
            'branch_phone' => (string) ($branch['contact_number'] ?? ''),
            'form_errors' => session()->getFlashdata('form_errors') ?? [],
        ]);
    }

    /**
     * The member's claim that's still open: one awaiting review, or one already
     * approved for the current cycle. Shown instead of the form so a family
     * can't submit the same casket twice.
     */
    private function openClaim(int $planHolderId, int $planId): ?array
    {
        return (new ClaimSubmissionService())->openClaim($planHolderId, $planId);
    }
    /**
     * Confirmation screen after a claim or package request is submitted.
     */
    public function packageSubmitted(int $applicationId): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        $application = $planHolderId > 0
            ? db_connect()->table('service_applications sa')
                ->select('sa.application_id, sa.status, sa.created_at, sa.application_amount, sa.awaiting_death_certificate, sa.filed_on_behalf, sa.claimant_name, p.package_name, p.is_damayan_entitlement')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->where('sa.application_id', $applicationId)
                ->where('sa.plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray()
            : null;

        if (! $application) {
            return redirect()->to('/client/service?tab=packages')->with('error', 'Claim not found.');
        }

        $isEntitlement = (int) ($application['is_damayan_entitlement'] ?? 0) === 1;

        return view('client/package_submitted', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => $isEntitlement ? 'Claim Regular Casket' : 'Avail Package',
            'show_page_head' => false,
            'access' => $access,
            'application' => $application,
            'is_entitlement' => $isEntitlement,
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
        $serviceAccess = $membershipService->serviceAccess((int) $planHolder['plan_holder_id']);
        if (! $serviceAccess['allowed']) {
            return redirect()->back()->with('error', $serviceAccess['reason'] ?: 'Your plan does not allow service requests at this time.');
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
        $serviceAccess = $membershipService->serviceAccess((int) $planHolder['plan_holder_id']);
        if (! $serviceAccess['allowed']) {
            return redirect()->back()->with('error', $serviceAccess['reason'] ?: 'Your plan does not allow service requests at this time.');
        }

        $package = db_connect()->table('packages')
            ->select('package_id, package_name, base_price, is_damayan_entitlement')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();

        if (! $package) {
            return redirect()->back()->with('error', 'Selected package is unavailable.');
        }

        // Everything below - the eligibility re-check, validation, uploads and the
        // database writes - lives in ClaimSubmissionService so the Branch Admin
        // filing on a plan holder's behalf follows exactly the same rules.
        $result = (new ClaimSubmissionService())->submit(
            (int) $planHolder['plan_holder_id'],
            $packageId,
            $this->request->getPost(),
            $this->request->getFiles() ?? [],
            (int) $user['user_id']
        );

        if ($result['ok']) {
            return redirect()->to('/client/apply-package/submitted/' . $result['application_id']);
        }

        if (! empty($result['errors'])) {
            return redirect()->back()->withInput()->with('form_errors', $result['errors']);
        }

        if (! empty($result['redirect_open_claim'])) {
            return redirect()->to('/client/apply-package/' . $packageId)->with('error', 'You already have a claim in progress for this entitlement.');
        }

        return redirect()->back()->withInput()->with('error', (string) ($result['error'] ?? 'Unable to submit right now.'));
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
        return (new ClaimSubmissionService())->eligibility((int) ($access['plan_holder']['plan_holder_id'] ?? 0));
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
        return (new ClaimSubmissionService())->entitlementClaimStatus($canApply, $membership, $planId);
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
