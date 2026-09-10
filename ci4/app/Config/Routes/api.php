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

    // PSGC Cloud address proxy for the Applicant Information form's
    // Town/City -> Barangay dropdowns (client/plan_registration.php).
    // Behind 'auth' only (not role:4) since Branch Admin/Staff/Admin also
    // reach this same PHP session context when assisting a walk-in
    // applicant; the endpoints themselves are read-only and carry no
    // plan-holder-specific data.
    $routes->group('address', ['filter' => 'auth'], static function (RouteCollection $routes) {
        $routes->get('cities', 'Api\AddressController::cities');
        $routes->get('barangays/(:segment)', 'Api\AddressController::barangays/$1');
    });

    // Reusable Government ID Verification component (App\Services\
    // GovernmentIdVerificationService). Behind 'auth' only, same reasoning
    // as address/ above - every registration flow that needs an ID
    // verification step calls this same endpoint; the controller itself
    // always verifies against session('user_id'), never a value the
    // browser sends, so a user can only ever verify their own submission.
    $routes->group('id-verification', ['filter' => 'auth'], static function (RouteCollection $routes) {
        $routes->get('types', 'Api\IdVerificationController::idTypes');
        $routes->post('verify', 'Api\IdVerificationController::verify');
    });
});

// AJAX endpoints
$routes->group('ajax', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Placeholder for AJAX endpoints that don't fit traditional patterns
    // e.g., $routes->post('check-eligibility', 'Ajax\EligibilityController::check');
});
