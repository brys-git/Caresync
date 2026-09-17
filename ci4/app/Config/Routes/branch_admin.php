<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Branch Admin Routes (Role: 2)
 * All routes require 'auth' and 'role:2' filters
 */

$routes->group('branch-admin', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::branchAdmin', ['filter' => 'role:2']);

    // Registration & Approvals
    $routes->get('registration-approvals', 'ClientPortal::registrationApprovals', ['filter' => 'role:2']);

    // Client Management
    $routes->get('client-management', 'BranchAdmin\ClientController::index', ['filter' => 'role:2']);
    $routes->get('client-management/view/(:num)', 'BranchAdmin\ClientController::view/$1', ['filter' => 'role:2']);
    $routes->get('client-management/edit/(:num)', 'BranchAdmin\ClientController::edit/$1', ['filter' => 'role:2']);
    $routes->post('client-management/update/(:num)', 'BranchAdmin\ClientController::update/$1', ['filter' => 'role:2']);
    $routes->post('client-management/approve/(:num)', 'BranchAdmin\ClientController::approve/$1', ['filter' => 'role:2']);

    // Client (Alternative routes)
    $routes->get('client', 'BranchAdmin\ClientController::index', ['filter' => 'role:2']);
    $routes->get('client/view/(:num)', 'BranchAdmin\ClientController::view/$1', ['filter' => 'role:2']);
    $routes->get('client/edit/(:num)', 'BranchAdmin\ClientController::edit/$1', ['filter' => 'role:2']);
    $routes->post('client/update/(:num)', 'BranchAdmin\ClientController::update/$1', ['filter' => 'role:2']);
    $routes->get('client/register', 'BranchAdmin\ClientController::create', ['filter' => 'role:2']);
    $routes->post('client/store', 'BranchAdmin\ClientController::store', ['filter' => 'role:2']);

    // Payment Tracking
    $routes->get('payment-tracking', 'PaymentTracking::branchAdmin', ['filter' => 'role:2']);
    $routes->post('payment-tracking/record-cash', 'PaymentTracking::recordCash', ['filter' => 'role:2']);
    $routes->post('payment-tracking/approve/(:num)', 'PaymentTracking::approveGcash/$1', ['filter' => 'role:2']);
    $routes->post('payment-tracking/reject/(:num)', 'PaymentTracking::rejectGcash/$1', ['filter' => 'role:2']);
    $routes->post('payment-tracking/save-remarks/(:num)', 'PaymentTracking::saveRemarks/$1', ['filter' => 'role:2']);

    // Service balance continuation
    $routes->get('service-balances', 'ServiceBalances::index', ['filter' => 'role:2']);
    $routes->get('service-balances/(:num)', 'ServiceBalances::show/$1', ['filter' => 'role:2']);
    $routes->post('service-balances/pay/(:num)', 'ServiceBalances::pay/$1', ['filter' => 'role:2']);

    // Cash payment recording used to live here (BranchAdmin\CashPaymentController,
    // removed): it wrote to the now-retired cash_payment_records table via
    // free-text client_name with no plan_id, so recorded cash could never
    // advance plans.months_paid. It also had no sidebar link - unreachable
    // UI. The real, working cash/GCash counter-entry flow is
    // PaymentTracking::recordCash() below, linked from the sidebar as
    // "Payment Tracking" - that's the one place that writes cash payments.
    // Old bookmarks to these two URLs now land on that page instead of 404.
    $routes->get('cash-payment-record', static fn () => redirect()->to('/branch-admin/payment-tracking'), ['filter' => 'role:2']);
    $routes->get('cash-payments', static fn () => redirect()->to('/branch-admin/payment-tracking'), ['filter' => 'role:2']);

    // Collection List (who has paid, who hasn't) - every collector in this branch.
    $routes->get('collection-list', 'CollectionListController::branchAdmin', ['filter' => 'role:2']);
    $routes->get('collection-list/print', 'CollectionListController::printBranchAdmin', ['filter' => 'role:2']);
    $routes->post('collection-list/save-remarks/(:num)', 'CollectionListController::saveRemarks/$1', ['filter' => 'role:2']);

    // Collector Assignments - assigns a barangay (or the whole branch) to a
    // collector. Collection List and GCash approval both depend on this.
    $routes->get('collector-assignments', 'BranchAdmin\CollectorAssignmentController::index', ['filter' => 'role:2']);
    $routes->post('collector-assignments/store', 'BranchAdmin\CollectorAssignmentController::store', ['filter' => 'role:2']);
    $routes->post('collector-assignments/delete/(:num)', 'BranchAdmin\CollectorAssignmentController::destroy/$1', ['filter' => 'role:2']);

    // Service Package Management
    $routes->get('service-package', 'BranchAdmin\ServiceOfferController::index', ['filter' => 'role:2']);
    $routes->get('service-package/services', 'BranchAdmin\ServiceOfferController::index', ['filter' => 'role:2']);
    $routes->get('service-package/packages', 'BranchAdmin\PackageController::index', ['filter' => 'role:2']);
    
    // Service Requests & Applications
    $routes->get('service-package/requests', 'BranchAdmin\ServiceApplicationController::index', ['filter' => 'role:2']);
    $routes->get('service-package/requests/(:num)', 'BranchAdmin\ServiceApplicationController::show/$1', ['filter' => 'role:2']);
    $routes->get('service-package/requests/document/(:num)', 'BranchAdmin\ServiceApplicationController::downloadDocument/$1', ['filter' => 'role:2']);
    $routes->post('service-package/requests/approve/(:num)', 'BranchAdmin\ServiceApplicationController::approve/$1', ['filter' => 'role:2']);
    $routes->post('service-package/requests/reject/(:num)', 'BranchAdmin\ServiceApplicationController::reject/$1', ['filter' => 'role:2']);

    // Ongoing Services & Scheduling
    $routes->get('service-package/ongoing', 'BranchAdmin\ServiceController::index', ['filter' => 'role:2']);
    $routes->post('service-package/ongoing/update-status/(:num)', 'BranchAdmin\ServiceController::updateStatus/$1', ['filter' => 'role:2']);
    $routes->get('service-package/schedule', 'BranchAdmin\ServiceController::create', ['filter' => 'role:2']);
    $routes->post('service-package/schedule/store', 'BranchAdmin\ServiceController::store', ['filter' => 'role:2']);

    // Services Management
    $routes->get('services/create', 'BranchAdmin\ServiceOfferController::create', ['filter' => 'role:2']);
    $routes->post('services/store', 'BranchAdmin\ServiceOfferController::store', ['filter' => 'role:2']);
    $routes->get('services/view/(:num)', 'BranchAdmin\ServiceOfferController::view/$1', ['filter' => 'role:2']);
    $routes->get('services/edit/(:num)', 'BranchAdmin\ServiceOfferController::edit/$1', ['filter' => 'role:2']);
    $routes->post('services/update/(:num)', 'BranchAdmin\ServiceOfferController::update/$1', ['filter' => 'role:2']);

    // Packages Management
    $routes->get('packages/create', 'BranchAdmin\PackageController::create', ['filter' => 'role:2']);
    $routes->post('packages/store', 'BranchAdmin\PackageController::store', ['filter' => 'role:2']);
    $routes->get('packages/view/(:num)', 'BranchAdmin\PackageController::view/$1', ['filter' => 'role:2']);
    $routes->get('packages/edit/(:num)', 'BranchAdmin\PackageController::edit/$1', ['filter' => 'role:2']);
    $routes->post('packages/update/(:num)', 'BranchAdmin\PackageController::update/$1', ['filter' => 'role:2']);
    $routes->post('packages/add-item/(:num)', 'BranchAdmin\PackageController::addItem/$1', ['filter' => 'role:2']);

    // Staff & Monitoring
    // assign()/activities()/store() were fully implemented but never routed -
    // only index() (the Staff List tab) was reachable, so the Assign Tasks
    // and Staff Activities nav tabs, and the assign-staff form, all 404'd.
    // Found during the 2026-09-09 system scan.
    $routes->get('staff-monitoring', 'BranchAdmin\StaffMonitoringController::index', ['filter' => 'role:2']);
    $routes->get('staff-monitoring/assign', 'BranchAdmin\StaffMonitoringController::assign', ['filter' => 'role:2']);
    $routes->post('staff-monitoring/store', 'BranchAdmin\StaffMonitoringController::store', ['filter' => 'role:2']);
    $routes->get('staff-monitoring/activities', 'BranchAdmin\StaffMonitoringController::activities', ['filter' => 'role:2']);

    // Staff Management (edit a staff member's email/contact/status) - a
    // complete, working controller that was simply never wired to any
    // route, so the "Edit Staff" button on Staff Monitoring 404'd. Found
    // during the 2026-09-09 system scan.
    $routes->get('staff-management', 'BranchAdmin\StaffManagementController::index', ['filter' => 'role:2']);
    $routes->get('staff-management/edit/(:num)', 'BranchAdmin\StaffManagementController::edit/$1', ['filter' => 'role:2']);
    $routes->post('staff-management/update/(:num)', 'BranchAdmin\StaffManagementController::update/$1', ['filter' => 'role:2']);

    // Reports & Analytics
    // Panel brief section 7: "reports" had no index() to land on - BranchAdmin\
    // ReportController only ever defined remittance()/generate() - so this link
    // in the sidebar 404'd. Landing on remittance (its main report) instead.
    $routes->get('reports', 'BranchAdmin\ReportController::remittance', ['filter' => 'role:2']);
    // Every filter/Print/PDF/CSV button on the remittance page posts here -
    // this route did not exist at all before, so none of them worked.
    $routes->post('reports/remittance/generate', 'BranchAdmin\ReportController::generate', ['filter' => 'role:2']);
    $routes->get('reports/overdue', 'BranchAdmin\ReportController::overdue', ['filter' => 'role:2']);
    $routes->get('reports/commission', 'BranchAdmin\ReportController::commission', ['filter' => 'role:2']);
    $routes->get('analytics', 'Analytics::branchAdmin', ['filter' => 'role:2']);

    // Profile Management
    $routes->get('profile', 'BranchAdmin\ProfileController::index', ['filter' => 'role:2']);
    $routes->post('profile/update', 'BranchAdmin\ProfileController::update', ['filter' => 'role:2']);
});
