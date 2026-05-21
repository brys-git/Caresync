<?php

namespace App\Controllers;

use App\Models\ServiceApplicationModel;
use App\Models\ServiceModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\ServiceBalanceService;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ClientService;
use CodeIgniter\HTTP\ResponseInterface;

class ServiceApplications extends BaseController
{
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }
    /**
     * @var array<string, bool>
     */
    private array $columnExistsCache = [];

    public function index(): ResponseInterface|string
    {
        $db = db_connect();
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');
        $canApply = ! ($roleId === 4 && (int) session('is_plan_holder') !== 1);

        $clientService = new ClientService();
        $hasServiceListId = $db->fieldExists('service_list_id', 'service_applications');
        $serviceHasAvailability = $db->tableExists('service_list') && $this->tableHasColumn('service_list', 'is_available');
        $packageHasAvailability = $db->tableExists('packages') && $this->tableHasColumn('packages', 'is_available');
        $packageHasStatus = $db->tableExists('packages') && $this->tableHasColumn('packages', 'status');

        $serviceCatalog = [];
        if ($db->tableExists('service_list')) {
            $serviceCatalog = $db->query(
                'SELECT service_list_id AS service_id, service_name, description, base_price, status FROM service_list WHERE status = \'active\''
                . ($serviceHasAvailability ? ' AND is_available = 1' : '')
                . ' ORDER BY service_name ASC'
            )->getResultArray();
        }

        $serviceJoinTable = null;
        if ($db->tableExists('service_list')) {
            $serviceJoinTable = 'service_list';
        }

        $applicationNameExpr = 'p.package_name';
        $applicationPriceExpr = 'p.base_price';
        if ($hasServiceListId && $serviceJoinTable === 'service_list') {
            $applicationNameExpr = 'COALESCE(p.package_name, sl.service_name)';
            $applicationPriceExpr = 'COALESCE(p.base_price, sl.base_price)';
        }

        $applicationTypeExpr = $hasServiceListId
            ? 'CASE WHEN sa.service_list_id IS NOT NULL THEN "service" ELSE "package" END'
            : '"package"';

        $applicationsBuilder = $db->table('service_applications sa')
            ->select('sa.*, ' . $applicationNameExpr . ' AS application_name, ' . $applicationPriceExpr . ' AS application_price, ' . $applicationTypeExpr . ' AS application_type, ph.unique_identifier, u.first_name, u.last_name, ph.branch_id', false)
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->orderBy('sa.created_at', 'DESC');

        if ($hasServiceListId && $serviceJoinTable === 'service_list') {
            $applicationsBuilder->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left');
        }

        if ($roleId === 4) {
            $applicationsBuilder->where('ph.user_id', (int) session('user_id'));
        } elseif (in_array($roleId, [2], true) && $branchId > 0) {
            $applicationsBuilder->where('ph.branch_id', $branchId);
        }

        $applications = $applicationsBuilder->get()->getResultArray();

        $planHolder = null;
        if ($roleId === 4) {
            $planHolder = $db->table('plan_holders')
                ->where('user_id', (int) session('user_id'))
                ->orderBy('plan_holder_id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $currentProfile = null;
        if ($planHolder) {
            $currentProfile = $clientService->getClientDetails((int) $planHolder['plan_holder_id']);
        }

        $packageFilterSql = '1 = 1';
        if ($packageHasAvailability) {
            $packageFilterSql = 'p.is_available = 1';
        } elseif ($packageHasStatus) {
            $packageFilterSql = "p.status = 'active'";
        }

        $packages = $db->query(
            'SELECT p.package_id, p.package_name, p.description, p.base_price, p.is_customizable, COUNT(pi.item_id) AS item_count FROM packages p LEFT JOIN package_items pi ON pi.package_id = p.package_id WHERE '
            . $packageFilterSql
            . ' GROUP BY p.package_id, p.package_name, p.description, p.base_price, p.is_customizable ORDER BY p.package_name ASC'
        )->getResultArray();
        $packageItems = $db->query(
            'SELECT pi.package_id, pi.item_name, pi.description FROM package_items pi INNER JOIN packages p ON p.package_id = pi.package_id WHERE '
            . $packageFilterSql
            . ' ORDER BY p.package_name ASC, pi.item_name ASC'
        )->getResultArray();

        return view('service_applications/index', [
            'applications' => $applications,
            'packages' => $packages,
            'package_items' => $packageItems,
            'service_catalog' => $serviceCatalog,
            'plan_holder' => $planHolder,
            'can_apply' => $canApply,
            'current_profile' => $currentProfile,
            'role_layout' => $this->resolveLayoutView(),
        ]);
    }

    public function updateProfile()
    {
        $planHolder = $this->requireCurrentPlanHolder();
        if (! $planHolder) {
            return redirect()->back()->with('error', 'Your account is not linked to a plan holder profile.');
        }

        $currentUserId = (int) session('user_id');
        $rules = [
            'package_id' => 'permit_empty|is_natural_no_zero',
            'service_id' => 'permit_empty|is_natural_no_zero',
            'apply_target' => 'required|in_list[service,package]',
        ];

        $userUpdated = (new UserModel())->update($currentUserId, [
            'username' => trim((string) $this->request->getPost('username')),
            'email' => trim((string) $this->request->getPost('email')),
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'contact_number' => trim((string) $this->request->getPost('contact_number')),
        ]);

        if (! $userUpdated) {
            return redirect()->back()->withInput()->with('error', 'Failed to update profile details.');
        }

        return redirect()->to(base_url('service-applications') . '#profile-tab')->with('success', 'Profile updated successfully.');
    }

    public function updateMembership()
    {
        $planHolder = $this->requireCurrentPlanHolder();
        if (! $planHolder) {
            return redirect()->back()->with('error', 'Your account is not linked to a plan holder profile.');
        }

        $rules = [
            'status' => 'required|in_list[active,inactive]',
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

        $planHolderUpdated = (new PlanHolderModel())->update((int) $planHolder['plan_holder_id'], [
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
            'status' => (string) $this->request->getPost('status'),
        ]);

        if (! $planHolderUpdated) {
            return redirect()->back()->withInput()->with('error', 'Failed to update membership details.');
        }

        return redirect()->to(base_url('service-applications') . '#membership-tab')->with('success', 'Membership details updated successfully.');
    }

    public function store()
    {
        if ((int) session('role_id') === 4 && (int) session('is_plan_holder') !== 1) {
            return redirect()->to('/plan-holder-registration')
                ->with('error', 'Please complete plan holder registration before applying for services or packages.');
        }

        $db = db_connect();
        $hasServiceListId = $db->fieldExists('service_list_id', 'service_applications');
        $planHolder = $db->table('plan_holders')
            ->where('user_id', (int) session('user_id'))
            ->orderBy('plan_holder_id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $planHolder) {
            return redirect()->to('/plan-holder-registration')
                ->with('error', 'Please register as a plan holder first before applying for services or packages.');
        }

        $packageId = (int) $this->request->getPost('package_id');
        $serviceId = (int) $this->request->getPost('service_id');
        $serviceName = trim((string) $this->request->getPost('service_name'));
        $applyTarget = (string) $this->request->getPost('apply_target');

        $rules = [
            'package_id' => 'permit_empty|is_natural_no_zero',
            'service_id' => 'permit_empty|is_natural_no_zero',
            'apply_target' => 'required|in_list[service,package]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if ($packageId <= 0 && $serviceId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please select a service or package to apply.');
        }

        if ($applyTarget === 'service' && $serviceId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid service to apply.');
        }

        if ($applyTarget === 'package' && $packageId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid package to apply.');
        }

        if ($applyTarget === 'service' && ! $hasServiceListId) {
            return redirect()->back()->withInput()->with('error', 'Service-only applications require the latest database migration.');
        }

        $insertData = [
            'plan_holder_id' => (int) $planHolder['plan_holder_id'],
            'package_id' => $packageId > 0 ? $packageId : null,
            'status' => 'pending',
        ];

        if ($hasServiceListId) {
            $insertData['service_list_id'] = $serviceId > 0 ? $serviceId : null;
        }

        $saved = (new ServiceApplicationModel())->insert($insertData);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Unable to submit service application.');
        }

        $notificationSuffix = $applyTarget === 'service' ? ' from a selected service.' : '.';
        $this->notificationService->notify((int) $planHolder['user_id'], 'Your service application has been submitted for review' . $notificationSuffix, 'registration_pending');
        $this->activityLogService->log(
            (int) session('user_id'),
            'created',
            'service_application',
            (int) db_connect()->insertID(),
            'Submitted service application',
            null,
            ['status' => 'pending']
        );

        return redirect()->to('/service-applications')->with('success', 'Service application submitted successfully.');
    }

    public function review()
    {
        $rules = [
            'application_id' => 'required|is_natural_no_zero',
            'status' => 'required|in_list[approved,rejected]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $applicationId = (int) $this->request->getPost('application_id');
        $status = (string) $this->request->getPost('status');
        $db = db_connect();
        $hasServiceListId = $db->fieldExists('service_list_id', 'service_applications');

        if ($hasServiceListId && $db->tableExists('service_list')) {
            $application = $db->table('service_applications sa')
                ->select('sa.*, ph.branch_id, ph.user_id, p.base_price AS package_price, p.package_name, sl.base_price AS service_price, sl.service_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
                ->where('sa.application_id', $applicationId)
                ->get()
                ->getRowArray();
        } else {
            $application = $db->table('service_applications sa')
                ->select('sa.*, ph.branch_id, ph.user_id, p.base_price AS package_price, p.package_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->where('sa.application_id', $applicationId)
                ->get()
                ->getRowArray();
        }

        if (! $application) {
            return redirect()->back()->withInput()->with('error', 'Service application was not found.');
        }

        (new ServiceApplicationModel())->update($applicationId, ['status' => $status]);

        if ($status === 'approved') {
            $serviceId = (int) ($application['service_list_id'] ?? 0);
            $packageId = (int) ($application['package_id'] ?? 0);
            $packageName = (string) ($application['package_name'] ?? $application['service_name'] ?? 'Selected service');
            $basePrice = $serviceId > 0
                ? (string) ($application['service_price'] ?? 0)
                : (string) ($application['package_price'] ?? 0);

            $serviceInsert = [
                'plan_holder_id' => (int) $application['plan_holder_id'],
                'branch_id' => (int) $application['branch_id'],
                'package_id' => $packageId > 0 ? $packageId : null,
                'total_cost' => $basePrice,
                'service_date' => date('Y-m-d'),
                'service_time' => null,
                'burial_location' => null,
                'assigned_staff' => null,
                'notes' => 'Created from approved service application for ' . $packageName . '.',
                'status' => 'pending',
            ];

            if ($db->fieldExists('service_list_id', 'services')) {
                $serviceInsert['service_list_id'] = $serviceId > 0 ? $serviceId : null;
            }

            $serviceId = (int) (new ServiceModel())->insert($serviceInsert);

            $balanceService = new ServiceBalanceService();
            $balanceId = $balanceService->createBalanceRecord([
                'application_id' => (int) $application['application_id'],
                'plan_holder_id' => (int) $application['plan_holder_id'],
                'branch_id' => (int) $application['branch_id'],
                'service_list_id' => $serviceId > 0 ? (int) ($application['service_list_id'] ?? 0) : 0,
                'package_id' => $packageId,
                'service_type' => $serviceId > 0 && (int) ($application['service_list_id'] ?? 0) > 0 ? 'service' : 'package',
                'service_name' => (string) ($application['service_name'] ?? $application['package_name'] ?? 'Selected service'),
                'package_name' => (string) ($application['package_name'] ?? null),
                'package_cost' => (float) ($serviceId > 0 ? $basePrice : $basePrice),
            ], [
                'service_id' => $serviceId,
            ]);

            $this->notificationService->notify((int) $application['user_id'], 'Your service application for ' . $packageName . ' was approved.', 'service_approved');

            if ($balanceId) {
                $this->notificationService->notify((int) $application['user_id'], 'A separate funeral balance was created for continuation payments after assistance was applied.', 'service_balance_created');
            }
        } else {
            $this->notificationService->notify((int) $application['user_id'], 'Your service application for ' . ((string) ($application['package_name'] ?? $application['service_name'] ?? 'your selected item')) . ' was rejected.', 'service_rejected');
        }

        $this->activityLogService->log(
            (int) session('user_id'),
            $status === 'approved' ? 'approved' : 'rejected',
            'service_application',
            $applicationId,
            'Reviewed service application',
            ['status' => 'pending'],
            ['status' => $status]
        );

        return redirect()->to('/service-applications')->with('success', 'Service application reviewed successfully.');
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

    private function requireCurrentPlanHolder(): ?array
    {
        $db = db_connect();

        return $db->table('plan_holders')
            ->where('user_id', (int) session('user_id'))
            ->orderBy('plan_holder_id', 'DESC')
            ->get()
            ->getRowArray() ?: null;
    }

    private function nullablePost(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    private function nullableIntPost(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : (int) $value;
    }

    private function nullableDecimalPost(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . ':' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $exists = db_connect()->fieldExists($column, $table);
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }
}
