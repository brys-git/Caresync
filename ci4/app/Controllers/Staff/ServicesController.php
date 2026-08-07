<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Models\PackageModel;
use App\Models\PackageVersionModel;
use App\Models\ServiceApplicationModel;
use App\Models\ServiceModel;
use App\Models\ServiceListModel;

class ServicesController extends BaseController
{
    protected ServiceModel $serviceModel;
    protected PackageModel $packageModel;
    protected PackageVersionModel $packageVersionModel;
    protected ServiceApplicationModel $serviceApplicationModel;
    protected ServiceListModel $serviceListModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->packageModel = new PackageModel();
        $this->packageVersionModel = new PackageVersionModel();
        $this->serviceApplicationModel = new ServiceApplicationModel();
        $this->serviceListModel = new ServiceListModel();
    }

    public function index(): string
    {
        $this->ensureStaffAccess();

        $branchId = (int) session()->get('branch_id');
        $activeTab = (string) $this->request->getGet('tab');
        if (! in_array($activeTab, ['services', 'packages'], true)) {
            $activeTab = 'services';
        }

        $branchIssue = null;
        if ($branchId <= 0) {
            $services = [];
            $packages = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $services = db_connect()->table('services s')
                ->select('s.service_id, s.plan_holder_id, s.service_list_id, s.package_id, s.service_date, s.status, s.assigned_staff, sl.service_name, ph.unique_identifier, u.first_name, u.last_name, p.package_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = s.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
                ->join('users st', 'st.user_id = s.assigned_staff', 'left')
                ->where('s.branch_id', $branchId)
                ->orderBy('s.service_date', 'DESC')
                ->orderBy('s.service_id', 'DESC')
                ->get()
                ->getResultArray();

            $packages = $this->packageModel
                ->where('is_available', 1)
                ->orderBy('package_name', 'ASC')
                ->findAll();
        }

        $selectedServiceId = (int) $this->request->getGet('service_id');
        $selectedPackageId = (int) $this->request->getGet('package_id');

        $selectedService = null;
        if ($selectedServiceId > 0) {
            foreach ($services as $service) {
                if ((int) ($service['service_id'] ?? 0) === $selectedServiceId) {
                    $selectedService = $service;
                    break;
                }
            }
        }

        $selectedPackage = null;
        $selectedPackageServices = [];
        $selectedPackageVersions = [];
        if ($selectedPackageId > 0) {
            foreach ($packages as $package) {
                if ((int) ($package['package_id'] ?? 0) === $selectedPackageId) {
                    $selectedPackage = $package;
                    break;
                }
            }

            if ($selectedPackage !== null) {
                $selectedPackageServices = [];
                $db = db_connect();
                if ($db->tableExists('package_services')) {
                    $packageServiceFields = $db->getFieldNames('package_services');
                    if (in_array('service_list_id', $packageServiceFields, true)) {
                        $selectedPackageServices = $db->table('package_services ps')
                            ->select('sl.service_list_id, sl.service_name, sl.description, sl.base_price, sl.status')
                            ->join('service_list sl', 'sl.service_list_id = ps.service_list_id', 'inner')
                            ->where('sl.is_available', 1)
                            ->where('ps.package_id', $selectedPackageId)
                            ->orderBy('sl.service_name', 'ASC')
                            ->get()
                            ->getResultArray();
                    } elseif (in_array('service_id', $packageServiceFields, true)) {
                        $selectedPackageServices = $db->table('package_services ps')
                            ->select('sl.service_list_id, sl.service_name, sl.description, sl.base_price, sl.status')
                            ->join('service_list sl', 'sl.service_list_id = ps.service_id', 'inner')
                            ->where('sl.is_available', 1)
                            ->where('ps.package_id', $selectedPackageId)
                            ->orderBy('sl.service_name', 'ASC')
                            ->get()
                            ->getResultArray();
                    }
                }

                $selectedPackageVersions = $this->packageVersionModel
                    ->where('package_id', $selectedPackageId)
                    ->orderBy('effective_date', 'DESC')
                    ->orderBy('version_id', 'DESC')
                    ->findAll();
            }
        }

        $serviceListForCards = $this->serviceListModel->where('is_available', 1)->orderBy('service_name', 'ASC')->findAll();

        return view('staff/services/index', [
            'active_tab' => $activeTab,
            'services' => $services,
            'packages' => $packages,
            'selected_service' => $selectedService,
            'selected_package' => $selectedPackage,
            'selected_package_services' => $selectedPackageServices,
            'selected_package_versions' => $selectedPackageVersions,
            'service_list' => $serviceListForCards,
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
            'page_title' => null,
            'total_services' => count($services),
            'total_packages' => count($packages),
        ]);
    }

    public function serviceRequests(): string
    {
        $this->ensureStaffAccess();

        $branchId = (int) session()->get('branch_id');

        $branchIssue = null;
        if ($branchId <= 0) {
            $requests = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $requests = db_connect()->table('service_applications sa')
                ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.status, sa.created_at, ph.unique_identifier, u.first_name, u.last_name, p.package_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->where('ph.branch_id', $branchId)
                ->orderBy('sa.created_at', 'DESC')
                ->orderBy('sa.application_id', 'DESC')
                ->get()
                ->getResultArray();
        }

        return view('staff/services/requests', [
            'requests' => $requests,
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function ongoingServices(): string
    {
        $this->ensureStaffAccess();

        $branchId = (int) session()->get('branch_id');

        $branchIssue = null;
        if ($branchId <= 0) {
            $services = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $services = db_connect()->table('services s')
                ->select('s.service_id, s.plan_holder_id, s.service_list_id, s.service_date, s.status, s.assigned_staff, sl.service_name, ph.unique_identifier, u.first_name, u.last_name, p.package_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
                ->join('users u', 'u.user_id = ph.user_id', 'inner')
                ->join('packages p', 'p.package_id = s.package_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
                ->join('users st', 'st.user_id = s.assigned_staff', 'left')
                ->where('s.branch_id', $branchId)
                ->whereIn('s.status', ['pending', 'ongoing', 'completed'])
                ->orderBy('s.service_date', 'DESC')
                ->orderBy('s.service_id', 'DESC')
                ->get()
                ->getResultArray();
        }

        return view('staff/services/ongoing', [
            'services' => $services,
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function updateStatus(int $serviceId)
    {
        $this->ensureStaffAccess();

        $branchId = (int) session()->get('branch_id');
        $currentUserId = (int) session()->get('user_id');

        $service = db_connect()->table('services s')
            ->select('s.*, ph.user_id AS plan_holder_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->where('s.service_id', $serviceId)
            ->where('s.branch_id', $branchId)
            ->get()
            ->getRowArray();

        if (! $service || (int) ($service['assigned_staff'] ?? 0) !== $currentUserId) {
            return redirect()->to('/unauthorized')->with('error', 'You are not authorized to update this service.');
        }

        $rules = [
            'status' => 'required|in_list[pending,ongoing,completed,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $updated = db_connect()->table('services')->where('service_id', $serviceId)->update([
            'status' => (string) $this->request->getPost('status'),
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Failed to update service status.');
        }

        return redirect()->to('/staff/services/ongoing')->with('success', 'Service status updated successfully.');
    }

    private function ensureStaffAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 3 && $roleName !== 'staff') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
