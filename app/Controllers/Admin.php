<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TenantsModel;
use App\Models\UsersModel;
use App\Models\ButtonsModel;
use App\Models\UsageLogsModel;
use App\Models\TenantUsersModel;

class Admin extends BaseController
{
    protected $tenantsModel;
    protected $usersModel;
    protected $buttonsModel;
    protected $usageLogsModel;
    protected $tenantUsersModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->tenantsModel = new TenantsModel();
        $this->usersModel = new UsersModel();
        $this->buttonsModel = new ButtonsModel();
        $this->usageLogsModel = new UsageLogsModel();
        $this->tenantUsersModel = new TenantUsersModel();
    }

    public function index()
    {
        return redirect()->to('admin/dashboard');
    }

    public function dashboard()
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get statistics
        $stats = [
            'total_tenants' => $this->tenantsModel->countAll(),
            'active_tenants' => $this->tenantsModel->where('active', 1)->countAllResults(),
            'total_users' => $this->usersModel->countAll(),
            'total_api_users' => $this->tenantUsersModel->countAll(),
            'total_buttons' => $this->buttonsModel->countAll(),
            'total_requests' => $this->usageLogsModel->countAll(),
        ];

        // Get recent tenants with their usage stats
        $recentTenants = $this->tenantsModel->orderBy('created_at', 'DESC')->findAll(5);
        foreach ($recentTenants as &$tenant) {
            // Get API user count
            $tenant['api_users'] = $this->tenantUsersModel->where('tenant_id', $tenant['tenant_id'])->countAllResults();
            
            // Get usage statistics
            $tenant['total_buttons'] = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])->countAllResults();
            $tenant['total_requests'] = $this->usageLogsModel->where('tenant_id', $tenant['tenant_id'])->countAllResults();
            $tenant['total_tokens'] = $this->usageLogsModel->where('tenant_id', $tenant['tenant_id'])->selectSum('tokens')->get()->getRow()->tokens ?? 0;
            
            // Set subscription status
            $tenant['subscription_status'] = ucfirst($tenant['subscription_status'] ?? 'trial');
        }

        // Get usage by subscription status
        $db = \Config\Database::connect();
        $usageByStatus = $db->query("
            SELECT 
                t.subscription_status,
                COUNT(DISTINCT t.tenant_id) as tenant_count,
                COUNT(DISTINCT ul.id) as total_requests,
                COALESCE(SUM(ul.tokens), 0) as total_tokens
            FROM tenants t
            LEFT JOIN usage_logs ul ON t.tenant_id = ul.tenant_id
            GROUP BY t.subscription_status
            ORDER BY tenant_count DESC
        ")->getResultArray();

        $data = [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'usageByStatus' => $usageByStatus
        ];

        return view('admin/dashboard', $data);
    }

    public function tenants()
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get all tenants
        $tenants = $this->tenantsModel->orderBy('created_at', 'DESC')->findAll();

        // Get usage statistics for each tenant
        $db = \Config\Database::connect();
        foreach ($tenants as &$tenant) {
            // Count API users
            $tenant['api_users'] = $this->tenantUsersModel->where('tenant_id', $tenant['tenant_id'])->countAllResults();

            // Get usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens
                FROM usage_logs
                WHERE tenant_id = ?
            ", [$tenant['tenant_id']])->getRowArray();

            $tenant['total_requests'] = $usage['total_requests'] ?? 0;
            $tenant['total_tokens'] = $usage['total_tokens'] ?? 0;
        }

        $data = [
            'title' => 'Manage Tenants',
            'tenants' => $tenants
        ];

        return view('admin/tenants', $data);
    }

    public function viewTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API users for this tenant with their usage statistics
        $apiUsers = $this->tenantUsersModel->where('tenant_id', $tenant['tenant_id'])->findAll();
        
        // Get usage statistics for each API user
        $db = \Config\Database::connect();
        foreach ($apiUsers as &$user) {
            $usage = $db->query("
                SELECT COALESCE(SUM(tokens), 0) as total_tokens
                FROM usage_logs
                WHERE tenant_id = ? AND api_user_id = ?
            ", [$tenant['tenant_id'], $user['user_id']])->getRowArray();
            
            $user['usage'] = $usage['total_tokens'] ?? 0;
        }

        // Get buttons with their usage statistics
        $buttons = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])->findAll();
        foreach ($buttons as &$button) {
            // Get button usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens,
                    COUNT(DISTINCT api_user_id) as unique_users
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                AND status = 'success'
            ", [$tenant['tenant_id'], $button['id']])->getRowArray();

            // Get last usage timestamp
            $lastUsage = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant['tenant_id'], $button['id']])->getRowArray();

            $button['usage'] = $usage;
            $button['last_used'] = $lastUsage['created_at'] ?? null;
        }

        // Sort buttons by most used
        usort($buttons, function($a, $b) {
            return ($b['usage']['total_requests'] ?? 0) - ($a['usage']['total_requests'] ?? 0);
        });

        // Get monthly usage using SQLite's strftime function
        $monthlyUsage = $db->query("
            WITH RECURSIVE dates(date) AS (
                SELECT date('now', '-11 months', 'start of month')
                UNION ALL
                SELECT date(date, '+1 month')
                FROM dates
                WHERE date < date('now', 'start of month')
            )
            SELECT 
                strftime('%Y-%m', dates.date) as month,
                COUNT(DISTINCT ul.id) as total_requests,
                COALESCE(SUM(ul.tokens), 0) as total_tokens
            FROM dates
            LEFT JOIN usage_logs ul ON 
                strftime('%Y-%m', datetime(ul.created_at)) = strftime('%Y-%m', dates.date)
                AND ul.tenant_id = ?
            GROUP BY strftime('%Y-%m', dates.date)
            ORDER BY dates.date ASC
        ", [$tenant['tenant_id']])->getResultArray();

        // Calculate totals
        $totals = $db->query("
            SELECT 
                COUNT(DISTINCT id) as total_requests,
                COALESCE(SUM(tokens), 0) as total_tokens
            FROM usage_logs
            WHERE tenant_id = ?
            AND status = 'success'
        ", [$tenant['tenant_id']])->getRowArray();

        $tenant['total_requests'] = $totals['total_requests'] ?? 0;
        $tenant['total_tokens'] = $totals['total_tokens'] ?? 0;
        $tenant['total_buttons'] = count($buttons);
        $tenant['api_users'] = count($apiUsers);

        $data = [
            'title' => 'View Tenant - ' . $tenant['name'],
            'tenant' => $tenant,
            'apiUsers' => $apiUsers,
            'buttons' => $buttons,
            'monthlyUsage' => $monthlyUsage
        ];

        return view('admin/tenant_view', $data);
    }

    public function createTenant()
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        if ($this->request->getMethod() === 'post') {
            // Validate input
            $rules = [
                'tenant_id' => 'required|min_length[3]|is_unique[tenants.tenant_id]',
                'name' => 'required|min_length[3]',
                'email' => 'required|valid_email',
                'subscription_status' => 'required|in_list[trial,active,expired]',
                'active' => 'permit_empty|in_list[0,1]'
            ];

            if ($this->validate($rules)) {
                // Create tenant
                $this->tenantsModel->insert([
                    'tenant_id' => $this->request->getPost('tenant_id'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ?? 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                return redirect()->to('admin/tenants')
                    ->with('success', 'Tenant created successfully');
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Create Tenant'
        ];

        return view('admin/create_tenant', $data);
    }

    public function editTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            // Validate input
            $rules = [
                'name' => 'required|min_length[3]',
                'email' => 'required|valid_email',
                'subscription_status' => 'required|in_list[trial,active,expired]',
                'active' => 'permit_empty|in_list[0,1]'
            ];

            if ($this->validate($rules)) {
                // Update tenant
                $this->tenantsModel->update($tenantId, [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ?? 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                return redirect()->to('admin/tenants')
                    ->with('success', 'Tenant updated successfully');
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Edit Tenant - ' . $tenant['name'],
            'tenant' => $tenant
        ];

        return view('admin/tenant_edit', $data);
    }

    public function deleteTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Delete tenant and all related data
        $this->tenantsModel->delete($tenant['id']);

        return redirect()->to('admin/tenants')
            ->with('success', 'Tenant deleted successfully');
    }

    public function tenantApiUsers($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API users (not system users)
        $users = $this->tenantUsersModel->where('tenant_id', $tenant['tenant_id'])->findAll();

        $data = [
            'title' => 'API Users - ' . $tenant['name'],
            'tenant' => $tenant,
            'users' => $users
        ];

        return view('admin/tenant_users', $data);
    }

    public function createApiUser($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            // Validate input
            $rules = [
                'user_id' => 'required|min_length[3]|is_unique[tenant_users.user_id]',
                'name' => 'required|min_length[3]',
                'email' => 'permit_empty|valid_email',
                'quota' => 'required|integer|greater_than[0]'
            ];

            if ($this->validate($rules)) {
                // Create API user
                $this->tenantUsersModel->insert([
                    'tenant_id' => $tenant['tenant_id'],
                    'user_id' => $this->request->getPost('user_id'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                return redirect()->to('admin/tenants/users/' . $tenantId)
                    ->with('success', 'API user created successfully');
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Create API User - ' . $tenant['name'],
            'tenant' => $tenant
        ];

        return view('admin/tenant_user_add', $data);
    }

    public function editApiUser($tenantId, $userId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->find($userId);
        if (!$user || $user['tenant_id'] !== $tenant['tenant_id']) {
            return redirect()->to('admin/tenants/users/' . $tenantId)
                ->with('error', 'API user not found');
        }

        if ($this->request->getMethod() === 'post') {
            // Validate input
            $rules = [
                'name' => 'required|min_length[3]',
                'email' => 'permit_empty|valid_email',
                'quota' => 'required|integer|greater_than[0]',
                'active' => 'required|in_list[0,1]'
            ];

            if ($this->validate($rules)) {
                // Update API user
                $this->tenantUsersModel->update($userId, [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                return redirect()->to('admin/tenants/users/' . $tenantId)
                    ->with('success', 'API user updated successfully');
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title' => 'Edit API User - ' . $tenant['name'],
            'tenant' => $tenant,
            'user' => $user
        ];

        return view('admin/tenant_user_edit', $data);
    }

    public function deleteApiUser($tenantId, $userId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->find($userId);
        if (!$user || $user['tenant_id'] !== $tenant['tenant_id']) {
            return redirect()->to('admin/tenants/users/' . $tenantId)
                ->with('error', 'API user not found');
        }

        // Delete API user
        $this->tenantUsersModel->delete($userId);

        return redirect()->to('admin/tenants/users/' . $tenantId)
            ->with('success', 'API user deleted successfully');
    }

    public function apiUserUsage($tenantId, $userId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->find($userId);
        if (!$user || $user['tenant_id'] !== $tenant['tenant_id']) {
            return redirect()->to('admin/tenants/users/' . $tenantId)
                ->with('error', 'API user not found');
        }

        // Get usage statistics
        $db = \Config\Database::connect();
        $monthlyUsage = $db->query("
            WITH RECURSIVE dates(date) AS (
                SELECT date('now', '-11 months', 'start of month')
                UNION ALL
                SELECT date(date, '+1 month')
                FROM dates
                WHERE date < date('now', 'start of month')
            )
            SELECT 
                strftime('%Y-%m', dates.date) as month,
                COUNT(DISTINCT ul.id) as total_requests,
                COALESCE(SUM(ul.tokens), 0) as total_tokens
            FROM dates
            LEFT JOIN usage_logs ul ON 
                strftime('%Y-%m', datetime(ul.created_at)) = strftime('%Y-%m', dates.date)
                AND ul.tenant_id = ?
                AND ul.api_user_id = ?
            GROUP BY strftime('%Y-%m', dates.date)
            ORDER BY dates.date ASC
        ", [$tenant['tenant_id'], $user['user_id']])->getResultArray();

        $data = [
            'title' => 'API User Usage - ' . $user['name'],
            'tenant' => $tenant,
            'user' => $user,
            'monthlyUsage' => $monthlyUsage
        ];

        return view('admin/tenant_user_usage', $data);
    }

    public function tenantButtons($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get buttons with usage statistics
        $buttons = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])->findAll();
        $db = \Config\Database::connect();
        
        foreach ($buttons as &$button) {
            // Get button usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens,
                    COUNT(DISTINCT api_user_id) as unique_users
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                AND status = 'success'
            ", [$tenant['tenant_id'], $button['id']])->getRowArray();

            // Get last usage timestamp
            $lastUsage = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant['tenant_id'], $button['id']])->getRowArray();

            $button['usage'] = $usage;
            $button['last_used'] = $lastUsage['created_at'] ?? null;
        }

        // Sort buttons by most used
        usort($buttons, function($a, $b) {
            return ($b['usage']['total_requests'] ?? 0) - ($a['usage']['total_requests'] ?? 0);
        });

        $data = [
            'title' => 'Manage Buttons - ' . $tenant['name'],
            'tenant' => $tenant,
            'buttons' => $buttons
        ];

        return view('admin/tenant_buttons', $data);
    }

    public function createButton($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'type' => 'required|in_list[standard,custom]',
                'description' => 'permit_empty|max_length[1000]',
                'prompt' => 'required|min_length[10]',
                'system_prompt' => 'permit_empty|max_length[2000]',
                'temperature' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[2]',
                'max_tokens' => 'permit_empty|integer|greater_than[0]|less_than[4096]'
            ];

            if ($this->validate($rules)) {
                $buttonData = [
                    'tenant_id' => $tenant['tenant_id'],
                    'name' => $this->request->getPost('name'),
                    'type' => $this->request->getPost('type'),
                    'description' => $this->request->getPost('description'),
                    'prompt' => $this->request->getPost('prompt'),
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'temperature' => $this->request->getPost('temperature') ?: 0.7,
                    'max_tokens' => $this->request->getPost('max_tokens') ?: 2048,
                    'active' => 1
                ];

                if ($this->buttonsModel->insert($buttonData)) {
                    return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                        ->with('success', 'Button created successfully');
                }

                return redirect()->back()
                    ->with('error', 'Failed to create button')
                    ->withInput();
            }

            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = [
            'title' => 'Create Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'validation' => $this->validator
        ];

        return view('admin/tenant_button_form', $data);
    }

    public function editButton($tenantId, $buttonId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $button = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])
            ->where('id', $buttonId)
            ->first();

        if (!$button) {
            return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                ->with('error', 'Button not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'type' => 'required|in_list[standard,custom]',
                'description' => 'permit_empty|max_length[1000]',
                'prompt' => 'required|min_length[10]',
                'system_prompt' => 'permit_empty|max_length[2000]',
                'temperature' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[2]',
                'max_tokens' => 'permit_empty|integer|greater_than[0]|less_than[4096]',
                'active' => 'required|in_list[0,1]'
            ];

            if ($this->validate($rules)) {
                $buttonData = [
                    'name' => $this->request->getPost('name'),
                    'type' => $this->request->getPost('type'),
                    'description' => $this->request->getPost('description'),
                    'prompt' => $this->request->getPost('prompt'),
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'temperature' => $this->request->getPost('temperature') ?: 0.7,
                    'max_tokens' => $this->request->getPost('max_tokens') ?: 2048,
                    'active' => $this->request->getPost('active')
                ];

                if ($this->buttonsModel->update($button['id'], $buttonData)) {
                    return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                        ->with('success', 'Button updated successfully');
                }

                return redirect()->back()
                    ->with('error', 'Failed to update button')
                    ->withInput();
            }

            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = [
            'title' => 'Edit Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'button' => $button,
            'validation' => $this->validator
        ];

        return view('admin/tenant_button_form', $data);
    }

    public function deleteButton($tenantId, $buttonId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $button = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])
            ->where('id', $buttonId)
            ->first();

        if (!$button) {
            return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                ->with('error', 'Button not found');
        }

        try {
            // Delete associated usage logs first
            $this->usageLogsModel->where('tenant_id', $tenant['tenant_id'])
                ->where('button_id', $button['id'])
                ->delete();

            // Then delete the button
            if ($this->buttonsModel->delete($button['id'])) {
                return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                    ->with('success', 'Button and associated usage logs deleted successfully');
            }

            return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                ->with('error', 'Failed to delete button');
        } catch (\Exception $e) {
            log_message('error', 'Error deleting button: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete button. Please try again.');
        }
    }
}
