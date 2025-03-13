<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Don't check auth for login-related routes
        $uri = $request->uri->getPath();
        if (in_array($uri, ['auth/login', 'auth/attemptLogin'])) {
            return;
        }

        // If not logged in, redirect to login
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        // Check role-based access
        if (!empty($arguments)) {
            $role = session()->get('role');
            if (!in_array($role, $arguments)) {
                // If superadmin, redirect to admin dashboard
                if ($role === 'superadmin') {
                    return redirect()->to('/admin/dashboard');
                }
                // If tenant, redirect to buttons page
                if ($role === 'tenant') {
                    return redirect()->to('/buttons');
                }
                // If no valid role, logout
                session()->destroy();
                return redirect()->to('/auth/login')
                    ->with('error', 'Invalid role. Please login again.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after
    }
}
