<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Si el usuario no está logueado, redirigir al login
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        // Si se requiere un rol específico y el usuario no lo tiene
        if (!empty($arguments) && !in_array(session()->get('role'), $arguments)) {
            return redirect()->to('usage')
                ->with('error', 'You do not have permission to access that page');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No hacer nada después
    }
}
