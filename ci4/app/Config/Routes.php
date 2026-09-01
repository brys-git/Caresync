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

// Create Plan (panel brief section 1: create a plan supporting different
// packages/benefit tiers, and offer upgrade/cross-sell paths based on a
// plan holder's current plan). Admin: 1, BranchAdmin: 2, Staff: 3 - the
// controller's own ensureAccess() is the same set, this is defense in
// depth. Previously unrouted entirely - this whole feature (including the
// one place that can assign a package to a plan holder's plan at all)
// existed in code but had no route pointing at it.
$routes->get('packages', 'Packages::index', ['filter' => 'role:1,2,3']);
$routes->post('packages/create', 'Packages::storePackage', ['filter' => 'role:1,2,3']);
$routes->post('packages/add-item', 'Packages::storeItem', ['filter' => 'role:1,2,3']);
$routes->post('packages/add-version', 'Packages::storeVersion', ['filter' => 'role:1,2,3']);
$routes->post('packages/assign-plan', 'Packages::assignToPlan', ['filter' => 'role:1,2,3']);
