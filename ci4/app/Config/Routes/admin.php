<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * System Admin Routes (Role: 1)
 * All routes require 'auth' and 'role:1' filters
 */

$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::admin', ['filter' => 'role:1']);

    // Registration & Approvals
    $routes->get('registration-approvals', 'ClientPortal::registrationApprovals', ['filter' => 'role:1']);

    // Branch Management
    $routes->get('branch-management', 'Admin\BranchManagementController::index', ['filter' => 'role:1']);
    $routes->get('branch-management/create', 'Admin\BranchManagementController::create', ['filter' => 'role:1']);
    $routes->post('branch-management/store', 'Admin\BranchManagementController::store', ['filter' => 'role:1']);
    $routes->get('branch-management/edit/(:num)', 'Admin\BranchManagementController::edit/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/update/(:num)', 'Admin\BranchManagementController::update/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/toggle-status/(:num)', 'Admin\BranchManagementController::toggleStatus/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/toggle-availability', 'Admin\BranchManagementController::toggleAvailability', ['filter' => 'role:1']);
    $routes->get('branch-management/export-transactions', 'Admin\BranchManagementController::exportTransactions', ['filter' => 'role:1']);
    $routes->post('branch-management/approval/service/approve/(:num)', 'Admin\BranchManagementController::approveService/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/approval/service/reject/(:num)', 'Admin\BranchManagementController::rejectService/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/approval/package/approve/(:num)', 'Admin\BranchManagementController::approvePackage/$1', ['filter' => 'role:1']);
    $routes->post('branch-management/approval/package/reject/(:num)', 'Admin\BranchManagementController::rejectPackage/$1', ['filter' => 'role:1']);

    // Client Management
    $routes->get('client-management', 'Admin\ClientManagementController::index', ['filter' => 'role:1']);
    $routes->get('client-management/view/(:num)', 'ClientDetailController::show/$1', ['filter' => 'role:1']);
    $routes->get('client-management/statement/(:num)', 'ClientDetailController::statement/$1', ['filter' => 'role:1']);
    $routes->get('client-management/ledger/(:num)', 'ClientDetailController::ledger/$1', ['filter' => 'role:1']);
    $routes->post('client-management/note/(:num)', 'ClientDetailController::saveNote/$1', ['filter' => 'role:1']);
    $routes->get('client-management/file/(:num)/(:segment)/(:num)', 'ClientDetailController::file/$1/$2/$3', ['filter' => 'role:1']);
    $routes->get('client-management/edit/(:num)', 'Admin\ClientManagementController::edit/$1', ['filter' => 'role:1']);
    $routes->post('client-management/update/(:num)', 'Admin\ClientManagementController::update/$1', ['filter' => 'role:1']);
    $routes->get('client/register', 'Admin\ClientManagementController::create', ['filter' => 'role:1']);
    $routes->post('client/store', 'Admin\ClientManagementController::store', ['filter' => 'role:1']);

    // Users: one list of every account (System Admin)
    $routes->get('users', 'Admin\UsersController::index', ['filter' => 'role:1']);
    $routes->get('users/create', 'Admin\UsersController::create', ['filter' => 'role:1']);
    $routes->post('users/create', 'Admin\UsersController::store', ['filter' => 'role:1']);
    $routes->get('users/(:num)', 'Admin\UsersController::show/$1', ['filter' => 'role:1']);
    $routes->get('users/(:num)/edit', 'Admin\UsersController::edit/$1', ['filter' => 'role:1']);
    $routes->post('users/(:num)/edit', 'Admin\UsersController::update/$1', ['filter' => 'role:1']);
    $routes->post('users/(:num)/toggle-status', 'Admin\UsersController::toggleStatus/$1', ['filter' => 'role:1']);
    $routes->post('users/(:num)/reset-password', 'Admin\UsersController::resetPassword/$1', ['filter' => 'role:1']);

    // The old Create Account page is now Users > Add user; old links and bookmarks still work.
    $routes->get('create-account', static fn () => redirect()->to('/admin/users/create', 301), ['filter' => 'role:1']);

    $routes->post('client-management/resend-login/(:num)', 'AccountLoginDetails::resend/$1', ['filter' => 'role:1']);
    $routes->post('client-management/reinstate/(:num)', 'PlanReinstatement::record/$1', ['filter' => 'role:1']);

    // Payment Monitoring
    $routes->get('payment-monitoring', 'PaymentTracking::admin', ['filter' => 'role:1']);
    $routes->get('payment-monitoring/export', 'PaymentTracking::exportCsv', ['filter' => 'role:1']);

    // Reports & Analytics (panel brief section 7)
    $routes->get('reports', 'Reports::index', ['filter' => 'role:1']);
    $routes->get('reports/overdue', 'Reports::overdue', ['filter' => 'role:1']);
    $routes->get('reports/ledger', 'Reports::ledger', ['filter' => 'role:1']);
    $routes->get('reports/avails', 'Reports::avails', ['filter' => 'role:1']);
    $routes->get('reports/collections', 'Reports::collections', ['filter' => 'role:1']);
    $routes->get('reports/commission', 'Reports::commission', ['filter' => 'role:1']);
    $routes->get('reports/remittance', 'Reports::remittance', ['filter' => 'role:1']);
    $routes->get('reports/monthly-summary', 'Reports::monthlySummary', ['filter' => 'role:1']);
    // The old Analytics page is part of the Dashboard now; old links and bookmarks still work.
    $routes->get('analytics', static fn () => redirect()->to('/dashboard/admin', 301), ['filter' => 'role:1']);

    // One-time credentials files from bulk client imports (System Admin only)
    $routes->get('imports/credentials', 'Admin\ImportCredentialsController::index', ['filter' => 'role:1']);
    $routes->get('imports/credentials/(:segment)', 'Admin\ImportCredentialsController::show/$1', ['filter' => 'role:1']);
    $routes->get('imports/credentials/(:segment)/download', 'Admin\ImportCredentialsController::download/$1', ['filter' => 'role:1']);
    $routes->post('imports/credentials/(:segment)/delete', 'Admin\ImportCredentialsController::delete/$1', ['filter' => 'role:1']);

    // Packages & Services Management (packages, services, add-ons)
    $routes->get('catalog', 'Admin\CatalogController::index', ['filter' => 'role:1']);
    $routes->get('catalog/new/(:segment)', 'Admin\CatalogController::create/$1', ['filter' => 'role:1']);
    $routes->get('catalog/edit/(:segment)/(:num)', 'Admin\CatalogController::edit/$1/$2', ['filter' => 'role:1']);
    $routes->post('catalog/save/(:segment)', 'Admin\CatalogController::save/$1', ['filter' => 'role:1']);
    $routes->post('catalog/save/(:segment)/(:num)', 'Admin\CatalogController::save/$1/$2', ['filter' => 'role:1']);
    $routes->post('catalog/availability/(:segment)/(:num)', 'Admin\CatalogController::setAvailability/$1/$2', ['filter' => 'role:1']);

    // Service Offer Management
    $routes->get('service-offer', 'Admin\ServiceOfferController::index', ['filter' => 'role:1']);
    $routes->post('service-offer/package/store', 'Admin\ServiceOfferController::storePackage', ['filter' => 'role:1']);
    $routes->post('service-offer/service/store', 'Admin\ServiceOfferController::storeService', ['filter' => 'role:1']);
    $routes->post('service-offer/approval/service/approve/(:num)', 'Admin\ServiceOfferController::approveService/$1', ['filter' => 'role:1']);
    $routes->post('service-offer/approval/service/reject/(:num)', 'Admin\ServiceOfferController::rejectService/$1', ['filter' => 'role:1']);
    $routes->post('service-offer/approval/package/approve/(:num)', 'Admin\ServiceOfferController::approvePackage/$1', ['filter' => 'role:1']);
    $routes->post('service-offer/approval/package/reject/(:num)', 'Admin\ServiceOfferController::rejectPackage/$1', ['filter' => 'role:1']);

    // Profile Management
    $routes->get('profile', 'Admin\ProfileController::index', ['filter' => 'role:1']);
    $routes->get('profile/edit', 'Admin\ProfileController::edit', ['filter' => 'role:1']);
    $routes->post('profile/update', 'Admin\ProfileController::update', ['filter' => 'role:1']);
    $routes->post('profile/change-password', 'Admin\ProfileController::changePassword', ['filter' => 'role:1']);
});
