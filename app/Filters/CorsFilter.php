<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('logger');
        // Get allowed origins from environment
        $allowed_origins_str = env('ALLOWED_ORIGINS', '*');

        // Log CORS configuration for debugging
        log_message('debug', 'CORS configuration: ALLOWED_ORIGINS=' . $allowed_origins_str);

        // Set appropriate CORS headers
        $origin = $request->getHeaderLine('Origin');

        // Log the requesting origin
        log_message('debug', 'Request origin: ' . ($origin ?: 'none'));

        // If we have a requesting origin and it's not empty
        if (!empty($origin)) {
            // Check if wildcard is set or if the origin is in the allowed list
            if ($allowed_origins_str === '*') {
                header('Access-Control-Allow-Origin: *');
                log_debug('CORS header set: Access-Control-Allow-Origin: *', '');
            } else {
                // Parse comma-separated list
                $allowed_origins = array_map('trim', explode(',', $allowed_origins_str));
                log_debug('CORS allowed origins: ' . implode(', ', $allowed_origins), '');

                if (in_array($origin, $allowed_origins)) {
                    header("Access-Control-Allow-Origin: {$origin}");
                    header('Access-Control-Allow-Credentials: true');
                    log_debug('CORS header set: Access-Control-Allow-Origin: ' . $origin, '');
                } else {
                    log_debug('Origin not allowed: ' . $origin, '');
                    return;
                }
            }

            // Get current route
            $uri = $request->getUri();
            $path = $uri->getPath();

            // Set basic CORS headers
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

            // Route-specific CORS settings
            if (strpos($path, 'api/llm-proxy') === 0) {
                if ($request->getMethod() === 'options') {
                    // LLM proxy routes - higher cache for preflight
                    header('Access-Control-Max-Age: 86400'); // 24 hours
                    header('Cache-Control: public, max-age=86400');
                } else {
                    // No caching for actual proxy requests
                    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                    header('Pragma: no-cache');
                }
                
                if (strpos($path, '/secure') !== false) {
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
                }
            } else if (strpos($path, 'api/quota') === 0) {
                if ($request->getMethod() === 'options') {
                    // Quota routes - shorter cache for preflight
                    header('Access-Control-Max-Age: 3600'); // 1 hour
                    header('Cache-Control: public, max-age=3600');
                } else {
                    // Short cache for quota checks
                    header('Cache-Control: private, must-revalidate, max-age=60'); // 1 minute
                }
                
                if (strpos($path, '/secure') !== false) {
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
                }
            } else {
                if ($request->getMethod() === 'options') {
                    // Default routes - moderate cache for preflight
                    header('Access-Control-Max-Age: 7200'); // 2 hours
                    header('Cache-Control: public, max-age=7200');
                } else {
                    // Default cache policy
                    header('Cache-Control: private, must-revalidate, max-age=300'); // 5 minutes
                }
            }

            // Add Vary header for proper caching
            header('Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers');

            // Handle preflight OPTIONS requests
            if ($request->getMethod() === 'options') {
                log_message('debug', 'Handling OPTIONS preflight request for path: ' . $path);
                header('Content-Length: 0');
                header('Content-Type: text/plain');
                exit(0);
            }

            return $request;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
        return $response;
    }
}
