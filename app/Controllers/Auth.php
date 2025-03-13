<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UsersModel;
use App\Models\TenantsModel;
use App\Models\TenantUsersModel;

class Auth extends Controller
{
    protected $usersModel;
    protected $tenantsModel;
    protected $tenantUsersModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->usersModel = new UsersModel();
        $this->tenantsModel = new TenantsModel();
        $this->tenantUsersModel = new TenantUsersModel();
    }

    public function login()
    {
        // If already logged in, redirect based on role
        if (session()->get('isLoggedIn')) {
            if (session()->get('role') === 'superadmin') {
                return redirect()->to('/admin/dashboard');
            }
            return redirect()->to('/buttons');
        }

        return view('auth/login', [
            'title' => 'Login'
        ]);
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[3]|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $username = trim($this->request->getPost('username'));
        $password = trim($this->request->getPost('password'));

        // Debug logging
        log_message('debug', '=== Login Attempt Debug ===');
        log_message('debug', 'Username/Email: ' . $username);
        log_message('debug', 'Password length: ' . strlen($password));

        // First try to find by username
        $user = $this->usersModel->where('username', $username)->first();
        
        // If not found by username, try email
        if (!$user) {
            $user = $this->usersModel->where('email', $username)->first();
        }

        // Debug logging
        if ($user) {
            log_message('debug', 'User found: ' . json_encode([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'active' => $user['active']
            ]));
        } else {
            log_message('debug', 'User not found');
        }

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Usuario no encontrado');
        }

        if (!$user['active']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'La cuenta está inactiva');
        }

        // Debug password verification
        log_message('debug', 'Password verification:');
        log_message('debug', 'Stored hash length: ' . strlen($user['password']));
        log_message('debug', 'Raw verification result: ' . var_export(password_verify($password, $user['password']), true));

        // Verify password using PHP's built-in function
        if (!password_verify($password, $user['password'])) {
            log_message('error', 'Failed login attempt for user: ' . $username);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Usuario o contraseña incorrectos');
        }

        // Add debug logging
        log_message('debug', 'Login attempt for user: ' . $username);
        log_message('debug', 'Password verification result: ' . (password_verify($password, $user['password']) ? 'true' : 'false'));
        log_message('debug', 'Stored hash: ' . $user['password']);
        log_message('debug', 'Provided password: ' . $password);

        // Update last login
        $this->usersModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s')
        ]);

        // Set basic session data
        $sessionData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'isLoggedIn' => true
        ];

        // If user is a tenant, get their tenant information
        if ($user['role'] === 'tenant') {
            // Get tenant information using tenant_id from users table
            $tenant = null;
            if (!empty($user['tenant_id'])) {
                $tenant = $this->tenantsModel->where('tenant_id', $user['tenant_id'])
                    ->where('active', 1)
                    ->first();
            }

            // If no tenant exists, create a demo tenant
            if (!$tenant) {
                $tenant_id = 'demo_' . bin2hex(random_bytes(4));
                $tenant = [
                    'tenant_id' => $tenant_id,
                    'name' => 'Demo Tenant',
                    'email' => $user['email'],
                    'quota' => 1000,
                    'active' => 1,
                    'subscription_status' => 'trial',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Insert tenant
                $this->tenantsModel->insert($tenant);

                // Update user's tenant_id
                $this->usersModel->update($user['id'], ['tenant_id' => $tenant_id]);

                // Associate user with tenant
                $this->tenantUsersModel->insert([
                    'tenant_id' => $tenant_id,
                    'user_id' => $user['id'],
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // Refresh tenant data
                $tenant = $this->tenantsModel->where('tenant_id', $tenant_id)->first();
            }

            // Add tenant data to session
            $sessionData['tenant_id'] = $tenant['tenant_id'];
            $sessionData['tenant_name'] = $tenant['name'];
        }

        // Set session data
        session()->set($sessionData);

        // Redirect based on role
        if ($user['role'] === 'superadmin') {
            return redirect()->to('/admin/dashboard');
        }

        // For tenant users, redirect to buttons page
        return redirect()->to('/buttons');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')
            ->with('message', 'Has cerrado sesión correctamente');
    }

    public function profile()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'title' => 'Mi Perfil',
            'user' => $this->usersModel->find(session()->get('id'))
        ];

        // If tenant user, get tenant information
        if (session()->get('role') === 'tenant') {
            $tenant = $this->tenantsModel->where('tenant_id', session()->get('tenant_id'))
                ->where('active', 1)
                ->first();
            $data['tenant'] = $tenant;
        }

        return view('auth/profile', $data);
    }

    public function updateProfile()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'email' => 'required|valid_email',
            'current_password' => 'permit_empty|min_length[3]',
            'new_password' => 'permit_empty|min_length[3]',
            'confirm_password' => 'matches[new_password]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $id = session()->get('id');
        $user = $this->usersModel->find($id);

        $data = [
            'email' => $this->request->getPost('email')
        ];

        // Handle password change if requested
        $current_password = $this->request->getPost('current_password');
        $new_password = $this->request->getPost('new_password');

        if (!empty($current_password) && !empty($new_password)) {
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                return redirect()->back()
                    ->with('error', 'La contraseña actual es incorrecta');
            }
            $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        if ($this->usersModel->update($id, $data)) {
            // Update session email if it was changed
            session()->set('email', $data['email']);

            return redirect()->back()
                ->with('success', 'Perfil actualizado correctamente');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al actualizar el perfil');
    }
}
