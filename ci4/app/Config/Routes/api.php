<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * API Routes (Future Use)
 * For REST API endpoints and AJAX requests
 */

// API group for future REST endpoints
$routes->group('api', static function (RouteCollection $routes) {
    // PSGC address reference (served from the local cache; upstream psgc.cloud is
    // only consulted to seed the cache the first time a level is requested).
    $routes->get('address/provinces', 'Api\AddressController::provinces');
    $routes->get('address/cities/(:alphanum)', 'Api\AddressController::cities/$1');
    $routes->get('address/barangays/(:alphanum)', 'Api\AddressController::barangays/$1');
});

// AJAX endpoints
$routes->group('ajax', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Placeholder for AJAX endpoints that don't fit traditional patterns
    // e.g., $routes->post('check-eligibility', 'Ajax\EligibilityController::check');
});
