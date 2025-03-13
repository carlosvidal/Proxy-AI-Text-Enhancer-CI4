<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------
 * Authentication Routes (no auth required)
 * --------------------------------------------------------------------
 */
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/login', 'Auth::attemptLogin');
$routes->get('auth/logout', 'Auth::logout');

/*
 * --------------------------------------------------------------------
 * Protected Routes (requires authentication)
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth'], function($routes) {
    // Common routes
    $routes->get('auth/profile', 'Auth::profile');
    $routes->post('auth/profile', 'Auth::updateProfile');
    
    // Usage Statistics (main dashboard for tenants)
    $routes->get('usage', 'Usage::index');
    $routes->get('usage/logs', 'Usage::logs');
    $routes->get('usage/api', 'Usage::api');
    $routes->get('usage/user/(:num)', 'Usage::userStats/$1');
});

/*
 * --------------------------------------------------------------------
 * Tenant Routes (requires tenant role)
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth:tenant'], function($routes) {
    // Redirect tenant root to usage dashboard
    $routes->get('/', 'Usage::index');
    
    // Buttons Management
    $routes->get('buttons', 'Buttons::index');
    $routes->get('buttons/create', 'Buttons::create');
    $routes->post('buttons/create', 'Buttons::create');
    $routes->get('buttons/edit/(:num)', 'Buttons::edit/$1');
    $routes->post('buttons/edit/(:num)', 'Buttons::edit/$1');
    $routes->get('buttons/delete/(:num)', 'Buttons::delete/$1');
    $routes->get('buttons/view/(:num)', 'Buttons::view/$1');
    
    // API Users Management
    $routes->get('api-users', 'TenantUsers::index');
    $routes->get('api-users/create', 'TenantUsers::create');
    $routes->post('api-users/create', 'TenantUsers::create');
    $routes->get('api-users/edit/(:num)', 'TenantUsers::edit/$1');
    $routes->post('api-users/edit/(:num)', 'TenantUsers::edit/$1');
    $routes->get('api-users/delete/(:num)', 'TenantUsers::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Admin Routes (requires superadmin role)
 * --------------------------------------------------------------------
 */
$routes->group('admin', ['filter' => 'auth:superadmin'], function($routes) {
    // Admin dashboard
    $routes->get('dashboard', 'Admin::dashboard');
    
    // Tenants Management
    $routes->get('tenants', 'Admin::tenants');
    $routes->get('tenants/view/(:segment)', 'Admin::viewTenant/$1');
    $routes->get('tenants/create', 'Admin::createTenant');
    $routes->post('tenants/create', 'Admin::storeTenant');
    $routes->get('tenants/edit/(:segment)', 'Admin::editTenant/$1');
    $routes->post('tenants/edit/(:segment)', 'Admin::updateTenant/$1');
    $routes->get('tenants/delete/(:segment)', 'Admin::deleteTenant/$1');
    
    // Tenant button management
    $routes->get('tenants/(:segment)/buttons', 'Admin::tenantButtons/$1');
    $routes->get('tenants/(:segment)/buttons/create', 'Admin::createButton/$1');
    $routes->post('tenants/(:segment)/buttons/create', 'Admin::storeButton/$1');
    $routes->get('tenants/(:segment)/buttons/(:num)/edit', 'Admin::editButton/$1/$2');
    $routes->post('tenants/(:segment)/buttons/(:num)/edit', 'Admin::updateButton/$1/$2');
    $routes->get('tenants/(:segment)/buttons/(:num)/delete', 'Admin::deleteButton/$1/$2');
    
    // Buttons Management (admin section)
    $routes->get('tenants/(:segment)/buttons', 'Admin::tenantButtons/$1');
    $routes->get('tenants/(:segment)/buttons/create', 'Admin::createButton/$1');
    $routes->post('tenants/(:segment)/buttons/create', 'Admin::storeButton/$1');
    $routes->get('tenants/(:segment)/buttons/(:num)/edit', 'Admin::editButton/$1/$2');
    $routes->post('tenants/(:segment)/buttons/(:num)/edit', 'Admin::updateButton/$1/$2');
    $routes->get('tenants/(:segment)/buttons/(:num)/delete', 'Admin::deleteButton/$1/$2');
    
    // API Users Management (admin section)
    $routes->get('tenants/(:segment)/users', 'Admin::tenantApiUsers/$1');
    $routes->get('tenants/(:segment)/users/create', 'Admin::createApiUser/$1');
    $routes->post('tenants/(:segment)/users/create', 'Admin::storeApiUser/$1');
    $routes->get('tenants/(:segment)/users/(:num)/edit', 'Admin::editApiUser/$1/$2');
    $routes->post('tenants/(:segment)/users/(:num)/edit', 'Admin::updateApiUser/$1/$2');
    $routes->get('tenants/(:segment)/users/(:num)/delete', 'Admin::deleteApiUser/$1/$2');
    $routes->get('tenants/(:segment)/users/(:num)/usage', 'Admin::apiUserUsage/$1/$2');
});

/*
 * --------------------------------------------------------------------
 * API Routes (requires JWT authentication)
 * --------------------------------------------------------------------
 */
$routes->group('api', ['filter' => 'jwt'], function($routes) {
    // LLM Proxy endpoints
    $routes->post('enhance', 'Api::enhance');
    $routes->get('quota', 'Api::quota');
    
    // Button management endpoints
    $routes->get('buttons', 'Api::getButtons');
    $routes->post('buttons', 'Api::createButton');
    $routes->put('buttons/(:num)', 'Api::updateButton/$1');
    $routes->delete('buttons/(:num)', 'Api::deleteButton/$1');
});

// JWT API Authentication routes
$routes->post('api/auth/login', 'Auth::apiLogin');
$routes->post('api/auth/refresh', 'Auth::refreshToken');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
