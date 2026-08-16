<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Local endpoint for the PSGC (Philippine Standard Geographic Code) reference data
 * used by the registration wizard's Province / Town-City / Barangay dropdowns.
 *
 * Data is served from the address_provinces / address_cities / address_barangays
 * tables. A level is fetched from the upstream psgc.cloud API only the first time
 * it is requested (lazy seed), so subsequent page loads never depend on a
 * third-party service. If the upstream is unreachable on a cold cache, the endpoint
 * returns an empty list and the wizard's existing "Unable to load" fallback applies
 * instead of hard-failing registration.
 */
class AddressController extends BaseController
{
    private const PSGC_BASE = 'https://psgc.cloud/api/v2';
    private const FETCH_TIMEOUT = 8;

    public function provinces(): ResponseInterface
    {
        $table = 'address_provinces';
        if (! db_connect()->tableExists($table)) {
            return $this->respond([]);
        }

        $rows = db_connect()->table($table)
            ->select('code, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            $rows = $this->fetchAndCache('provinces', $table, []);
        }

        return $this->respond($rows);
    }

    public function cities(string $provinceCode): ResponseInterface
    {
        $table = 'address_cities';
        $provinceCode = preg_replace('/[^A-Za-z0-9]/', '', (string) $provinceCode);

        if ($provinceCode === '' || ! db_connect()->tableExists($table)) {
            return $this->respond([]);
        }

        $rows = db_connect()->table($table)
            ->select('code, name')
            ->where('province_code', $provinceCode)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            $rows = $this->fetchAndCache(
                'provinces/' . urlencode($provinceCode) . '/cities-municipalities',
                $table,
                ['province_code' => $provinceCode]
            );
        }

        return $this->respond($rows);
    }

    public function barangays(string $cityCode): ResponseInterface
    {
        $table = 'address_barangays';
        $cityCode = preg_replace('/[^A-Za-z0-9]/', '', (string) $cityCode);

        if ($cityCode === '' || ! db_connect()->tableExists($table)) {
            return $this->respond([]);
        }

        $rows = db_connect()->table($table)
            ->select('code, name')
            ->where('city_code', $cityCode)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            $rows = $this->fetchAndCache(
                'cities-municipalities/' . urlencode($cityCode) . '/barangays',
                $table,
                ['city_code' => $cityCode]
            );
        }

        return $this->respond($rows);
    }

    private function respond(array $rows): ResponseInterface
    {
        return $this->response->setJSON(['data' => array_values($rows)]);
    }

    /**
     * Fetch one level from the upstream PSGC API and cache it locally.
     * Returns the rows as stored (sorted by name), or [] when the upstream is
     * unreachable or the table is missing.
     */
    private function fetchAndCache(string $psgcPath, string $table, array $extra): array
    {
        $db = db_connect();
        if (! $db->tableExists($table)) {
            return [];
        }

        $url = self::PSGC_BASE . '/' . $psgcPath;
        $context = stream_context_create([
            'http' => [
                'timeout' => self::FETCH_TIMEOUT,
                'ignore_errors' => true,
                'user_agent' => 'CareSync/1.0 (+psgc-address-cache)',
            ],
        ]);

        try {
            $raw = @file_get_contents($url, false, $context);
        } catch (\Throwable $e) {
            $raw = false;
        }

        $decoded = ($raw === false) ? null : json_decode($raw, true);
        $items = is_array($decoded) ? ($decoded['data'] ?? $decoded) : [];
        if (! is_array($items) || empty($items)) {
            return [];
        }

        $inserted = 0;
        foreach ($items as $item) {
            $code = (string) ($item['code'] ?? '');
            $name = trim((string) ($item['name'] ?? ''));
            if ($code === '' || $name === '') {
                continue;
            }

            $exists = $db->table($table)->where('code', $code)->countAllResults() > 0;
            if (! $exists) {
                $row = $extra;
                $row['code'] = $code;
                $row['name'] = $name;
                $db->table($table)->insert($row);
                $inserted++;
            }
        }

        // Return from the DB so ordering and shape are always consistent.
        $builder = $db->table($table)->select('code, name');
        foreach ($extra as $column => $value) {
            $builder->where($column, $value);
        }

        return $builder->orderBy('name', 'ASC')->get()->getResultArray();
    }
}
