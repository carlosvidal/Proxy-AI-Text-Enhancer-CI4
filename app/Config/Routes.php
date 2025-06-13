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
 * Language Routes
 * --------------------------------------------------------------------
 */
$routes->get('language/(:segment)', 'LanguageController::setLanguage/$1');

/*
 * --------------------------------------------------------------------
 * Authentication Routes (no auth required)
 * --------------------------------------------------------------------
 */
$routes->match(['get', 'post'], 'auth/login', 'Auth::login');
$routes->get('auth/logout', 'Auth::logout');

/*
 * --------------------------------------------------------------------
 * Protected Routes (requires authentication)
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth'], function ($routes) {
    // Common routes
    $routes->get('auth/profile', 'Auth::profile');
    $routes->post('auth/profile', 'Auth::updateProfile');

    // Usage Statistics (main dashboard for tenants)
    $routes->get('usage', 'Usage::index');
    $routes->get('usage/logs', 'Usage::logs');
    $routes->get('usage/api', 'Usage::api');

    // Domain Management Routes
    $routes->get('domains', 'Domains::index');
    $routes->get('domains/create', 'Domains::create');
    $routes->post('domains/store', 'Domains::store');
    $routes->get('domains/verify/(:segment)', 'Domains::verify/$1');
    $routes->get('domains/delete/(:segment)', 'Domains::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Tenant Routes (requires tenant role)
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth:tenant'], function ($routes) {
    // Redirect tenant root to usage dashboard
    $routes->get('/', 'Usage::index');

    // Buttons Management
    $routes->get('buttons', 'Buttons::index');
    $routes->get('buttons/create', 'Buttons::create');
    $routes->post('buttons/store', 'Buttons::store');
    $routes->get('buttons/edit/(:segment)', 'Buttons::edit/$1');
    $routes->post('buttons/update/(:segment)', 'Buttons::update/$1');
    $routes->get('buttons/delete/(:segment)', 'Buttons::delete/$1');
    $routes->get('buttons/view/(:segment)', 'Buttons::view/$1');

    // API Users Management
    $routes->get('api-users', 'ApiUsers::index');
    $routes->get('api-users/create', 'ApiUsers::create');
    $routes->post('api-users/store', 'ApiUsers::store');
    $routes->get('api-users/edit/(:segment)', 'ApiUsers::edit/$1');
    $routes->post('api-users/update/(:segment)', 'ApiUsers::update/$1');
    $routes->get('api-users/delete/(:segment)', 'ApiUsers::delete/$1');
    $routes->get('api-users/view/(:segment)', 'ApiUsers::view/$1');

    // API Keys Management
    $routes->get('api-keys', 'ApiKeys::index');
    $routes->get('api-keys/create', 'ApiKeys::create');
    $routes->post('api-keys/store', 'ApiKeys::store');
    $routes->get('api-keys/set-default/(:segment)', 'ApiKeys::setDefault/$1');
    $routes->get('api-keys/delete/(:segment)', 'ApiKeys::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Admin Routes (requires superadmin role)
 * --------------------------------------------------------------------
 */
$routes->group('admin', ['filter' => 'auth:superadmin'], function ($routes) {
    // Admin dashboard
    $routes->get('dashboard', 'Admin::dashboard');

    // Tenants Management
    $routes->get('tenants', 'Admin::tenants');
    $routes->get('tenants/view/(:segment)', 'Admin::viewTenant/$1');
    // API Keys Management (admin)
    $routes->get('tenants/(:segment)/api_keys', 'Admin::tenantApiKeys/$1');
    $routes->get('tenants/(:segment)/api_keys/add', 'Admin::addTenantApiKey/$1');
    $routes->post('tenants/(:segment)/api_keys/add', 'Admin::storeTenantApiKey/$1');
    $routes->post('tenants/(:segment)/update_plan', 'Admin::updateTenantPlan/$1');
    $routes->get('tenants/create', 'Admin::createTenant');
    $routes->post('tenants/store', 'Admin::storeTenant');
    $routes->get('tenants/edit/(:segment)', 'Admin::editTenant/$1');
    $routes->post('tenants/update/(:segment)', 'Admin::updateTenant/$1');
    $routes->get('tenants/delete/(:segment)', 'Admin::deleteTenant/$1');

    // API Users Management (admin section)
    $routes->get('tenants/(:segment)/users', 'Admin::tenantApiUsers/$1');
    $routes->get('tenants/(:segment)/users/create', 'Admin::createApiUser/$1');
    $routes->post('tenants/(:segment)/users/store', 'Admin::storeApiUser/$1');
    $routes->get('tenants/(:segment)/users/(:segment)/edit', 'Admin::editApiUser/$1/$2');
    $routes->post('tenants/(:segment)/users/(:segment)/update', 'Admin::updateApiUser/$1/$2');
    $routes->get('tenants/(:segment)/users/(:segment)/delete', 'Admin::deleteApiUser/$1/$2');
    $routes->get('tenants/(:segment)/users/(:segment)/usage', 'Admin::apiUserUsage/$1/$2');

    // Tenant button management
    $routes->get('tenants/(:segment)/buttons', 'Admin::tenantButtons/$1');
    $routes->get('tenants/(:segment)/buttons/create', 'Admin::createButton/$1');
    $routes->post('tenants/(:segment)/buttons/store', 'Admin::storeButton/$1');
    $routes->get('tenants/(:segment)/buttons/(:segment)/edit', 'Admin::editButton/$1/$2');
    $routes->post('tenants/(:segment)/buttons/(:segment)/update', 'Admin::updateButton/$1/$2');
    $routes->get('tenants/(:segment)/buttons/(:segment)/delete', 'Admin::deleteButton/$1/$2');

    // Admin Domain Management Routes
    $routes->get('tenants/(:segment)/domains', 'Domains::manageTenantDomains/$1');
    $routes->post('tenants/(:segment)/domains/max', 'Domains::updateMaxDomains/$1');

    // Rutas de usuarios de autenticación
    $routes->get('users', 'AdminUsers::index');
    $routes->get('users/create', 'AdminUsers::create');
    $routes->post('users/store', 'AdminUsers::store');
    $routes->get('users/edit/(:num)', 'AdminUsers::edit/$1');
    $routes->post('users/update/(:num)', 'AdminUsers::update/$1');
    $routes->get('users/view/(:num)', 'AdminUsers::view/$1');
    $routes->get('users/delete/(:num)', 'AdminUsers::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Migration Routes (protected, admin only)
 * --------------------------------------------------------------------
 */
$routes->group('', ['filter' => 'auth:superadmin'], function ($routes) {
    $routes->get('migrate', 'Migrate::index');
    $routes->get('migrate/version/(:num)', 'Migrate::version/$1');
    $routes->get('migrate/reset', 'Migrate::reset');
    $routes->get('migrate/status', 'Migrate::status');
});

/*
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */
$routes->group('api', function ($routes) {
    // System Endpoints
    $routes->get('llm-proxy/install', 'LlmProxy::install');
    $routes->get('llm-proxy/status', 'LlmProxy::status');
    $routes->get('llm-proxy/test-connection', 'LlmProxy::test_connection');

    // Main proxy endpoints
    $routes->post('llm-proxy', 'LlmProxy::index');
    $routes->post('llm-proxy/secure', 'LlmProxy::index', ['filter' => 'jwt']);
    $routes->options('llm-proxy', 'LlmProxy::options');
    $routes->options('llm-proxy/secure', 'LlmProxy::options');

    // Quota Management
    $routes->get('quota', 'LlmProxy::quota');
    $routes->get('quota/secure', 'LlmProxy::quota', ['filter' => 'jwt']);
    $routes->options('quota', 'LlmProxy::options');
    $routes->options('quota/secure', 'LlmProxy::options');
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
