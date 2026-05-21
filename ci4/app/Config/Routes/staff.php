<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Staff Routes (Role: 3)
 * All routes require 'auth' and 'role:3' filters
 */

$routes->group('staff', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::staff', ['filter' => 'role:3']);

    // Client Management
    $routes->get('client-management', 'Staff\ClientController::index', ['filter' => 'role:3']);
    $routes->get('client', 'Staff\ClientController::index', ['filter' => 'role:3']);
    $routes->get('client/view/(:num)', 'Staff\ClientController::view/$1', ['filter' => 'role:3']);
    $routes->get('client/edit/(:num)', 'Staff\ClientController::edit/$1', ['filter' => 'role:3']);
    $routes->post('client/update/(:num)', 'Staff\ClientController::update/$1', ['filter' => 'role:3']);
    $routes->get('client/register', 'Staff\ClientController::create', ['filter' => 'role:3']);
    $routes->post('client/store', 'Staff\ClientController::store', ['filter' => 'role:3']);

    // Payment Management
    $routes->get('payment-management', 'PaymentTracking::staff', ['filter' => 'role:3']);
    $routes->post('payment-management/record-cash', 'PaymentTracking::recordCash', ['filter' => 'role:3']);

    // Service Management
    $routes->get('services', 'Staff\ServicesController::index', ['filter' => 'role:3']);
    $routes->get('services/requests', 'Staff\ServicesController::serviceRequests', ['filter' => 'role:3']);
    $routes->get('services/ongoing', 'Staff\ServicesController::ongoingServices', ['filter' => 'role:3']);

    // Reports & Analytics
    $routes->get('reports', 'Staff\ReportsController::index', ['filter' => 'role:3']);
    $routes->get('analytics', 'Analytics::staff', ['filter' => 'role:3']);

    // Profile Management
    $routes->get('profile', 'Staff\ProfileController::index', ['filter' => 'role:3']);
    $routes->post('profile/update', 'Staff\ProfileController::update', ['filter' => 'role:3']);
});
