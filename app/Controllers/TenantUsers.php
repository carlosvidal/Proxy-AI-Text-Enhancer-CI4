<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\TenantsModel;
use App\Models\TenantUsersModel;

/**
 * TenantUsers Controller
 * 
 * This controller manages API users (not system authentication users).
 * API users are used only for tracking API consumption and quotas.
 * They do not have passwords and email is optional.
 * 
 * Key differences from system users:
 * - API users are stored in tenant_users table
 * - No password authentication (API uses separate JWT auth)
 * - Email is optional
 * - Multiple API users per tenant
 * - Used only to track API usage and quotas
 */
class TenantUsers extends Controller
{
    protected $tenantsModel;
    protected $tenantUsersModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->tenantsModel = new TenantsModel();
        $this->tenantUsersModel = new TenantUsersModel();
    }

    /**
     * List all API users for the current tenant
     */
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/auth/login')
                ->with('error', 'No tenant found');
        }

        // Get tenant information
        $tenant = $this->tenantsModel->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        if (!$tenant) {
            return redirect()->to('/auth/login')
                ->with('error', 'Tenant not found');
        }

        // Get API users for this tenant
        $users = $this->tenantUsersModel->where('tenant_id', $tenant_id)->findAll();

        // Get usage statistics for each API user
        $db = \Config\Database::connect();
        foreach ($users as &$user) {
            $usage = $db->query("
                SELECT COALESCE(SUM(tokens), 0) as total_tokens
                FROM usage_logs
                WHERE tenant_id = ? AND api_user_id = ?
            ", [$tenant_id, $user['user_id']])->getRowArray();

            $user['usage'] = $usage['total_tokens'] ?? 0;
        }

        $data = [
            'title' => 'API Users',
            'tenant' => $tenant,
            'users' => $users
        ];

        return view('tenants/users', $data);
    }

    /**
     * Create a new API user for the current tenant
     */
    public function create()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/auth/login')
                ->with('error', 'No tenant found');
        }

        // Get tenant information
        $tenant = $this->tenantsModel->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        if (!$tenant) {
            return redirect()->to('/auth/login')
                ->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'user_id' => 'required|min_length[3]|max_length[50]|is_unique[tenant_users.user_id]',
                'name' => 'required|min_length[3]|max_length[255]',
                'email' => 'permit_empty|valid_email',
                'quota' => 'required|numeric|greater_than[0]'
            ];

            if ($this->validate($rules)) {
                $userData = [
                    'tenant_id' => $tenant_id,
                    'user_id' => $this->request->getPost('user_id'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantUsersModel->insert($userData)) {
                    return redirect()->to('api-users')
                        ->with('success', 'API user created successfully');
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Error creating API user');
            }

            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Create API User',
            'tenant' => $tenant
        ];

        return view('tenants/add_user', $data);
    }

    /**
     * Edit an existing API user
     * 
     * @param int $id The ID of the API user to edit
     */
    public function edit($id)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/auth/login')
                ->with('error', 'No tenant found');
        }

        // Get tenant information
        $tenant = $this->tenantsModel->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        if (!$tenant) {
            return redirect()->to('/auth/login')
                ->with('error', 'Tenant not found');
        }

        // Get API user information
        $user = $this->tenantUsersModel->where('id', $id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$user) {
            return redirect()->to('api-users')
                ->with('error', 'API user not found');
        }

        // Get usage statistics
        $db = \Config\Database::connect();
        $usage = $db->query("
            SELECT COALESCE(SUM(tokens), 0) as total_tokens
            FROM usage_logs
            WHERE tenant_id = ? AND api_user_id = ?
        ", [$tenant_id, $user['user_id']])->getRowArray();

        $user['usage'] = $usage['total_tokens'] ?? 0;

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'email' => 'permit_empty|valid_email',
                'quota' => 'required|numeric|greater_than[0]',
                'active' => 'required|in_list[0,1]'
            ];

            if ($this->validate($rules)) {
                $updateData = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantUsersModel->update($id, $updateData)) {
                    return redirect()->to('api-users')
                        ->with('success', 'API user updated successfully');
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Error updating API user');
            }

            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Edit API User',
            'tenant' => $tenant,
            'user' => $user
        ];

        return view('tenants/edit_user', $data);
    }

    /**
     * Delete an API user
     * 
     * @param int $id The ID of the API user to delete
     */
    public function delete($id)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/auth/login')
                ->with('error', 'No tenant found');
        }

        // Get API user information
        $user = $this->tenantUsersModel->where('id', $id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$user) {
            return redirect()->to('api-users')
                ->with('error', 'API user not found');
        }

        if ($this->tenantUsersModel->delete($id)) {
            return redirect()->to('api-users')
                ->with('success', 'API user deleted successfully');
        }

        return redirect()->to('api-users')
            ->with('error', 'Error deleting API user');
    }
}
