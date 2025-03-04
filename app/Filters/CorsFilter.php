<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get allowed origins from environment
        $allowed_origins_str = env('ALLOWED_ORIGINS', '');

        // Check if wildcard is set
        if ($allowed_origins_str === '*') {
            $allowed_origins = '*';
        } else {
            // Parse comma-separated list
            $allowed_origins = array_map('trim', explode(',', $allowed_origins_str));
        }

        $origin = $request->getHeaderLine('Origin');

        // Set appropriate CORS headers based on origin
        if (!empty($origin)) {
            if ($allowed_origins === '*') {
                header('Access-Control-Allow-Origin: *');
            } elseif (in_array($origin, $allowed_origins)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
            }

            header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
        }

        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'options') {
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            header('HTTP/1.1 204 No Content');
            exit;
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // We'll skip adding headers here as they should be set in the before method
        return $response;
    }
}
