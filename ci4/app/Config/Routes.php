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
 * - collector.php   : Collector routes (Role 5)
 * - api.php         : API and AJAX endpoints (Future use)
 */

// Load route modules
require APPPATH . 'Config/Routes/auth.php';
require APPPATH . 'Config/Routes/admin.php';
require APPPATH . 'Config/Routes/branch_admin.php';
require APPPATH . 'Config/Routes/staff.php';
require APPPATH . 'Config/Routes/client.php';
require APPPATH . 'Config/Routes/collector.php';
require APPPATH . 'Config/Routes/api.php';

// Role-based dashboard redirects
$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:1']);
    $routes->get('branch-admin', 'Dashboard::branchAdmin', ['filter' => 'role:2']);
    $routes->get('staff', 'Dashboard::staff', ['filter' => 'role:3']);
    $routes->get('plan-holder', 'ClientPortal::dashboard', ['filter' => 'role:4']);
    $routes->get('collector', 'Dashboard::collector', ['filter' => 'role:5']);
});

// Plan Holder Registration Routes (accessible by Admin: 1, BranchAdmin: 2)
$routes->get('plan-holders/register', 'PlanHolders::register', ['filter' => 'auth']);
$routes->post('plan-holders/store', 'PlanHolders::store', ['filter' => 'auth']);

// User account creation (Admin: 1, BranchAdmin: 2, Staff: 3 — Users::store forces
// Staff-created accounts to role_id 4 regardless of what's submitted). Previously
// unrouted: this screen existed but was unreachable from any page in the app,
// which also meant the new Collector role had nowhere to actually be assigned.
$routes->get('users/create', 'Users::create', ['filter' => 'role:1,2,3']);
$routes->post('users/create', 'Users::store', ['filter' => 'role:1,2,3']);
