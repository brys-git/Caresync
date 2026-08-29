<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Collector Routes (Role: 5)
 * All routes require 'auth' and 'role:5' filters.
 *
 * Phase 1 scope: just enough for a Collector account to log in and land on a
 * real dashboard. Collection-entry, remittance-report, and commission screens
 * are built in the phase that reworks payments (see the panel brief's own
 * suggested order — roles first, Collector-specific features once the
 * payment flow they depend on is reworked).
 */

$routes->group('collector', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::collector', ['filter' => 'role:5']);
});
