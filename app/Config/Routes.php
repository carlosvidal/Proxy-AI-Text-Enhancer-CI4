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
 * LLM Proxy Routes
 * --------------------------------------------------------------------
 */

// Endpoint principal para peticiones al proxy
$routes->post('api/llm-proxy', 'LlmProxy::index');
$routes->options('api/llm-proxy', 'LlmProxy::options');

// Endpoint para verificar cuota
$routes->get('api/quota', 'LlmProxy::quota');
$routes->options('api/quota', 'LlmProxy::options');

// Endpoint de instalación (protegido, solo para administradores)
$routes->get('api/llm-proxy/install', 'LlmProxy::install');

// Endpoint de estado del proxy
$routes->get('api/llm-proxy/status', 'LlmProxy::status');

// Endpoint para probar conexión
$routes->get('api/llm-proxy/test-connection', 'LlmProxy::test_connection');

// Rutas para test de CORS
$routes->get('test/cors', 'CorsTest::index');
$routes->options('test/cors', 'CorsTest::options');

// Rutas para el dashboard de uso
$routes->get('usage', 'Usage::index');
$routes->get('usage/logs', 'Usage::logs');
$routes->get('usage/logs/(:num)', 'Usage::logs/$1');
$routes->get('usage/quotas', 'Usage::quotas');
$routes->get('usage/providers', 'Usage::providers');
$routes->get('usage/cache', 'Usage::cache');

// Rutas para el dashboard de tenants
$routes->get('tenants', 'Tenants::index');
$routes->get('tenants/create', 'Tenants::create');
$routes->post('tenants/create', 'Tenants::create');
$routes->get('tenants/edit/(:num)', 'Tenants::edit/$1');
$routes->post('tenants/edit/(:num)', 'Tenants::edit/$1');
$routes->get('tenants/delete/(:num)', 'Tenants::delete/$1');

// Rutas de migración para configuración de base de datos
$routes->get('migrate', 'Migrate::index');
$routes->get('migrate/version/(:num)', 'Migrate::version/$1');
$routes->get('migrate/reset', 'Migrate::reset');
$routes->get('migrate/status', 'Migrate::status');

$routes->get('auth/login', 'Auth::login');
$routes->post('auth/login', 'Auth::login');
$routes->get('auth/logout', 'Auth::logout');
$routes->get('auth/profile', 'Auth::profile');
$routes->post('auth/profile', 'Auth::updateProfile');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
