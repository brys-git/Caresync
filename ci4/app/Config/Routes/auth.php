<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Authentication Routes
 * No role filtering required - accessible to all
 */

$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->get('signin', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->get('change-password', 'Auth::changePassword', ['filter' => 'auth']);
$routes->post('change-password', 'Auth::updatePassword', ['filter' => 'auth']);
$routes->get('unauthorized', 'Home::unauthorized', ['filter' => 'auth']);
$routes->get('signup', 'Auth::register');
