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
    // Placeholder for future API endpoints
    // e.g., $routes->get('members', 'Api\MembersController::index');
});

// AJAX endpoints
$routes->group('ajax', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Placeholder for AJAX endpoints that don't fit traditional patterns
    // e.g., $routes->post('check-eligibility', 'Ajax\EligibilityController::check');
});
