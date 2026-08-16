<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Models\AddOnModel;
use App\Models\NotificationModel;
use App\Models\PackageInclusionModel;
use App\Models\PackageItemModel;
use App\Models\PackageModel;
use App\Models\PackageVariantModel;
use App\Models\PackageVersionModel;
use App\Models\ServiceListModel;
use App\Models\ServiceRateModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PackageController extends BaseController
{
    private PackageModel $packageModel;
    private PackageItemModel $packageItemModel;
    private PackageVersionModel $packageVersionModel;
    private PackageVariantModel $packageVariantModel;
    private PackageInclusionModel $packageInclusionModel;
    private AddOnModel $addOnModel;
    private ServiceListModel $serviceListModel;
    private ServiceRateModel $serviceRateModel;

    public function __construct()
    {
        $this->packageModel = new PackageModel();
        $this->packageItemModel = new PackageItemModel();
        $this->packageVersionModel = new PackageVersionModel();
        $this->packageVariantModel = new PackageVariantModel();
        $this->packageInclusionModel = new PackageInclusionModel();
        $this->addOnModel = new AddOnModel();
        $this->serviceListModel = new ServiceListModel();
        $this->serviceRateModel = new ServiceRateModel();
    }

    public function index(): string
    {
        $this->ensureBranchAdminAccess();

        $db = db_connect();
        $activeTab = (string) $this->request->getGet('tab');
        if (! in_array($activeTab, ['packages', 'services', 'addons', 'rates', 'requests', 'ongoing', 'schedule'], true)) {
            $activeTab = 'packages';
        }

        $packages = $this->packageModel->orderBy('package_name', 'ASC')->findAll();

        // Load variants and inclusions for each package
        foreach ($packages as &$pkg) {
            $pkg['variants'] = $this->packageVariantModel->getActiveVariants((int) $pkg['package_id']);
            $pkg['inclusions'] = $this->packageInclusionModel->getActiveInclusions((int) $pkg['package_id']);
        }

        $services = $db->table('service_list')
            ->select('service_list_id, service_name, description, base_price, status')
            ->where('is_available', 1)
            ->orderBy('service_name', 'ASC')
            ->get()
            ->getResultArray();

        // Load service rates for Balik Probinsya
        foreach ($services as &$svc) {
            $svc['rates'] = $this->serviceRateModel->getActiveRates((int) $svc['service_list_id']);
        }

        $pendingPackages = $db->table('pending_packages pp')
            ->select('pp.pending_package_id, pp.package_name, pp.description, pp.base_price, pp.status, pp.created_at, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = pp.created_by', 'left')
            ->where('pp.created_by', (int) session('user_id'))
            ->orderBy('pp.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $pendingCount = 0;
        $approvedCount = 0;
        foreach ($pendingPackages as $pp) {
            if (($pp['status'] ?? '') === 'pending') $pendingCount++;
            if (($pp['status'] ?? '') === 'approved') $approvedCount++;
        }

        // Get ALL add-ons (not just active) for management tab
        $allAddOns = $this->addOnModel->orderBy('addon_name', 'ASC')->findAll();

        return view('branch_admin/service_package/index', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
            'active_tab' => $activeTab,
            'packages' => $packages,
            'services' => $services,
            'add_ons' => $allAddOns,
            'pending_packages' => $pendingPackages,
            'total_packages' => count($packages),
            'total_services' => count($services),
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
        ]);
    }

    public function create(): string
    {
        $this->ensureBranchAdminAccess();

        $addOns = $this->addOnModel->getActiveAddOns('optional');

        return view('branch_admin/packages/create', [
            'service_list' => $this->serviceListModel->where('is_available', 1)->orderBy('service_name', 'ASC')->findAll(),
            'add_ons' => $addOns,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function store()
    {
        $this->ensureBranchAdminAccess();

        $rules = [
            'package_name' => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'base_price' => 'required|decimal|greater_than[0]',
            'is_customizable' => 'required|in_list[0,1]',
            'initial_effective_date' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $serviceListIds = array_values(array_filter(array_map('intval', (array) $this->request->getPost('service_list_id')), static fn (int $serviceListId): bool => $serviceListId > 0));

            if (empty($serviceListIds)) {
                throw new \RuntimeException('At least one package service is required.');
            }

            $pendingSaved = $db->table('pending_packages')->insert([
                'package_name' => trim((string) $this->request->getPost('package_name')),
                'description' => trim((string) $this->request->getPost('description')),
                'base_price' => (string) $this->request->getPost('base_price'),
                'is_customizable' => (int) $this->request->getPost('is_customizable'),
                'initial_effective_date' => (string) $this->request->getPost('initial_effective_date'),
                'service_list_ids' => json_encode($serviceListIds),
                'created_by' => (int) session('user_id'),
            ]);

            if (! $pendingSaved) {
                throw new \RuntimeException('Failed to submit package request for approval.');
            }

            $db->transCommit();

            $this->notifySystemAdmins('New package pending approval from branch admin.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Package request submitted for approval.');
    }

    public function view(int $id): string
    {
        $this->ensureBranchAdminAccess();

        $package = $this->packageModel->find($id);

        if (! $package) {
            throw PageNotFoundException::forPageNotFound();
        }

        $package['variants'] = $this->packageVariantModel->getActiveVariants($id);
        $package['inclusions'] = $this->packageInclusionModel->getActiveInclusions($id);
        $package['items'] = $this->packageItemModel->where('package_id', $id)->orderBy('item_id', 'ASC')->findAll();
        $package['versions'] = $this->packageVersionModel->where('package_id', $id)->orderBy('effective_date', 'DESC')->orderBy('version_id', 'DESC')->findAll();

        return view('branch_admin/service_package/packages_view', [
            'package' => $package,
            'service_list' => $this->serviceListModel->where('is_available', 1)->orderBy('service_name', 'ASC')->findAll(),
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function edit(int $id): string
    {
        $this->ensureBranchAdminAccess();

        $package = $this->packageModel->find($id);

        if (! $package) {
            throw PageNotFoundException::forPageNotFound();
        }

        $package['variants'] = $this->packageVariantModel->getActiveVariants($id);
        $package['inclusions'] = $this->packageInclusionModel->getActiveInclusions($id);

        return view('branch_admin/service_package/packages_edit', [
            'package' => $package,
            'service_list' => $this->serviceListModel->where('is_available', 1)->orderBy('service_name', 'ASC')->findAll(),
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function update(int $id)
    {
        $this->ensureBranchAdminAccess();

        $package = $this->packageModel->find($id);

        if (! $package) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'package_name' => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'is_customizable' => 'required|in_list[0,1]',
            'new_price' => 'permit_empty|decimal|greater_than[0]',
            'effective_date' => 'permit_empty|valid_date',
            'version_status' => 'permit_empty|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->packageModel->update($id, [
                'package_name' => trim((string) $this->request->getPost('package_name')),
                'description' => trim((string) $this->request->getPost('description')),
                'is_customizable' => (int) $this->request->getPost('is_customizable'),
            ]);

            $newPrice = trim((string) $this->request->getPost('new_price'));
            if ($newPrice !== '') {
                $this->packageVersionModel->insert([
                    'package_id' => $id,
                    'price' => $newPrice,
                    'effective_date' => (string) $this->request->getPost('effective_date'),
                    'status' => (string) ($this->request->getPost('version_status') ?: 'active'),
                ]);
            }

            $newServiceIds = array_map('intval', (array) $this->request->getPost('new_service_list_id'));

            foreach ($newServiceIds as $serviceListId) {
                if ($serviceListId <= 0) {
                    continue;
                }

                $service = $this->serviceListModel->find($serviceListId);
                if (! $service) {
                    continue;
                }

                $this->packageItemModel->insert([
                    'package_id' => $id,
                    'item_name' => (string) ($service['service_name'] ?? ''),
                    'description' => trim((string) ($service['description'] ?? '')),
                ]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to update package.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package updated successfully.');
    }

    public function addItem(int $id)
    {
        $this->ensureBranchAdminAccess();

        $package = $this->packageModel->find($id);

        if (! $package) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'service_list_id' => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceListId = (int) $this->request->getPost('service_list_id');
        $service = $this->serviceListModel->find($serviceListId);
        if (! $service) {
            return redirect()->back()->withInput()->with('error', 'Selected service is invalid.');
        }

        $saved = $this->packageItemModel->insert([
            'package_id' => $id,
            'item_name' => (string) ($service['service_name'] ?? ''),
            'description' => trim((string) ($service['description'] ?? '')),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add package service.');
        }

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package service added successfully.');
    }


    private function ensureBranchAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 2 && $roleName !== 'branch admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }

    private function notifySystemAdmins(string $message): void
    {
        $admins = (new UserModel())
            ->select('user_id')
            ->where('role_id', 1)
            ->findAll();

        $notificationModel = new NotificationModel();

        foreach ($admins as $admin) {
            $notificationModel->insert([
                'user_id' => (int) ($admin['user_id'] ?? 0),
                'message' => $message,
                'is_read' => 0,
            ]);
        }
    }

    // ===== Variant Management =====

    public function addVariant(int $id)
    {
        $this->ensureBranchAdminAccess();

        $rules = [
            'variant_name' => 'required|max_length[100]',
            'base_price' => 'required|decimal|greater_than[0]',
            'description' => 'permit_empty|max_length[500]',
            'is_default' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $saved = $this->packageVariantModel->insert([
            'package_id' => $id,
            'variant_name' => trim((string) $this->request->getPost('variant_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'base_price' => (string) $this->request->getPost('base_price'),
            'is_default' => (int) $this->request->getPost('is_default') ?? 0,
            'status' => 'active',
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add package variant.');
        }

        // If this is set as default, unset other defaults
        if ((int) $this->request->getPost('is_default') === 1) {
            $this->packageVariantModel
                ->where('package_id', $id)
                ->where('variant_id !=', $saved)
                ->set(['is_default' => 0])
                ->update();
        }

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package variant added successfully.');
    }

    public function updateVariant(int $id, int $variantId)
    {
        $this->ensureBranchAdminAccess();

        $variant = $this->packageVariantModel->find($variantId);
        if (! $variant || (int) $variant['package_id'] !== $id) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'variant_name' => 'required|max_length[100]',
            'base_price' => 'required|decimal|greater_than[0]',
            'description' => 'permit_empty|max_length[500]',
            'is_default' => 'permit_empty|in_list[0,1]',
            'status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->packageVariantModel->update($variantId, [
            'variant_name' => trim((string) $this->request->getPost('variant_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'base_price' => (string) $this->request->getPost('base_price'),
            'is_default' => (int) $this->request->getPost('is_default') ?? 0,
            'status' => (string) $this->request->getPost('status'),
        ]);

        // If this is set as default, unset other defaults
        if ((int) $this->request->getPost('is_default') === 1) {
            $this->packageVariantModel
                ->where('package_id', $id)
                ->where('variant_id !=', $variantId)
                ->set(['is_default' => 0])
                ->update();
        }

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package variant updated successfully.');
    }

    public function deleteVariant(int $id, int $variantId)
    {
        $this->ensureBranchAdminAccess();

        $variant = $this->packageVariantModel->find($variantId);
        if (! $variant || (int) $variant['package_id'] !== $id) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->packageVariantModel->delete($variantId);

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package variant deleted successfully.');
    }

    // ===== Inclusion Management =====

    public function addInclusion(int $id)
    {
        $this->ensureBranchAdminAccess();

        $rules = [
            'item_name' => 'required|max_length[150]',
            'description' => 'permit_empty|max_length[500]',
            'category' => 'required|max_length[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $saved = $this->packageInclusionModel->insert([
            'package_id' => $id,
            'item_name' => trim((string) $this->request->getPost('item_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'category' => trim((string) $this->request->getPost('category')),
            'status' => 'active',
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add package inclusion.');
        }

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package inclusion added successfully.');
    }

    public function updateInclusion(int $id, int $inclusionId)
    {
        $this->ensureBranchAdminAccess();

        $inclusion = $this->packageInclusionModel->find($inclusionId);
        if (! $inclusion || (int) $inclusion['package_id'] !== $id) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'item_name' => 'required|max_length[150]',
            'description' => 'permit_empty|max_length[500]',
            'category' => 'required|max_length[50]',
            'status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->packageInclusionModel->update($inclusionId, [
            'item_name' => trim((string) $this->request->getPost('item_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'category' => trim((string) $this->request->getPost('category')),
            'status' => (string) $this->request->getPost('status'),
        ]);

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package inclusion updated successfully.');
    }

    public function deleteInclusion(int $id, int $inclusionId)
    {
        $this->ensureBranchAdminAccess();

        $inclusion = $this->packageInclusionModel->find($inclusionId);
        if (! $inclusion || (int) $inclusion['package_id'] !== $id) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->packageInclusionModel->delete($inclusionId);

        return redirect()->to('/branch-admin/packages/view/' . $id)->with('success', 'Package inclusion deleted successfully.');
    }

    // ===== Add-on Management =====

    public function addAddOn(): string
    {
        $this->ensureBranchAdminAccess();

        $rules = [
            'addon_name' => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'base_price' => 'required|decimal|greater_than[0]',
            'min_price' => 'permit_empty|decimal|greater_than[0]',
            'max_price' => 'permit_empty|decimal|greater_than[0]',
            'category' => 'required|max_length[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $saved = $this->addOnModel->insert([
            'addon_name' => trim((string) $this->request->getPost('addon_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'base_price' => (string) $this->request->getPost('base_price'),
            'min_price' => $this->nullablePost('min_price'),
            'max_price' => $this->nullablePost('max_price'),
            'category' => trim((string) $this->request->getPost('category')),
            'is_active' => 1,
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add add-on.');
        }

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Add-on added successfully.');
    }

    public function updateAddOn(int $addonId)
    {
        $this->ensureBranchAdminAccess();

        $addon = $this->addOnModel->find($addonId);
        if (! $addon) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'addon_name' => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'base_price' => 'required|decimal|greater_than[0]',
            'min_price' => 'permit_empty|decimal|greater_than[0]',
            'max_price' => 'permit_empty|decimal|greater_than[0]',
            'category' => 'required|max_length[50]',
            'is_active' => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->addOnModel->update($addonId, [
            'addon_name' => trim((string) $this->request->getPost('addon_name')),
            'description' => trim((string) $this->request->getPost('description')),
            'base_price' => (string) $this->request->getPost('base_price'),
            'min_price' => $this->nullablePost('min_price'),
            'max_price' => $this->nullablePost('max_price'),
            'category' => trim((string) $this->request->getPost('category')),
            'is_active' => (int) $this->request->getPost('is_active'),
        ]);

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Add-on updated successfully.');
    }

    public function deleteAddOn(int $addonId)
    {
        $this->ensureBranchAdminAccess();

        $addon = $this->addOnModel->find($addonId);
        if (! $addon) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->addOnModel->delete($addonId);

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Add-on deleted successfully.');
    }

    // ===== Service Rate Management =====

    public function addServiceRate(int $serviceListId)
    {
        $this->ensureBranchAdminAccess();

        $serviceRateModel = new ServiceRateModel();

        $rules = [
            'origin' => 'required|max_length[100]',
            'destination' => 'required|max_length[100]',
            'rate' => 'required|decimal|greater_than[0]',
            'description' => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $saved = $serviceRateModel->insert([
            'service_list_id' => $serviceListId,
            'origin' => trim((string) $this->request->getPost('origin')),
            'destination' => trim((string) $this->request->getPost('destination')),
            'rate' => (string) $this->request->getPost('rate'),
            'description' => trim((string) $this->request->getPost('description')),
            'is_active' => 1,
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add service rate.');
        }

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Service rate added successfully.');
    }

    public function updateServiceRate(int $rateId)
    {
        $this->ensureBranchAdminAccess();

        $serviceRateModel = new ServiceRateModel();
        $rate = $serviceRateModel->find($rateId);
        if (! $rate) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'origin' => 'required|max_length[100]',
            'destination' => 'required|max_length[100]',
            'rate' => 'required|decimal|greater_than[0]',
            'description' => 'permit_empty|max_length[500]',
            'is_active' => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $serviceRateModel->update($rateId, [
            'origin' => trim((string) $this->request->getPost('origin')),
            'destination' => trim((string) $this->request->getPost('destination')),
            'rate' => (string) $this->request->getPost('rate'),
            'description' => trim((string) $this->request->getPost('description')),
            'is_active' => (int) $this->request->getPost('is_active'),
        ]);

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Service rate updated successfully.');
    }

    public function deleteServiceRate(int $rateId)
    {
        $this->ensureBranchAdminAccess();

        $serviceRateModel = new ServiceRateModel();
        $rate = $serviceRateModel->find($rateId);
        if (! $rate) {
            throw PageNotFoundException::forPageNotFound();
        }

        $serviceRateModel->delete($rateId);

        return redirect()->to('/branch-admin/service-package/packages')->with('success', 'Service rate deleted successfully.');
    }
}
