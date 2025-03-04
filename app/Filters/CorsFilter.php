<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowed_origins_str = env('ALLOWED_ORIGINS', 'http://127.0.0.1:5500,http://localhost:5500');
        $allowed_origins = $allowed_origins_str === '*' ? '*' : explode(',', $allowed_origins_str);

        $origin = $request->getHeaderLine('Origin');

        // Si la solicitud no tiene origen (ejemplo: Postman), permitir todas
        if (empty($origin) || $allowed_origins === '*') {
            header('Access-Control-Allow-Origin: *');
        } elseif (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($request->getMethod() === 'options') {
            header('HTTP/1.1 204 No Content');
            exit();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Asegurar que los headers CORS estén en todas las respuestas
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
