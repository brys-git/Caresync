<?php

namespace App\Services;

use App\Models\MembershipProgramModel;
use App\Models\PackageItemModel;
use App\Models\PackageModel;

/**
 * PlanBuilderService
 *
 * Fix Prompts Task 5A. A "plan" here = one membership_programs row + the
 * exactly one package it entitles (a packages row, always flagged
 * is_damayan_entitlement=1 - the thing a plan holder actually gets to
 * claim) + that package's inclusions (package_items). This service is the
 * only place that writes both halves together, in one transaction, so a
 * plan row can never point at a missing/mismatched package.
 *
 * Deliberately does not touch MembershipService's MONTHLY_FEE/
 * TOTAL_CONTRIBUTION/DEFAULT_PACKAGE_ID constants or any existing
 * registration/payment code path - this prompt only builds the catalog
 * management screen. The system keeps running on the Damayan constants
 * until/unless a later phase (5B) wires registration to read the chosen
 * plan instead.
 */
class PlanBuilderService
{
    private MembershipProgramModel $programModel;
    private PackageModel $packageModel;
    private PackageItemModel $itemModel;

    public function __construct()
    {
        $this->programModel = new MembershipProgramModel();
        $this->packageModel = new PackageModel();
        $this->itemModel = new PackageItemModel();
    }

    /**
     * Every plan for the index table: entitled package name, inclusion
     * count, and how many plan holders are actually on that package
     * (plans.package_id, restored as the system of record - see
     * caresync-panel-revision-project notes).
     */
    public function list(): array
    {
        $db = db_connect();

        $rows = $this->programModel
            ->select('membership_programs.*, packages.package_name')
            ->join('packages', 'packages.package_id = membership_programs.package_id', 'left')
            ->orderBy('membership_programs.program_id', 'ASC')
            ->findAll();

        foreach ($rows as &$row) {
            $packageId = (int) ($row['package_id'] ?? 0);

            $row['inclusion_count'] = $packageId > 0
                ? $this->itemModel->where('package_id', $packageId)->countAllResults()
                : 0;

            $row['plan_holder_count'] = $packageId > 0
                ? (int) $db->table('plans')->where('package_id', $packageId)->countAllResults()
                : 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * One plan + its package + inclusions, for the show/edit pages.
     */
    public function find(int $programId): ?array
    {
        $program = $this->programModel->find($programId);
        if (! $program) {
            return null;
        }

        $packageId = (int) ($program['package_id'] ?? 0);
        $package = $packageId > 0 ? $this->packageModel->find($packageId) : null;
        $inclusions = $packageId > 0
            ? $this->itemModel->where('package_id', $packageId)->orderBy('item_id', 'ASC')->findAll()
            : [];

        return [
            'program'    => $program,
            'package'    => $package,
            'inclusions' => $inclusions,
        ];
    }

    /**
     * Create or update a plan: the packages row, its package_items
     * (replaced wholesale - reordering isn't supported, matching the
     * brief), and the membership_programs row, all in one transaction.
     * term_months is always computed here (ceil(plan_price /
     * monthly_fee)) - never trusted from the form, which only shows it
     * live-calculated for information.
     *
     * @param array{plan_name:string, plan_price:float, monthly_fee:float, description:string, is_active:int, package_name:string, package_description:string, inclusions:array<int,array{item_name:string,description:string}>} $data
     * @return array{success: bool, errors: array<string,string>, program_id: ?int}
     */
    public function save(array $data, ?int $programId = null): array
    {
        $errors = $this->validate($data, $programId);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'program_id' => null];
        }

        $db = db_connect();
        $db->transStart();

        $existing = $programId ? $this->programModel->find($programId) : null;
        $packageId = (int) ($existing['package_id'] ?? 0) ?: null;

        $packagePayload = [
            'package_name'           => $data['package_name'],
            'description'            => $data['package_description'] ?? '',
            'base_price'             => $data['plan_price'],
            'is_customizable'        => 0,
            'is_available'           => 1,
            'is_damayan_entitlement' => 1,
            'status'                 => 'approved',
        ];

        if ($packageId) {
            $this->packageModel->update($packageId, $packagePayload);
        } else {
            $packageId = (int) $this->packageModel->insert($packagePayload, true);
        }

        $this->itemModel->where('package_id', $packageId)->delete();
        foreach ($data['inclusions'] as $inclusion) {
            $itemName = trim((string) ($inclusion['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }
            $this->itemModel->insert([
                'package_id'  => $packageId,
                'item_name'   => $itemName,
                'description' => trim((string) ($inclusion['description'] ?? '')),
            ]);
        }

        $termMonths = (int) ceil($data['plan_price'] / $data['monthly_fee']);

        $programPayload = [
            'program_name' => $data['plan_name'],
            'plan_price'   => $data['plan_price'],
            'monthly_fee'  => $data['monthly_fee'],
            'term_months'  => $termMonths,
            'package_id'   => $packageId,
            'description'  => $data['description'] ?? '',
            'is_active'    => $data['is_active'] ?? 1,
        ];

        if ($programId) {
            $this->programModel->update($programId, $programPayload);
        } else {
            $programId = (int) $this->programModel->insert($programPayload, true);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['success' => false, 'errors' => ['general' => 'Unable to save plan. Please try again.'], 'program_id' => null];
        }

        return ['success' => true, 'errors' => [], 'program_id' => $programId];
    }

    /**
     * Refuses to deactivate the last active plan, and refuses to delete
     * (this service has no delete()) a plan with plan holders on it -
     * deactivate instead. There is no hard-delete method at all: a plan a
     * real plan holder is on must never disappear from under them.
     */
    public function setActive(int $programId, bool $active): array
    {
        if (! $active) {
            $current = $this->programModel->find($programId);
            if (! $current) {
                return ['success' => false, 'error' => 'Plan not found.'];
            }

            if ((int) $current['is_active'] === 1) {
                $activeCount = $this->programModel->where('is_active', 1)->countAllResults();
                if ($activeCount <= 1) {
                    return ['success' => false, 'error' => 'Cannot deactivate the last active plan.'];
                }
            }
        }

        $this->programModel->update($programId, ['is_active' => $active ? 1 : 0]);

        return ['success' => true, 'error' => null];
    }

    /**
     * @return array<string,string> field => message
     */
    private function validate(array $data, ?int $programId): array
    {
        $errors = [];

        $planName = trim((string) ($data['plan_name'] ?? ''));
        if ($planName === '') {
            $errors['plan_name'] = 'Plan name is required.';
        } elseif (mb_strlen($planName) > 150) {
            $errors['plan_name'] = 'Plan name must be 150 characters or fewer.';
        } else {
            $duplicate = $this->programModel->where('program_name', $planName);
            if ($programId) {
                $duplicate->where('program_id !=', $programId);
            }
            if ($duplicate->countAllResults() > 0) {
                $errors['plan_name'] = 'A plan with this name already exists.';
            }
        }

        $planPrice = (float) ($data['plan_price'] ?? 0);
        if ($planPrice <= 0) {
            $errors['plan_price'] = 'Plan price must be greater than zero.';
        }

        $monthlyFee = (float) ($data['monthly_fee'] ?? 0);
        if ($monthlyFee <= 0) {
            $errors['monthly_fee'] = 'Monthly contribution must be greater than zero.';
        } elseif ($planPrice > 0 && $monthlyFee > $planPrice) {
            $errors['monthly_fee'] = 'Monthly contribution cannot exceed the plan price.';
        }

        if (trim((string) ($data['package_name'] ?? '')) === '') {
            $errors['package_name'] = 'Package name is required.';
        }

        $inclusions = array_values(array_filter(
            $data['inclusions'] ?? [],
            static fn ($i) => trim((string) ($i['item_name'] ?? '')) !== ''
        ));
        if ($inclusions === []) {
            $errors['inclusions'] = 'At least one inclusion is required.';
        }

        return $errors;
    }
}
