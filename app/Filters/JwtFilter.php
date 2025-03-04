<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtFilter implements FilterInterface
{
    /**
     * Filter requests before controller execution
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('jwt');

        // Get JWT token from request header
        $token = get_jwt_from_header();

        // Check if token exists
        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'error' => [
                        'message' => 'Unauthorized - No token provided'
                    ]
                ]);
        }

        // Validate the token
        $tokenData = validate_jwt($token);

        if (!$tokenData) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'error' => [
                        'message' => 'Unauthorized - Invalid or expired token'
                    ]
                ]);
        }

        // Store token data in request for controller use
        $request->jwtData = $tokenData->data;
    }

    /**
     * We don't have anything to do here.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after controller execution
    }
}
