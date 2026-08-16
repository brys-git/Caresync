<?php

namespace App\Services;

class ServiceManagementService
{
    public function getServiceOfferingsByBranch(int $branchId): array
    {
        return db_connect()->table('services s')
            ->select("s.service_id, COALESCE(sl.service_name, '-') AS service_type, s.notes, s.status, s.branch_id, b.branch_name", false)
            ->join('branches b', 'b.branch_id = s.branch_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->where('sl.is_available', 1)
            ->where('s.branch_id', $branchId)
            ->groupStart()
                ->where('s.plan_holder_id IS NULL', null, false)
                ->orWhere('s.plan_holder_id', 0)
            ->groupEnd()
            ->orderBy('s.service_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getServicesByBranch(int $branchId): array
    {
        return db_connect()->table('services s')
            ->select("s.service_id, s.plan_holder_id, s.package_id, COALESCE(sl.service_name, '-') AS service_type, s.service_date, s.service_time, s.burial_location, s.notes, s.status, s.assigned_staff, b.branch_name, u.first_name, u.last_name", false)
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = s.branch_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->where('sl.is_available', 1)
            ->where('s.branch_id', $branchId)
            ->orderBy('s.service_date', 'DESC')
            ->orderBy('s.service_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getServiceDetails(int $id): ?array
    {
        $db = db_connect();

        $service = $db->table('services s')
            ->select('s.*, p.package_name, ph.unique_identifier, ph.branch_id, u.first_name, u.last_name, u.contact_number, st.first_name AS staff_first_name, st.last_name AS staff_last_name')
            ->join('packages p', 'p.package_id = s.package_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'left')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->join('users st', 'st.user_id = s.assigned_staff', 'left')
            ->where('s.service_id', $id)
            ->get()
            ->getRowArray();

        if (! $service) {
            return null;
        }

        $service['costs'] = $db->table('service_costs')
            ->select('cost_id, service_id, description, amount')
            ->where('service_id', $id)
            ->orderBy('cost_id', 'DESC')
            ->get()
            ->getResultArray();

        $service['add_ons'] = $db->table('service_add_ons')
            ->select('add_on_id, service_id, item_name, price')
            ->where('service_id', $id)
            ->orderBy('add_on_id', 'DESC')
            ->get()
            ->getResultArray();

        return $service;
    }

    public function createService(array $data): int
    {
        $db = db_connect();
        $saved = $db->table('services')->insert([
            'plan_holder_id' => (int) $data['plan_holder_id'],
            'branch_id' => (int) $data['branch_id'],
            'service_list_id' => isset($data['service_list_id']) && $data['service_list_id'] !== '' ? (int) $data['service_list_id'] : null,
            'package_id' => (int) $data['package_id'],
            'total_cost' => number_format((float) $data['total_cost'], 2, '.', ''),
            'service_date' => (string) $data['service_date'],
            'service_time' => $this->nullable($data['service_time'] ?? null),
            'burial_location' => $this->nullable($data['burial_location'] ?? null),
            'assigned_staff' => isset($data['assigned_staff']) && $data['assigned_staff'] !== '' ? (int) $data['assigned_staff'] : null,
            'notes' => $this->nullable($data['notes'] ?? null),
            'status' => (string) ($data['status'] ?? 'pending'),
        ]);

        if (! $saved) {
            throw new \RuntimeException('Failed to create service.');
        }

        return (int) $db->insertID();
    }

    public function updateService(int $id, array $data): void
    {
        $updated = db_connect()->table('services')
            ->where('service_id', $id)
            ->update([
                'service_list_id' => isset($data['service_list_id']) && $data['service_list_id'] !== '' ? (int) $data['service_list_id'] : null,
                'package_id' => (int) $data['package_id'],
                'service_date' => (string) $data['service_date'],
                'service_time' => $this->nullable($data['service_time'] ?? null),
                'burial_location' => $this->nullable($data['burial_location'] ?? null),
                'notes' => $this->nullable($data['notes'] ?? null),
            ]);

        if (! $updated) {
            throw new \RuntimeException('Failed to update service.');
        }
    }

    public function getPackages(): array
    {
        $db = db_connect();

        return $db->table('packages p')
            ->select('p.package_id, p.package_name, p.description, p.base_price, p.is_customizable, pv.price AS active_price, pv.effective_date')
            ->where('p.is_available', 1)
            ->join(
                '(SELECT v1.package_id, v1.price, v1.effective_date
                    FROM package_versions v1
                    INNER JOIN (
                        SELECT package_id, MAX(effective_date) AS max_effective_date
                        FROM package_versions
                        WHERE status = \'active\'
                        GROUP BY package_id
                    ) m ON m.package_id = v1.package_id AND m.max_effective_date = v1.effective_date
                    WHERE v1.status = \'active\'
                ) pv',
                'pv.package_id = p.package_id',
                'left'
            )
            ->orderBy('p.package_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function createPackage(array $data): int
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $saved = $db->table('packages')->insert([
                'package_name' => (string) $data['package_name'],
                'description' => $this->nullable($data['description'] ?? null),
                'base_price' => number_format((float) $data['base_price'], 2, '.', ''),
                'is_customizable' => (int) $data['is_customizable'],
            ]);

            if (! $saved) {
                throw new \RuntimeException('Failed to create package.');
            }

            $packageId = (int) $db->insertID();

            $db->table('package_versions')->insert([
                'package_id' => $packageId,
                'price' => number_format((float) $data['base_price'], 2, '.', ''),
                'effective_date' => (string) ($data['initial_effective_date'] ?? date('Y-m-d')),
                'status' => 'active',
            ]);

            $items = $data['items'] ?? [];
            foreach ($items as $item) {
                $itemName = trim((string) ($item['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $db->table('package_items')->insert([
                    'package_id' => $packageId,
                    'item_name' => $itemName,
                    'description' => $this->nullable($item['description'] ?? null),
                ]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to complete package transaction.');
            }

            $db->transCommit();

            return $packageId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function getServiceRequests(int $branchId): array
    {
        return db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.plan_holder_id, sa.package_id, sa.status, sa.created_at, u.first_name, u.last_name, p.package_name, ph.branch_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('packages p', 'p.package_id = sa.package_id', 'left')
            ->where('p.is_available', 1)
            ->where('ph.branch_id', $branchId)
            ->orderBy('sa.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function approveServiceRequest(int $id): void
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $request = $db->table('service_applications sa')
                ->select('sa.*, ph.branch_id, ph.user_id, p.base_price, p.package_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
                ->join('packages p', 'p.package_id = sa.package_id', 'left')
                ->where('sa.application_id', $id)
                ->get()
                ->getRowArray();

            if (! $request) {
                throw new \RuntimeException('Service request not found.');
            }

            $db->table('service_applications')->where('application_id', $id)->update(['status' => 'approved']);

            $db->table('services')->insert([
                'plan_holder_id' => (int) $request['plan_holder_id'],
                'branch_id' => (int) $request['branch_id'],
                'service_list_id' => null,
                'package_id' => (int) $request['package_id'],
                'total_cost' => number_format((float) ($request['base_price'] ?? 0), 2, '.', ''),
                'service_date' => date('Y-m-d'),
                'service_time' => null,
                'burial_location' => null,
                'assigned_staff' => null,
                'notes' => 'Created from approved service request.',
                'status' => 'pending',
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to approve service request.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function rejectServiceRequest(int $id): void
    {
        $updated = db_connect()->table('service_applications')
            ->where('application_id', $id)
            ->update(['status' => 'rejected']);

        if (! $updated) {
            throw new \RuntimeException('Failed to reject service request.');
        }
    }

    public function getOngoingServices(int $branchId): array
    {
        return db_connect()->table('services s')
            ->select("s.service_id, COALESCE(sl.service_name, '-') AS service_type, s.service_date, s.status, s.assigned_staff, u.first_name, u.last_name, st.first_name AS staff_first_name, st.last_name AS staff_last_name", false)
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->join('users st', 'st.user_id = s.assigned_staff', 'left')
            ->where('s.branch_id', $branchId)
            ->orderBy('s.service_date', 'DESC')
            ->orderBy('s.service_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getPlanHoldersByBranch(int $branchId): array
    {
        return db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.user_id, ph.unique_identifier, ph.branch_id, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('ph.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStaffByBranch(int $branchId): array
    {
        return db_connect()->table('users')
            ->select('user_id, first_name, last_name')
            ->where('role_id', 3)
            ->where('branch_id', $branchId)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPackageDetails(int $id): ?array
    {
        $db = db_connect();

        $package = $db->table('packages')
            ->select('package_id, package_name, description, base_price, is_customizable')
            ->where('package_id', $id)
            ->get()
            ->getRowArray();

        if (! $package) {
            return null;
        }

        $package['items'] = $db->table('package_items')
            ->select('item_id, item_name, description')
            ->where('package_id', $id)
            ->orderBy('item_id', 'ASC')
            ->get()
            ->getResultArray();

        $package['versions'] = $db->table('package_versions')
            ->select('version_id, price, effective_date, status')
            ->where('package_id', $id)
            ->orderBy('effective_date', 'DESC')
            ->orderBy('version_id', 'DESC')
            ->get()
            ->getResultArray();

        return $package;
    }

    public function updatePackage(int $id, array $data): void
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $db->table('packages')->where('package_id', $id)->update([
                'package_name' => (string) $data['package_name'],
                'description' => $this->nullable($data['description'] ?? null),
                'is_customizable' => (int) $data['is_customizable'],
            ]);

            if (! empty($data['new_price']) && ! empty($data['effective_date'])) {
                $db->table('package_versions')->insert([
                    'package_id' => $id,
                    'price' => number_format((float) $data['new_price'], 2, '.', ''),
                    'effective_date' => (string) $data['effective_date'],
                    'status' => (string) ($data['version_status'] ?? 'active'),
                ]);
            }

            $items = $data['new_items'] ?? [];
            foreach ($items as $item) {
                $itemName = trim((string) ($item['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $db->table('package_items')->insert([
                    'package_id' => $id,
                    'item_name' => $itemName,
                    'description' => $this->nullable($item['description'] ?? null),
                ]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to update package.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
