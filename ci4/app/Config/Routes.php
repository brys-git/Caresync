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

// Unknown URLs: the branded CareSync 404 page (sent through the filters, so it also gets the security headers).
$routes->set404Override('App\Controllers\ErrorPage::notFound');

// One-time database update for hosts without a command line. Inert (404) unless .env has app.migrateKey.
$routes->get('migrate/(:segment)', 'MigrateOnce::run/$1');

// Role-based dashboard redirects
$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:1']);
    $routes->get('branch-admin', 'Dashboard::branchAdmin', ['filter' => 'role:2']);
    $routes->get('staff', 'Dashboard::staff', ['filter' => 'role:3']);
    $routes->get('collector', 'Dashboard::collector', ['filter' => 'role:5']);
});

// Plan holder verification queue (Admin: 1, BranchAdmin: 2). The old POST plan-holders/store registration route was
// removed: every registration goes through the shared wizard (branch-admin/client/register, admin/client/register).
$routes->get('plan-holders/register', 'PlanHolders::register', ['filter' => 'auth']);
// Approve/reject a pending plan holder registration - PlanHolders::approve()/
// reject() already existed fully implemented (role check + branch scoping
// done inside the controller, same as register/store above) but were never
// wired to a route, so the Approve/Reject buttons on the approvals tab of
// plan-holders/register 404'd. Found during the 2026-09-09 system scan.
$routes->post('plan-holders/approvals/approve/(:num)', 'PlanHolders::approve/$1', ['filter' => 'auth']);
$routes->post('plan-holders/approvals/reject/(:num)', 'PlanHolders::reject/$1', ['filter' => 'auth']);

// Verify a pending GCash initial payment from the registration-approvals
// queue (approvals/registration_queue.php's "Verify" button) - the button
// already posted to this exact path, and ClientPortal::verifyInitialPayment()
// already fully implemented it (branch-scoped, checks the GCash reference,
// marks the payment paid), but no route existed for it at all. Found
// during the 2026-09-09 system scan.
$routes->post('payments/verify-initial/(:num)', 'ClientPortal::verifyInitialPayment/$1', ['filter' => 'role:2']);

// The old "User accounts" page is now Users > Add user (admin/users/create). Old links and
// bookmarks still work: this redirect is a closure and does not use any controller.
// Branch Admin and Staff create accounts from their own pages
// (branch-admin/create-account, staff/create-account).
$routes->get('users/create', static fn () => redirect()->to('/admin/users/create', 301), ['filter' => 'role:1']);

// Receipt PDF for one paid payment. Plan holders get their own; Branch Admin and
// Staff get their branch's; System Admin any. Scoping is in ReceiptPdfService.
$routes->get('receipt/(:num)', 'ReceiptController::download/$1', ['filter' => 'role:1,2,3,4']);

// Staff/BranchAdmin/Admin notifications bell (topbar.php links here for any
// non-plan-holder role; plan holders use client/notification instead, routed
// separately in Routes/client.php). Controllers\Notifications::index() and
// its view were already fully built and role-aware (resolveLayoutView()
// picks admin/branch_admin/staff layout by role_id) but this route was never
// added, so the bell 404'd for every staff/branch admin/admin account.
$routes->get('notifications', 'Notifications::index', ['filter' => 'role:1,2,3']);
$routes->post('notifications/read', 'Notifications::markRead', ['filter' => 'role:1,2,3']);

// Fix Prompts Task 5A: the old four-step Packages module (Create Package/
// Add Item/Set Price Version/Assign to Plan Holder) is replaced by the
// real Plan Builder below as the "Plan builder" nav destination. /packages
// itself now redirects there. Packages::storePackage()/storeItem()/
// storeVersion()/assignToPlan() and their POST routes are left in place
// (not deleted - see PlanBuilder's own docblock) since assignToPlan() is
// "the one place that can assign a package to a plan holder's plan at
// all" and this prompt's brief explicitly excludes a plan-holder-
// assignment UI from the new pages - but as of this redirect, nothing in
// the app links to any of these four POST routes anymore (all lived only
// in packages/index.php's own form, confirmed by grepping every view).
// Flagged to the user rather than silently dropped.
$routes->addRedirect('packages', 'plan-builder', 301);
$routes->post('packages/create', 'Packages::storePackage', ['filter' => 'role:1,2,3']);
$routes->post('packages/add-item', 'Packages::storeItem', ['filter' => 'role:1,2,3']);
$routes->post('packages/add-version', 'Packages::storeVersion', ['filter' => 'role:1,2,3']);
$routes->post('packages/assign-plan', 'Packages::assignToPlan', ['filter' => 'role:1,2,3']);

// Plan Builder (Fix Prompts Task 5A): a plan = one membership_programs
// row + its one entitled package + that package's inclusions. Create/
// edit/toggle are System Admin only; Branch Admin/Staff get read-only
// index/show - see PlanBuilderService.
$routes->get('plan-builder', 'PlanBuilder::index', ['filter' => 'role:1,2,3']);
// Create is System Admin OR Branch Admin now - a Branch Admin submission
// goes in pending (invisible at registration) until System Admin approves
// it below. See PlanBuilderService's docblock.
$routes->get('plan-builder/create', 'PlanBuilder::create', ['filter' => 'role:1,2']);
$routes->post('plan-builder/store', 'PlanBuilder::store', ['filter' => 'role:1,2']);
// AJAX: the create/edit form's package dropdown fetches a package's
// inclusions to preview (read-only - inclusions are edited on Services &
// Packages, not here). role:1,2 matches who can reach the create/edit form.
$routes->get('plan-builder/package-inclusions', 'PlanBuilder::packageInclusions', ['filter' => 'role:1,2']);
// Where a payment will go (plan months / package instalments): preview on the recording screens.
$routes->get('payments/allocation-preview', 'PaymentPreview::allocation', ['filter' => 'auth']);
$routes->get('plan-builder/(:num)', 'PlanBuilder::show/$1', ['filter' => 'role:1,2,3']);
$routes->get('plan-builder/(:num)/edit', 'PlanBuilder::edit/$1', ['filter' => 'role:1']);
$routes->post('plan-builder/(:num)/update', 'PlanBuilder::update/$1', ['filter' => 'role:1']);
$routes->post('plan-builder/(:num)/toggle', 'PlanBuilder::toggle/$1', ['filter' => 'role:1']);
$routes->post('plan-builder/(:num)/delete', 'PlanBuilder::destroy/$1', ['filter' => 'role:1']);
$routes->post('plan-builder/(:num)/approve', 'PlanBuilder::approve/$1', ['filter' => 'role:1']);
$routes->post('plan-builder/(:num)/reject', 'PlanBuilder::reject/$1', ['filter' => 'role:1']);
