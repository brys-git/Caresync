<?php

namespace App\Controllers;

use App\Services\PlanBuilderService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fix Prompts Task 5A. Replaces the "Plan builder" nav item, which used to
 * point at the four-step Packages module (Create Package/Add Item/Set
 * Price Version/Assign to Plan Holder) - a real plan-definition screen
 * (name, price, monthly contribution, term, entitled package + its
 * inclusions) that never existed. See PlanBuilderService for what a "plan"
 * means here. Mutating routes are role:1-only at the route level
 * (App\Config\Routes.php) - Branch Admin/Staff reach only index()/show().
 */
class PlanBuilder extends BaseController
{
    private PlanBuilderService $service;

    public function __construct()
    {
        $this->service = new PlanBuilderService();
    }

    public function index(): string
    {
        return view('plan_builder/index', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title'  => 'Plan Builder',
            'page_sub'    => 'Every sellable plan and what it entitles.',
            'plans'       => $this->service->list(),
            'can_manage'  => $this->canManage(),
        ]);
    }

    public function create(): string
    {
        return view('plan_builder/form', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title'  => 'New Plan',
            'program_id'  => null,
            'plan'        => null,
            'package'     => null,
            'inclusions'  => [],
            'errors'      => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function store(): ResponseInterface
    {
        $result = $this->service->save($this->collectInput());

        if (! $result['success']) {
            return redirect()->to('/plan-builder/create')->withInput()->with('errors', $result['errors'])->with('error', 'Please fix the errors below.');
        }

        return redirect()->to('/plan-builder/' . $result['program_id'])->with('success', 'Plan created successfully.');
    }

    public function show(int $programId): string
    {
        $found = $this->service->find($programId);
        if ($found === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('plan_builder/show', [
            'role_layout'       => $this->resolveLayoutView(),
            'page_title'        => (string) $found['program']['program_name'],
            'program'           => $found['program'],
            'package'           => $found['package'],
            'inclusions'        => $found['inclusions'],
            'plan_holder_count' => $found['plan_holder_count'],
            'can_manage'        => $this->canManage(),
        ]);
    }

    public function edit(int $programId): string
    {
        $found = $this->service->find($programId);
        if ($found === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('plan_builder/form', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title'  => 'Edit Plan',
            'program_id'  => $programId,
            'plan'        => $found['program'],
            'package'     => $found['package'],
            'inclusions'  => $found['inclusions'],
            'errors'      => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function update(int $programId): ResponseInterface
    {
        $result = $this->service->save($this->collectInput(), $programId);

        if (! $result['success']) {
            return redirect()->to('/plan-builder/' . $programId . '/edit')->withInput()->with('errors', $result['errors'])->with('error', 'Please fix the errors below.');
        }

        return redirect()->to('/plan-builder/' . $programId)->with('success', 'Plan updated successfully.');
    }

    public function toggle(int $programId): ResponseInterface
    {
        $found = $this->service->find($programId);
        if ($found === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $activateNext = (int) ($found['program']['is_active'] ?? 0) !== 1;
        $result = $this->service->setActive($programId, $activateNext);

        if (! $result['success']) {
            return redirect()->back()->with('error', (string) $result['error']);
        }

        return redirect()->back()->with('success', $activateNext ? 'Plan activated.' : 'Plan deactivated.');
    }

    public function destroy(int $programId): ResponseInterface
    {
        $result = $this->service->delete($programId);

        if (! $result['success']) {
            return redirect()->to('/plan-builder/' . $programId)->with('error', (string) $result['error']);
        }

        return redirect()->to('/plan-builder')->with('success', 'Plan deleted.');
    }

    private function canManage(): bool
    {
        return (int) session('role_id') === 1;
    }

    private function collectInput(): array
    {
        $rawInclusions = (array) ($this->request->getPost('inclusions') ?? []);

        $inclusions = [];
        foreach ($rawInclusions as $row) {
            $row = (array) $row;
            $inclusions[] = [
                'item_id'     => (int) ($row['item_id'] ?? 0) ?: null,
                'item_name'   => trim((string) ($row['item_name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        return [
            'plan_name'           => trim((string) $this->request->getPost('plan_name')),
            'plan_price'          => (float) $this->request->getPost('plan_price'),
            'monthly_fee'         => (float) $this->request->getPost('monthly_fee'),
            'description'         => trim((string) $this->request->getPost('description')),
            'is_active'           => (string) $this->request->getPost('is_active') === '1' ? 1 : 0,
            'package_name'        => trim((string) $this->request->getPost('package_name')),
            'package_description' => trim((string) $this->request->getPost('package_description')),
            'inclusions'          => $inclusions,
        ];
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session('role_id');

        if ($role === 2) {
            return 'layouts/branch_admin';
        }

        if ($role === 3) {
            return 'layouts/staff';
        }

        return 'layouts/admin';
    }
}
