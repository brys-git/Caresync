<?php

namespace App\Controllers;

use App\Models\PackageItemModel;
use App\Models\PackageModel;
use App\Models\PackageVersionModel;
use App\Models\PlanModel;

class Packages extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');

        if ($roleId === 1) {
            return redirect()->to('/admin/service-offer?tab=packages')->send();
        }

        $packageModel = new PackageModel();
        $packages = $packageModel->orderBy('package_name', 'ASC')->findAll();

        $packageItems = $db->table('package_items pi')
            ->select('pi.item_id, pi.package_id, pi.item_name, pi.description, p.package_name')
            ->join('packages p', 'p.package_id = pi.package_id', 'inner')
            ->orderBy('p.package_name', 'ASC')
            ->orderBy('pi.item_name', 'ASC')
            ->get()
            ->getResultArray();

        $packageVersions = $db->table('package_versions pv')
            ->select('pv.version_id, pv.package_id, pv.price, pv.effective_date, pv.status, p.package_name')
            ->join('packages p', 'p.package_id = pv.package_id', 'inner')
            ->orderBy('p.package_name', 'ASC')
            ->orderBy('pv.effective_date', 'DESC')
            ->get()
            ->getResultArray();

        $planHolderBuilder = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.branch_id, ph.unique_identifier, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC');

        if ($roleId === 2 && $branchId > 0) {
            $planHolderBuilder->where('ph.branch_id', $branchId);
        }

        $planHolders = $planHolderBuilder->get()->getResultArray();

        return view('packages/index', [
            'packages' => $packages,
            'package_items' => $packageItems,
            'package_versions' => $packageVersions,
            'plan_holders' => $planHolders,
            'role_layout' => $this->resolveLayoutView(),
        ]);
    }

    public function storePackage()
    {
        $rules = [
            'package_name' => 'required|max_length[100]',
            'base_price' => 'required|decimal',
            'is_customizable' => 'required|in_list[0,1]',
            'initial_effective_date' => 'required|valid_date',
            'initial_version_status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $packageModel = new PackageModel();
            $versionModel = new PackageVersionModel();

            $packageId = (int) $packageModel->insert([
                'package_name' => trim((string) $this->request->getPost('package_name')),
                'description' => trim((string) $this->request->getPost('description')),
                'base_price' => (string) $this->request->getPost('base_price'),
                'is_customizable' => (int) $this->request->getPost('is_customizable'),
            ], true);

            if ($packageId <= 0) {
                throw new \RuntimeException('Failed to create package.');
            }

            $savedVersion = $versionModel->insert([
                'package_id' => $packageId,
                'price' => (string) $this->request->getPost('base_price'),
                'effective_date' => (string) $this->request->getPost('initial_effective_date'),
                'status' => (string) $this->request->getPost('initial_version_status'),
            ]);

            if (! $savedVersion) {
                throw new \RuntimeException('Failed to create initial price version.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return redirect()->to('/packages')->with('success', 'Package created with initial price version.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function storeItem()
    {
        $rules = [
            'package_id' => 'required|is_natural_no_zero',
            'item_name' => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $itemModel = new PackageItemModel();
        $saved = $itemModel->insert([
            'package_id' => (int) $this->request->getPost('package_id'),
            'item_name' => trim((string) $this->request->getPost('item_name')),
            'description' => trim((string) $this->request->getPost('description')),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to add package item.');
        }

        return redirect()->to('/packages')->with('success', 'Package item added.');
    }

    public function storeVersion()
    {
        $rules = [
            'package_id' => 'required|is_natural_no_zero',
            'price' => 'required|decimal',
            'effective_date' => 'required|valid_date',
            'status' => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $versionModel = new PackageVersionModel();
        $saved = $versionModel->insert([
            'package_id' => (int) $this->request->getPost('package_id'),
            'price' => (string) $this->request->getPost('price'),
            'effective_date' => (string) $this->request->getPost('effective_date'),
            'status' => (string) $this->request->getPost('status'),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to create package version.');
        }

        return redirect()->to('/packages')->with('success', 'Price version created.');
    }

    public function assignToPlan()
    {
        $rules = [
            'plan_holder_id' => 'required|is_natural_no_zero',
            'package_id' => 'required|is_natural_no_zero',
            'version_id' => 'required|is_natural_no_zero',
            'start_date' => 'required|valid_date',
            'status' => 'required|in_list[active,inactive,completed]',
            'passbook_fee' => 'permit_empty|decimal',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $packageId = (int) $this->request->getPost('package_id');
        $versionId = (int) $this->request->getPost('version_id');

        $versionModel = new PackageVersionModel();
        $version = $versionModel->find($versionId);

        if (! $version || (int) $version['package_id'] !== $packageId) {
            return redirect()->back()->withInput()->with('error', 'Selected version does not belong to selected package.');
        }

        $lockedPrice = (string) $version['price'];
        $planModel = new PlanModel();

        $saved = $planModel->insert([
            'plan_holder_id' => (int) $this->request->getPost('plan_holder_id'),
            'package_id' => $packageId,
            'version_id' => $versionId,
            'monthly_fee' => $lockedPrice,
            'passbook_fee' => $this->nullablePost('passbook_fee') ?? '50.00',
            'start_date' => (string) $this->request->getPost('start_date'),
            'status' => (string) $this->request->getPost('status'),
            'months_paid' => 0,
            'remaining_balance' => $lockedPrice,
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to assign package version to plan holder.');
        }

        return redirect()->to('/packages')->with('success', 'Package version assigned to plan with locked price.');
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

    protected function nullablePost(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }
}
