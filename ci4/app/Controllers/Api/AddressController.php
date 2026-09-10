<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PsgcService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AddressController
 *
 * Server-side proxy for the PSGC Cloud API (https://psgc.cloud/api-docs/v2),
 * used by the Applicant Information form's Town/City -> Barangay dropdowns
 * (client/plan_registration.php). Proxying instead of calling PSGC Cloud
 * directly from the browser keeps the upstream URL/shape out of the
 * frontend and gives one place to turn a raw upstream failure into the
 * friendly, non-technical error message the applicant sees instead of a
 * stack trace. The actual fetch/cache logic lives in PsgcService, shared
 * with ClientRegistrationController's server-side save-time validation.
 */
class AddressController extends BaseController
{
    private const FRIENDLY_ERROR = 'Unable to load Philippine address data. Please try again.';

    /**
     * GET /api/address/cities?q=<search>
     * Searchable list of cities/municipalities. The full PSGC list (~1,600
     * rows) is cached by PsgcService and filtered here so the browser only
     * ever receives a short, relevant slice - never the whole country.
     */
    public function cities(): ResponseInterface
    {
        $query = trim((string) $this->request->getGet('q'));

        $rows = (new PsgcService())->getCities();
        if ($rows === null) {
            return $this->response->setStatusCode(502)->setJSON(['error' => self::FRIENDLY_ERROR]);
        }

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => mb_strpos(mb_strtolower($row['name']), $needle) !== false
            ));
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->response->setJSON(['data' => array_slice($rows, 0, 50)]);
    }

    /**
     * GET /api/address/barangays/(:segment)
     * Barangays belonging to one city/municipality PSGC code - never the
     * full nationwide barangay list.
     */
    public function barangays(string $cityCode): ResponseInterface
    {
        $cityCode = trim($cityCode);
        if ($cityCode === '' || ! ctype_digit($cityCode)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid town/city selected.']);
        }

        $rows = (new PsgcService())->getBarangaysForCity($cityCode);
        if ($rows === null) {
            return $this->response->setStatusCode(502)->setJSON(['error' => self::FRIENDLY_ERROR]);
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->response->setJSON(['data' => $rows]);
    }
}
