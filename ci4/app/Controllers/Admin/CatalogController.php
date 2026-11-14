<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AddonModel;
use App\Models\PackageModel;
use App\Models\ServiceListModel;
use App\Services\ActivityLogService;

/**
 * Packages & Services Management: one module, three tabs (Funeral Packages,
 * Services, Optional Add-ons), each with its own create/edit form.
 *
 * Items are never deleted - they are Archived, so anything already linked to
 * a client transaction stays intact. Transactions copy the price at the time
 * (application_amount, services.total_cost), so changing a price here does not
 * touch past records.
 */
class CatalogController extends BaseController
{
    private const TYPES = ['packages', 'services', 'addons'];
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const IMAGE_MAX_BYTES = 2097152; // 2 MB

    private ActivityLogService $activityLog;

    public function __construct()
    {
        $this->activityLog = new ActivityLogService();
    }

    public function index(): string
    {
        $this->ensureAdminAccess();

        $tab = (string) $this->request->getGet('tab');
        if (! in_array($tab, self::TYPES, true)) {
            $tab = 'packages';
        }

        $search = cs_search_term($this->request->getGet('search'));
        $show = (string) $this->request->getGet('show'); // '' = active items, 'archived'
        $db = db_connect();

        $rows = match ($tab) {
            'packages' => $db->table('packages')->orderBy('package_name', 'ASC')->get()->getResultArray(),
            'services' => $db->table('service_list')->orderBy('service_name', 'ASC')->get()->getResultArray(),
            default    => $db->table('addons')->orderBy('addon_name', 'ASC')->get()->getResultArray(),
        };
        $rows = array_values(array_filter($rows, static fn (array $r): bool => ($show === 'archived') === (($r['availability_status'] ?? 'available') === 'archived')));
        $rows = cs_filter_rows($rows, $search, ['package_name', 'service_name', 'addon_name', 'item_code', 'short_description', 'category']);

        if ($tab === 'services') {
            foreach ($rows as &$row) {
                $row['routes'] = $db->table('service_routes')->where('service_list_id', $row['service_list_id'])->orderBy('sort_order', 'ASC')->orderBy('route_id', 'ASC')->get()->getResultArray();
            }
            unset($row);
        }

        return view('admin/catalog/index', [
            'role_layout' => 'layouts/admin',
            'page_title'  => 'Packages & Services',
            'page_sub'    => 'Manage funeral packages, services and optional add-ons.',
            'tab'         => $tab,
            'search'      => $search,
            'show'        => $show === 'archived' ? 'archived' : '',
            'rows'        => $rows,
        ]);
    }

    public function create(string $type): string
    {
        $this->ensureAdminAccess();

        return $this->renderForm($this->requireType($type), null);
    }

    public function edit(string $type, int $id): string
    {
        $this->ensureAdminAccess();
        $type = $this->requireType($type);

        $item = $this->findItem($type, $id);
        if (! $item) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->renderForm($type, $item);
    }

    public function save(string $type, int $id = 0)
    {
        $this->ensureAdminAccess();
        $type = $this->requireType($type);

        $item = $id > 0 ? $this->findItem($type, $id) : null;
        if ($id > 0 && ! $item) {
            return redirect()->to('/admin/catalog?tab=' . $type)->with('error', 'Item not found.');
        }

        return match ($type) {
            'packages' => $this->savePackage($item),
            'services' => $this->saveService($item),
            default    => $this->saveAddon($item),
        };
    }

    /** Archive or restore. Never a hard delete. */
    public function setAvailability(string $type, int $id)
    {
        $this->ensureAdminAccess();
        $type = $this->requireType($type);

        $item = $this->findItem($type, $id);
        $status = (string) $this->request->getPost('availability_status');
        if (! $item || ! in_array($status, ['available', 'unavailable', 'archived'], true)) {
            return redirect()->to('/admin/catalog?tab=' . $type)->with('error', 'Item not found.');
        }

        $this->applyStatus($type, $id, $status);
        $this->audit($type === 'packages' ? 'package' : ($type === 'services' ? 'service' : 'addon'), $id, $status === 'archived' ? 'archived' : 'updated', 'Set ' . $item['item_code'] . ' to ' . $status, ['availability_status' => $item['availability_status']], ['availability_status' => $status]);

        return redirect()->to('/admin/catalog?tab=' . $type . ($status === 'archived' ? '' : '&show=archived'))
            ->with('success', ($item['package_name'] ?? $item['service_name'] ?? $item['addon_name']) . ($status === 'archived' ? ' was archived. Existing records are unchanged.' : ' is now ' . $status . '.'));
    }

    // ------------------------------------------------------------------
    // Packages
    // ------------------------------------------------------------------

    private function savePackage(?array $item)
    {
        $in = $this->input(['package_name', 'category', 'variant', 'base_price', 'description', 'short_description', 'availability_status', 'stock_quantity']);
        $errors = [];

        $in['base_price'] = str_replace(',', '', $in['base_price']);
        if ($in['package_name'] === '') {
            $errors['package_name'] = 'Package name is required.';
        } elseif (mb_strlen($in['package_name']) > 100) {
            $errors['package_name'] = 'Must be 100 characters or fewer.';
        }
        if (! in_array($in['category'], ['wood', 'metal'], true)) {
            $errors['category'] = 'Choose a package category.';
        }
        if (! in_array($in['variant'], ['regular', 'oversized', 'half_glass', 'full_glass'], true)) {
            $errors['variant'] = 'Choose a package variant.';
        }
        if (! is_numeric($in['base_price']) || (float) $in['base_price'] <= 0) {
            $errors['base_price'] = 'Enter a price greater than 0.';
        }
        if ($in['description'] === '') {
            $errors['description'] = 'Package description is required.';
        }
        if (! in_array($in['availability_status'], ['available', 'unavailable'], true)) {
            $in['availability_status'] = 'available';
        }
        if ($in['stock_quantity'] !== '' && (! ctype_digit($in['stock_quantity']) || (int) $in['stock_quantity'] > 100000)) {
            $errors['stock_quantity'] = 'Enter a whole number of 0 or more, or leave blank.';
        }

        $db = db_connect();
        if (! isset($errors['package_name']) && ! isset($errors['category']) && ! isset($errors['variant'])) {
            $dup = $db->table('packages')
                ->where('LOWER(package_name)', mb_strtolower($in['package_name']))
                ->where('category', $in['category'])
                ->where('variant', $in['variant']);
            if ($item) {
                $dup->where('package_id !=', (int) $item['package_id']);
            }
            if ($dup->countAllResults() > 0) {
                $errors['package_name'] = 'A package with this name, category and variant already exists.';
            }
        }

        [$image, $imageError] = $this->handleImage('image', $item === null || empty($item['image_path']), $item['image_path'] ?? null);
        if ($imageError) {
            $errors['image'] = $imageError;
        }

        $inclusionIds = array_map('intval', (array) $this->request->getPost('inclusions'));
        $master = $db->table('package_inclusion_items')->orderBy('sort_order', 'ASC')->get()->getResultArray();
        $inclusionIds = array_values(array_intersect($inclusionIds, array_column($master, 'inclusion_item_id')));

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $adminId = (int) session('user_id');
        $now = date('Y-m-d H:i:s');
        $row = [
            'package_name'      => $in['package_name'],
            'category'          => $in['category'],
            'variant'           => $in['variant'],
            'base_price'        => number_format((float) $in['base_price'], 2, '.', ''),
            'description'       => $in['description'],
            'short_description' => $in['short_description'] !== '' ? mb_substr($in['short_description'], 0, 255) : null,
            'stock_quantity'    => $in['stock_quantity'] !== '' ? (int) $in['stock_quantity'] : null,
            'image_path'        => $image,
            'updated_by'        => $adminId,
            'updated_at'        => $now,
        ] + $this->flags('packages', $in['availability_status']);

        $model = new PackageModel();
        $db->transStart();
        if ($item) {
            $id = (int) $item['package_id'];
            if (empty($item['item_code'])) {
                $row['item_code'] = $this->nextCode('packages', 'PKG');
            }
            $model->update($id, $row);

            // A price change adds a new price version; earlier versions stay as history.
            if (round((float) $item['base_price'], 2) !== round((float) $in['base_price'], 2)) {
                $db->table('package_versions')->where('package_id', $id)->update(['status' => 'inactive']);
                $db->table('package_versions')->insert(['package_id' => $id, 'price' => $row['base_price'], 'effective_date' => date('Y-m-d'), 'status' => 'active']);
            }
        } else {
            $row += ['item_code' => $this->nextCode('packages', 'PKG'), 'is_customizable' => 0, 'created_by' => $adminId, 'created_at' => $now];
            $id = (int) $model->insert($row, true);
            $db->table('package_versions')->insert(['package_id' => $id, 'price' => $row['base_price'], 'effective_date' => date('Y-m-d'), 'status' => 'active']);
        }

        // Inclusions are covered by the package price; they are display rows only
        // (package_items) and are never priced or billed separately. Untouched
        // when the selection did not change, so custom rows survive.
        if ($inclusionIds !== $this->checkedInclusionIds($id, $master)) {
            $db->table('package_items')->where('package_id', $id)->delete();
            foreach ($master as $m) {
                if (in_array((int) $m['inclusion_item_id'], $inclusionIds, true)) {
                    $db->table('package_items')->insert(['package_id' => $id, 'item_name' => $m['item_name'], 'description' => $m['description']]);
                }
            }
        }
        $db->transComplete();

        $this->audit('package', $id, $item ? 'updated' : 'created', ($item ? 'Updated ' : 'Created ') . $row['package_name'], $item, $row);

        return redirect()->to('/admin/catalog?tab=packages')->with('success', 'Package "' . $row['package_name'] . '" was ' . ($item ? 'updated.' : 'created.'));
    }

    /** @return int[] master item ids that the package currently lists. */
    private function checkedInclusionIds(int $packageId, array $master): array
    {
        $names = array_map('mb_strtolower', array_column(db_connect()->table('package_items')->select('item_name')->where('package_id', $packageId)->get()->getResultArray(), 'item_name'));
        $checked = [];
        foreach ($master as $m) {
            $needle = mb_strtolower($m['item_name']);
            foreach ($names as $n) {
                if ($n === $needle || str_starts_with($n, $needle)) {
                    $checked[] = (int) $m['inclusion_item_id'];
                    break;
                }
            }
        }

        return $checked;
    }

    // ------------------------------------------------------------------
    // Services
    // ------------------------------------------------------------------

    private function saveService(?array $item)
    {
        $in = $this->input(['service_name', 'category', 'description', 'short_description', 'eligibility_rules', 'terms', 'notes', 'availability_status']);
        $errors = [];

        foreach (['service_name' => 'Service name', 'category' => 'Service category', 'description' => 'Service description'] as $k => $label) {
            if ($in[$k] === '') {
                $errors[$k] = $label . ' is required.';
            }
        }
        if (mb_strlen($in['service_name']) > 100) {
            $errors['service_name'] = 'Must be 100 characters or fewer.';
        }
        if (mb_strlen($in['category']) > 100) {
            $errors['category'] = 'Must be 100 characters or fewer.';
        }
        if (! in_array($in['availability_status'], ['available', 'unavailable', 'archived'], true)) {
            $in['availability_status'] = 'available';
        }

        $db = db_connect();
        if ($in['service_name'] !== '' && ! isset($errors['service_name'])) {
            $dup = $db->table('service_list')->where('LOWER(service_name)', mb_strtolower($in['service_name']));
            if ($item) {
                $dup->where('service_list_id !=', (int) $item['service_list_id']);
            }
            if ($dup->countAllResults() > 0) {
                $errors['service_name'] = 'A service with this name already exists.';
            }
        }

        // Route rows: origin / destination / fee, no repeated origin-destination pair.
        $routes = [];
        $seen = [];
        $ids = (array) $this->request->getPost('route_id');
        $origins = (array) $this->request->getPost('origin');
        $dests = (array) $this->request->getPost('destination');
        $fees = (array) $this->request->getPost('fee');
        foreach ($origins as $i => $origin) {
            $origin = trim((string) $origin);
            $dest = trim((string) ($dests[$i] ?? ''));
            $fee = str_replace(',', '', trim((string) ($fees[$i] ?? '')));
            if ($origin === '' && $dest === '' && $fee === '') {
                continue;
            }
            $n = count($routes) + 1;
            if ($origin === '' || $dest === '') {
                $errors['routes'] = "Route {$n}: origin and destination are required.";
                break;
            }
            if (! is_numeric($fee) || (float) $fee <= 0) {
                $errors['routes'] = "Route {$n}: enter a service fee greater than 0.";
                break;
            }
            $key = mb_strtolower($origin) . '|' . mb_strtolower($dest);
            if (isset($seen[$key])) {
                $errors['routes'] = "Route {$n}: {$origin} to {$dest} is listed more than once.";
                break;
            }
            $seen[$key] = true;
            $routes[] = ['id' => (int) ($ids[$i] ?? 0), 'origin' => $origin, 'destination' => $dest, 'fee' => number_format((float) $fee, 2, '.', '')];
        }
        if ($routes === [] && ! isset($errors['routes'])) {
            $errors['routes'] = 'Add at least one route with its service fee.';
        }

        // Routes that clients already booked cannot be removed.
        if ($item && ! isset($errors['routes'])) {
            $keep = array_column($routes, 'id');
            foreach ($db->table('service_routes')->where('service_list_id', (int) $item['service_list_id'])->get()->getResultArray() as $existing) {
                if (! in_array((int) $existing['route_id'], $keep, true)
                    && $db->table('service_applications')->where('selected_route_id', (int) $existing['route_id'])->countAllResults() > 0) {
                    $errors['routes'] = 'The route "' . $existing['route_name'] . '" is used by existing requests and cannot be removed.';
                    break;
                }
            }
        }

        [$image, $imageError] = $this->handleImage('image', false, $item['image_path'] ?? null);
        if ($imageError) {
            $errors['image'] = $imageError;
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $adminId = (int) session('user_id');
        $now = date('Y-m-d H:i:s');
        $row = [
            'service_name'      => $in['service_name'],
            'category'          => $in['category'],
            'description'       => $in['description'],
            'short_description' => $in['short_description'] !== '' ? mb_substr($in['short_description'], 0, 255) : null,
            'eligibility_rules' => $in['eligibility_rules'] !== '' ? $in['eligibility_rules'] : null,
            'terms'             => $in['terms'] !== '' ? $in['terms'] : null,
            'notes'             => $in['notes'] !== '' ? $in['notes'] : null,
            'base_price'        => min(array_column($routes, 'fee')),  // "from" price; the route fee is what is charged
            'image_path'        => $image,
            'updated_by'        => $adminId,
            'updated_at'        => $now,
        ] + $this->flags('services', $in['availability_status']);

        $model = new ServiceListModel();
        $db->transStart();
        if ($item) {
            $id = (int) $item['service_list_id'];
            if (empty($item['item_code'])) {
                $row['item_code'] = $this->nextCode('services', 'SRV');
            }
            $model->update($id, $row);
        } else {
            $row += ['item_code' => $this->nextCode('services', 'SRV'), 'created_by' => $adminId];
            $id = (int) $model->insert($row, true);
        }

        // Existing routes are updated in place (ids are referenced by past requests).
        $keep = [];
        foreach ($routes as $order => $r) {
            $data = ['origin' => $r['origin'], 'destination' => $r['destination'], 'route_name' => $r['origin'] . ' to ' . $r['destination'], 'price' => $r['fee'], 'sort_order' => $order + 1];
            $owned = $r['id'] > 0 && $db->table('service_routes')->where('route_id', $r['id'])->where('service_list_id', $id)->countAllResults() > 0;
            if ($owned) {
                $db->table('service_routes')->where('route_id', $r['id'])->update($data);
                $keep[] = $r['id'];
            } else {
                $db->table('service_routes')->insert($data + ['service_list_id' => $id]);
                $keep[] = (int) $db->insertID();
            }
        }
        $stale = $db->table('service_routes')->where('service_list_id', $id);
        if ($keep !== []) {
            $stale->whereNotIn('route_id', $keep);
        }
        $stale->delete();
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'The service could not be saved. Please try again.');
        }

        $this->audit('service', $id, $item ? 'updated' : 'created', ($item ? 'Updated ' : 'Created ') . $row['service_name'], $item, $row + ['routes' => $routes]);

        return redirect()->to('/admin/catalog?tab=services')->with('success', 'Service "' . $row['service_name'] . '" was ' . ($item ? 'updated.' : 'created.'));
    }

    // ------------------------------------------------------------------
    // Add-ons
    // ------------------------------------------------------------------

    private function saveAddon(?array $item)
    {
        $in = $this->input(['addon_name', 'short_description', 'description', 'pricing_method', 'price', 'min_price', 'max_price', 'availability_status']);
        $errors = [];

        if ($in['addon_name'] === '') {
            $errors['addon_name'] = 'Add-on name is required.';
        } elseif (mb_strlen($in['addon_name']) > 100) {
            $errors['addon_name'] = 'Must be 100 characters or fewer.';
        }
        if (! in_array($in['pricing_method'], ['fixed', 'range'], true)) {
            $errors['pricing_method'] = 'Choose a pricing method.';
        }
        if (! in_array($in['availability_status'], ['available', 'unavailable', 'archived'], true)) {
            $in['availability_status'] = 'available';
        }

        $money = static fn (string $v): string => str_replace(',', '', $v);
        $price = $min = $max = null;
        if ($in['pricing_method'] === 'fixed') {
            $v = $money($in['price']);
            if (! is_numeric($v) || (float) $v <= 0) {
                $errors['price'] = 'Enter a price greater than 0.';
            } else {
                $price = number_format((float) $v, 2, '.', '');
            }
        } elseif ($in['pricing_method'] === 'range') {
            $a = $money($in['min_price']);
            $b = $money($in['max_price']);
            if (! is_numeric($a) || (float) $a <= 0) {
                $errors['min_price'] = 'Enter a minimum price greater than 0.';
            }
            if (! is_numeric($b) || (float) $b <= 0) {
                $errors['max_price'] = 'Enter a maximum price greater than 0.';
            } elseif (is_numeric($a) && (float) $b < (float) $a) {
                $errors['max_price'] = 'Maximum must be greater than or equal to the minimum.';
            }
            if (! isset($errors['min_price']) && ! isset($errors['max_price'])) {
                $min = number_format((float) $a, 2, '.', '');
                $max = number_format((float) $b, 2, '.', '');
            }
        }

        $db = db_connect();
        if ($in['addon_name'] !== '' && ! isset($errors['addon_name'])) {
            $dup = $db->table('addons')->where('LOWER(addon_name)', mb_strtolower($in['addon_name']));
            if ($item) {
                $dup->where('addon_id !=', (int) $item['addon_id']);
            }
            if ($dup->countAllResults() > 0) {
                $errors['addon_name'] = 'An add-on with this name already exists.';
            }
        }

        [$image, $imageError] = $this->handleImage('image', false, $item['image_path'] ?? null);
        if ($imageError) {
            $errors['image'] = $imageError;
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $adminId = (int) session('user_id');
        $now = date('Y-m-d H:i:s');
        $row = [
            'addon_name'          => $in['addon_name'],
            'short_description'   => $in['short_description'] !== '' ? mb_substr($in['short_description'], 0, 255) : null,
            'description'         => $in['description'] !== '' ? $in['description'] : null,
            'pricing_method'      => $in['pricing_method'],
            'price'               => $price,
            'min_price'           => $min,
            'max_price'           => $max,
            'image_path'          => $image,
            'availability_status' => $in['availability_status'],
            'updated_by'          => $adminId,
            'updated_at'          => $now,
        ];

        $model = new AddonModel();
        if ($item) {
            $id = (int) $item['addon_id'];
            if (empty($item['item_code'])) {
                $row['item_code'] = $this->nextCode('addons', 'ADD');
            }
            $model->update($id, $row);
        } else {
            $row += ['item_code' => $this->nextCode('addons', 'ADD'), 'created_by' => $adminId, 'created_at' => $now];
            $id = (int) $model->insert($row, true);
        }

        $this->audit('addon', $id, $item ? 'updated' : 'created', ($item ? 'Updated ' : 'Created ') . $row['addon_name'], $item, $row);

        return redirect()->to('/admin/catalog?tab=addons')->with('success', 'Add-on "' . $row['addon_name'] . '" was ' . ($item ? 'updated.' : 'created.'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    private function renderForm(string $type, ?array $item): string
    {
        $db = db_connect();
        $data = [
            'role_layout' => 'layouts/admin',
            'page_title'  => ($item ? 'Edit ' : 'Create ') . match ($type) { 'packages' => 'Funeral Package', 'services' => 'Service', default => 'Optional Add-on' },
            'page_sub'    => 'Fields marked * are required.',
            'item'        => $item,
            'tab'         => $type,
            'next_code'   => $item['item_code'] ?? $this->nextCode($type, match ($type) { 'packages' => 'PKG', 'services' => 'SRV', default => 'ADD' }),
        ];

        if ($type === 'packages') {
            $master = $db->table('package_inclusion_items')->orderBy('sort_order', 'ASC')->get()->getResultArray();
            $data['inclusions'] = $master;
            $data['checked'] = $item ? $this->checkedInclusionIds((int) $item['package_id'], $master) : array_map('intval', array_column($master, 'inclusion_item_id'));
        }
        if ($type === 'services') {
            $data['routes'] = $item
                ? $db->table('service_routes')->where('service_list_id', (int) $item['service_list_id'])->orderBy('sort_order', 'ASC')->orderBy('route_id', 'ASC')->get()->getResultArray()
                : [];
        }

        return view('admin/catalog/form_' . $type, $data);
    }

    private function findItem(string $type, int $id): ?array
    {
        [$table, $pk] = match ($type) {
            'packages' => ['packages', 'package_id'],
            'services' => ['service_list', 'service_list_id'],
            default    => ['addons', 'addon_id'],
        };

        return db_connect()->table($table)->where($pk, $id)->get()->getRowArray() ?: null;
    }

    private function requireType(string $type): string
    {
        if (! in_array($type, self::TYPES, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $type;
    }

    /** @param string[] $keys */
    private function input(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = trim((string) $this->request->getPost($k));
        }

        return $out;
    }

    /**
     * The legacy columns every existing client query filters on, kept in step
     * with availability_status. Packages stay 'approved' unless archived.
     */
    private function flags(string $type, string $status): array
    {
        $available = $status === 'available' ? 1 : 0;
        if ($type === 'packages') {
            return ['availability_status' => $status, 'is_available' => $available, 'status' => $status === 'archived' ? 'inactive' : 'approved'];
        }

        return ['availability_status' => $status, 'is_available' => $available, 'status' => $available ? 'active' : 'inactive'];
    }

    private function applyStatus(string $type, int $id, string $status): void
    {
        $adminId = (int) session('user_id');
        $stamp = ['updated_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s')];

        match ($type) {
            'packages' => (new PackageModel())->update($id, $stamp + $this->flags('packages', $status)),
            'services' => (new ServiceListModel())->update($id, $stamp + $this->flags('services', $status)),
            default    => (new AddonModel())->update($id, $stamp + ['availability_status' => $status]),
        };
    }

    /** Next code like PKG-0004 (highest existing number + 1). */
    private function nextCode(string $type, string $prefix): string
    {
        $table = match ($type) { 'packages' => 'packages', 'services' => 'service_list', default => 'addons' };
        $max = 0;
        foreach (db_connect()->table($table)->select('item_code')->like('item_code', $prefix . '-', 'after')->get()->getResultArray() as $r) {
            $max = max($max, (int) substr((string) $r['item_code'], strlen($prefix) + 1));
        }

        return sprintf('%s-%04d', $prefix, $max + 1);
    }

    /**
     * @return array{0:?string,1:?string} [stored path, error]. With no new upload the current path is kept.
     */
    private function handleImage(string $field, bool $required, ?string $current): array
    {
        $file = $this->request->getFile($field);
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required ? [null, 'An image is required (JPG, PNG or WebP).'] : [$current, null];
        }
        if (! $file->isValid()) {
            return [$current, 'The upload failed: ' . $file->getErrorString() . '.'];
        }
        if ($file->getSize() > self::IMAGE_MAX_BYTES) {
            return [$current, 'The image must be 2 MB or smaller.'];
        }
        if (! in_array($file->getMimeType(), self::IMAGE_MIMES, true) || @getimagesize($file->getTempName()) === false) {
            return [$current, 'Only JPG, PNG or WebP images are allowed.'];
        }

        $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'catalog';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return [$current, 'The image folder could not be created.'];
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);

        return ['uploads/catalog/' . $name, null];
    }

    private function audit(string $module, int $id, string $action, string $description, ?array $old, ?array $new): void
    {
        $this->activityLog->log((int) session('user_id'), $action, 'catalog_' . $module, $id, $description, $old, $new);
    }

    private function ensureAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 1 && $roleName !== 'admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
