<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        // Get role and tenant info from session
        $role = session()->get('role');
        $tenant_id = session()->get('tenant_id');

        // Redirect based on role
        if ($role === 'superadmin') {
            return redirect()->to('/admin/dashboard');
        } else if ($role === 'tenant' && $tenant_id) {
            return redirect()->to('/usage');
        } else {
            // Something is wrong with the session, log out
            session()->destroy();
            return redirect()->to('/auth/login')
                ->with('error', 'Invalid session state. Please login again.');
        }
    }
}
