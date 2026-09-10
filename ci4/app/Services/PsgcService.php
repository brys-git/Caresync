<?php

namespace App\Services;

use Config\Services;

/**
 * PsgcService
 *
 * Fetches and caches Philippine city/municipality and barangay data from
 * PSGC Cloud (https://psgc.cloud/api-docs/v2). Shared by Api\
 * AddressController (the browser-facing search/list endpoints for the
 * Applicant Information form's address dropdowns) and
 * ClientRegistrationController (server-side validation that a submitted
 * Town/City -> Barangay pair is real and actually related - a client
 * can't be trusted to send back only PSGC codes this service itself
 * handed out).
 */
class PsgcService
{
    private const BASE_URL = 'https://psgc.cloud/api/v2';
    private const CITIES_CACHE_KEY = 'psgc_cities_municipalities';
    private const CACHE_TTL = 86400; // 24 hours - PSGC data is essentially static

    /**
     * @return array<int, array{code: string, name: string}>|null null only
     * on an upstream/cache failure - never an empty result set.
     */
    public function getCities(): ?array
    {
        return $this->fetchCached(self::CITIES_CACHE_KEY, self::BASE_URL . '/cities-municipalities');
    }

    /**
     * @return array<int, array{code: string, name: string}>|null
     */
    public function getBarangaysForCity(string $cityCode): ?array
    {
        $cityCode = trim($cityCode);
        if ($cityCode === '' || ! ctype_digit($cityCode)) {
            return null;
        }

        return $this->fetchCached(
            'psgc_barangays_' . $cityCode,
            self::BASE_URL . '/cities-municipalities/' . $cityCode . '/barangays'
        );
    }

    /**
     * True only if $cityCode is a real PSGC city/municipality and
     * $barangayCode is one of its actual barangays. Used to validate a
     * submitted registration before saving - never trusts codes sent from
     * the browser at face value.
     */
    public function isBarangayInCity(string $cityCode, string $barangayCode): bool
    {
        $barangayCode = trim($barangayCode);
        if ($barangayCode === '') {
            return false;
        }

        $barangays = $this->getBarangaysForCity($cityCode);
        if ($barangays === null) {
            return false;
        }

        foreach ($barangays as $row) {
            if ($row['code'] === $barangayCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{code: string, name: string}>|null
     */
    private function fetchCached(string $cacheKey, string $url): ?array
    {
        $cache = Services::cache();

        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->fetchFromPsgc($url);
        if ($rows === null) {
            return null;
        }

        $cache->save($cacheKey, $rows, self::CACHE_TTL);

        return $rows;
    }

    /**
     * @return array<int, array{code: string, name: string}>|null
     */
    private function fetchFromPsgc(string $url): ?array
    {
        try {
            $response = Services::curlrequest(['timeout' => 8])->get($url);

            if ($response->getStatusCode() !== 200) {
                log_message('error', '[PSGC] Non-200 response from ' . $url . ': ' . $response->getStatusCode());

                return null;
            }

            $body = json_decode((string) $response->getBody(), true);
            if (! is_array($body) || ! isset($body['data']) || ! is_array($body['data'])) {
                log_message('error', '[PSGC] Unexpected response shape from ' . $url);

                return null;
            }

            return $this->normalizeRows($body['data']);
        } catch (\Throwable $e) {
            log_message('error', '[PSGC] Request to ' . $url . ' failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{code: string, name: string}>
     */
    private function normalizeRows(array $rows): array
    {
        return array_values(array_filter(array_map(static function ($row): ?array {
            if (! is_array($row)) {
                return null;
            }

            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                return null;
            }

            return ['code' => $code, 'name' => $name];
        }, $rows)));
    }
}
