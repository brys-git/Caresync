<?php

namespace App\Services;

/**
 * PsgcService
 *
 * Province -> city/municipality -> barangay lookups for the two Mindoro provinces the branches operate in (Oriental
 * Mindoro: app/Data/OrientalMindoroAddress.php; Occidental Mindoro: app/Data/OccidentalMindoroAddress.php, both PSA PSGC),
 * read from local files - no remote API is called while registering.
 *
 * Shared by the registration form (server-side validation that a submitted Province -> Town/City -> Barangay triple is
 * real and actually related: a client can't be trusted to send back only codes this service handed out), by
 * cs_format_address() (province name for display) and by the registration view (the branch's own province is the default).
 *
 * Accepts either the current 10-digit PSGC code or the legacy 9-digit old_code on input and matches against both, so plan
 * holders saved before the switch keep validating.
 */
class PsgcService
{
    /** Data files, one per province. */
    private const DATA_FILES = [
        APPPATH . 'Data/OccidentalMindoroAddress.php',
        APPPATH . 'Data/OrientalMindoroAddress.php',
    ];

    /** @var list<array<string, mixed>>|null one entry per province, each with its 'cities' */
    private static ?array $provinces = null;

    /** @var array<string, array<string, mixed>>|null city rows keyed by both code and old_code (each carries 'province_code') */
    private static ?array $citiesByCode = null;

    /** @var array<string, array{code: string, name: string, city_code: string}>|null keyed by both code and old_code */
    private static ?array $barangaysByCode = null;

    /** @return list<array{code: string, name: string}> */
    public function getProvinces(): array
    {
        $this->load();

        return array_map(static fn (array $p): array => ['code' => $p['province_code'], 'name' => $p['province']], self::$provinces);
    }

    /**
     * Cities/municipalities of one province (all provinces when $provinceCode is null).
     *
     * @return list<array{code: string, name: string}>|null null if $provinceCode is not a known province
     */
    public function getCities(?string $provinceCode = null): ?array
    {
        $this->load();

        $out = [];
        foreach (self::$provinces as $province) {
            if ($provinceCode !== null && $province['province_code'] !== trim($provinceCode)) {
                continue;
            }
            foreach ($province['cities'] as $city) {
                $out[] = ['code' => $city['code'], 'name' => $city['name']];
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * @return list<array{code: string, name: string}>|null null if $cityCode isn't a real city/municipality code.
     */
    public function getBarangaysForCity(string $cityCode): ?array
    {
        $this->load();

        $city = $this->findCity($cityCode);
        if ($city === null) {
            return null;
        }

        return array_map(
            static fn (array $barangay): array => ['code' => $barangay['code'], 'name' => $barangay['name']],
            $city['barangays']
        );
    }

    /** True only if $cityCode is a real city/municipality and $barangayCode is one of its actual barangays. */
    public function isBarangayInCity(string $cityCode, string $barangayCode): bool
    {
        $this->load();

        $city = $this->findCity($cityCode);
        if ($city === null) {
            return false;
        }

        $barangay = self::$barangaysByCode[trim($barangayCode)] ?? null;

        return $barangay !== null && $barangay['city_code'] === $city['code'];
    }

    /** True only if $cityCode is a city/municipality of the province $provinceCode. */
    public function isCityInProvince(string $provinceCode, string $cityCode): bool
    {
        $city = $this->findCity($cityCode);

        return $city !== null && $city['province_code'] === trim($provinceCode);
    }

    /** The province a city/municipality code belongs to (code), or null. */
    public function provinceCodeForCity(string $cityCode): ?string
    {
        $this->load();

        return $this->findCity($cityCode)['province_code'] ?? null;
    }

    /** "Oriental Mindoro" for 1705200000 (or any city code inside it); null when unknown. */
    public function provinceName(string $provinceOrCityCode): ?string
    {
        $this->load();
        $code = trim($provinceOrCityCode);
        foreach (self::$provinces as $province) {
            if ($province['province_code'] === $code) {
                return $province['province'];
            }
        }
        $city = $this->findCity($code);

        return $city !== null ? $this->provinceName($city['province_code']) : null;
    }

    /** The province code for a province NAME as stored on a branch ("Oriental Mindoro"); null when it isn't one we have data for. */
    public function provinceCodeForName(string $name): ?string
    {
        $this->load();
        $needle = strtolower(trim($name));
        foreach (self::$provinces as $province) {
            if (strtolower($province['province']) === $needle) {
                return $province['province_code'];
            }
        }

        return null;
    }

    /** The province a branch sits in (its address_province), as a code: the default for a registration at that branch. */
    public function provinceCodeForBranch(int $branchId): ?string
    {
        if ($branchId <= 0) {
            return null;
        }
        $row = db_connect()->table('branches')->select('address_province')->where('branch_id', $branchId)->get(1)->getRowArray();

        return $row ? $this->provinceCodeForName((string) $row['address_province']) : null;
    }

    /** @return array<string, mixed>|null the city row (with its 'barangays' and 'province_code') matched by either code format. */
    private function findCity(string $cityCode): ?array
    {
        $this->load();
        $cityCode = trim($cityCode);

        return $cityCode === '' ? null : (self::$citiesByCode[$cityCode] ?? null);
    }

    private function load(): void
    {
        if (self::$provinces !== null) {
            return;
        }

        self::$provinces = [];
        self::$citiesByCode = [];
        self::$barangaysByCode = [];

        foreach (self::DATA_FILES as $file) {
            $data = require $file;
            self::$provinces[] = $data;

            foreach ($data['cities'] as $city) {
                $city['province_code'] = $data['province_code'];
                self::$citiesByCode[$city['code']] = $city;
                self::$citiesByCode[$city['old_code']] = $city;

                foreach ($city['barangays'] as $barangay) {
                    $row = ['code' => $barangay['code'], 'name' => $barangay['name'], 'city_code' => $city['code']];
                    self::$barangaysByCode[$barangay['code']] = $row;
                    self::$barangaysByCode[$barangay['old_code']] = $row;
                }
            }
        }
    }
}
