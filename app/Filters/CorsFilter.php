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
                }

                header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
                header('Access-Control-Max-Age: 86400'); // 24 hours cache
            }

            // Handle preflight OPTIONS requests
            if ($request->getMethod() === 'options') {
                log_message('debug', 'Handling OPTIONS preflight request');
                header('Content-Length: 0');
                header('Content-Type: text/plain');
                exit(0); // Stop further processing
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
