<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Main Routes Configuration
 * 
 * This file loads all role-based route modules for cleaner organization
 * and better maintainability. Each role has its own dedicated route file.
 * 
 * Route Files:
 * - auth.php        : Authentication routes (login, register, etc.)
 * - admin.php       : System Admin routes (Role 1)
 * - branch_admin.php: Branch Admin routes (Role 2)
 * - staff.php       : Staff routes (Role 3)
 * - client.php      : Plan Holder / Client routes (Role 4)
 * - api.php         : API and AJAX endpoints (Future use)
 */

// Load route modules
require APPPATH . 'Config/Routes/auth.php';
require APPPATH . 'Config/Routes/admin.php';
require APPPATH . 'Config/Routes/branch_admin.php';
require APPPATH . 'Config/Routes/staff.php';
require APPPATH . 'Config/Routes/client.php';
require APPPATH . 'Config/Routes/api.php';

// Role-based dashboard redirects
$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:1']);
    $routes->get('branch-admin', 'Dashboard::branchAdmin', ['filter' => 'role:2']);
    $routes->get('staff', 'Dashboard::staff', ['filter' => 'role:3']);
    $routes->get('plan-holder', 'Client\ClientDashboardController::dashboard', ['filter' => 'role:4']);
});

// Plan Holder Registration Routes (accessible by Admin: 1, BranchAdmin: 2)
$routes->get('plan-holders/register', 'PlanHolders::register', ['filter' => 'auth']);
$routes->post('plan-holders/store', 'PlanHolders::store', ['filter' => 'auth']);
$routes->post('plan-holders/approvals/approve/(:num)', 'PlanHolders::approve/$1', ['filter' => 'auth']);
$routes->post('plan-holders/approvals/reject/(:num)', 'PlanHolders::reject/$1', ['filter' => 'auth']);
