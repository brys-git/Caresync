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

    // Service balance continuation
    $routes->get('service-balances', 'ServiceBalances::index', ['filter' => 'role:2']);
    $routes->get('service-balances/(:num)', 'ServiceBalances::show/$1', ['filter' => 'role:2']);
    $routes->post('service-balances/pay/(:num)', 'ServiceBalances::pay/$1', ['filter' => 'role:2']);

    // Cash Payment Recording (for client initial payments)
    $routes->get('cash-payment-record', 'BranchAdmin\CashPaymentController::recordPaymentForm', ['filter' => 'role:2']);
    $routes->post('cash-payment-record/save', 'BranchAdmin\CashPaymentController::savePaymentRecord', ['filter' => 'role:2']);
    $routes->get('cash-payments', 'BranchAdmin\CashPaymentController::viewPayments', ['filter' => 'role:2']);

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
    $routes->get('staff-monitoring', 'BranchAdmin\StaffMonitoringController::index', ['filter' => 'role:2']);

    // Reports & Analytics
    $routes->get('reports', 'BranchAdmin\ReportController::index', ['filter' => 'role:2']);
    $routes->get('analytics', 'Analytics::branchAdmin', ['filter' => 'role:2']);

    // Profile Management
    $routes->get('profile', 'BranchAdmin\ProfileController::index', ['filter' => 'role:2']);
    $routes->post('profile/update', 'BranchAdmin\ProfileController::update', ['filter' => 'role:2']);
});
