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
        $recentTenants = $this->tenantsModel->orderBy('created_at', 'DESC')->asArray()->findAll(5);
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
        $tenants = $this->tenantsModel->orderBy('created_at', 'DESC')->asArray()->findAll();

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

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API users for this tenant with their usage statistics
        $apiUsers = $this->tenantUsersModel->where('tenant_id', $tenant['tenant_id'])->asArray()->findAll();
        
        // Get usage statistics for each API user
        $db = \Config\Database::connect();
        foreach ($apiUsers as &$user) {
            $usage = $db->query("
                SELECT COALESCE(SUM(tokens), 0) as total_tokens
                FROM usage_logs
                WHERE tenant_id = ? AND user_id = ?
            ", [$tenant['tenant_id'], $user['user_id']])->getRowArray();
            
            $user['usage'] = $usage['total_tokens'] ?? 0;
        }

        // Get buttons with their usage statistics
        $buttons = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])->asArray()->findAll();
        foreach ($buttons as &$button) {
            // Get button usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens,
                    COUNT(DISTINCT user_id) as unique_users
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                AND status = 'success'
            ", [$tenant['tenant_id'], $button['button_id']])->getRowArray();

            // Get last usage timestamp
            $lastUsage = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant['tenant_id'], $button['button_id']])->getRowArray();

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

        $data = [
            'title' => 'Create New Tenant'
        ];

        return view('admin/create_tenant', $data);
    }

    public function storeTenant()
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'subscription_status' => 'required|in_list[trial,active,expired]',
            'active' => 'permit_empty|in_list[0,1]'
        ];

        if ($this->validate($rules)) {
            try {
                helper('hash');
                // Create tenant with auto-generated ID
                $tenantData = [
                    'tenant_id' => generate_hash_id('ten'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ?? 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->tenantsModel->insert($tenantData);

                return redirect()->to('admin/tenants')
                    ->with('success', 'Tenant created successfully');
            } catch (\Exception $e) {
                log_message('error', 'Error creating tenant: ' . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Failed to create tenant. Please try again.')
                    ->withInput();
            }
        }

        return redirect()->back()
            ->withInput()
            ->with('errors', $this->validator->getErrors());
    }

    public function editTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $data = [
            'title' => 'Edit Tenant - ' . $tenant['name'],
            'tenant' => $tenant
        ];

        return view('admin/tenant_edit', $data);
    }

    public function updateTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'subscription_status' => 'required|in_list[trial,active,expired]',
            'active' => 'permit_empty|in_list[0,1]'
        ];

        if ($this->validate($rules)) {
            try {
                // Update tenant using tenant_id
                $this->tenantsModel->where('tenant_id', $tenantId)->set([
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ?? 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ])->update();

                return redirect()->to('admin/tenants')
                    ->with('success', 'Tenant updated successfully');
            } catch (\Exception $e) {
                log_message('error', 'Error updating tenant: ' . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Failed to update tenant. Please try again.')
                    ->withInput();
            }
        }

        return redirect()->back()
            ->withInput()
            ->with('errors', $this->validator->getErrors());
    }

    public function deleteTenant($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Delete tenant using tenant_id
        $this->tenantsModel->where('tenant_id', $tenantId)->delete();

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
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API users (not system users) and convert to array
        $users = $this->tenantUsersModel
            ->where('tenant_id', $tenant['tenant_id'])
            ->asArray()
            ->findAll();

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
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $data = [
            'title' => 'Create API User - ' . $tenant['name'],
            'tenant' => $tenant
        ];

        return view('admin/tenant_user_add', $data);
    }

    public function storeApiUser($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'permit_empty|valid_email',
            'quota' => 'required|integer|greater_than[0]'
        ];

        if ($this->validate($rules)) {
            try {
                helper('hash');
                // Create API user with auto-generated ID
                $userData = [
                    'tenant_id' => $tenant['tenant_id'],
                    'user_id' => generate_hash_id('usr'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->tenantUsersModel->insert($userData);

                return redirect()->to('admin/tenants/' . $tenantId . '/users')
                    ->with('success', 'API user created successfully');
            } catch (\Exception $e) {
                log_message('error', 'Error creating API user: ' . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Failed to create API user. Please try again.')
                    ->withInput();
            }
        }

        return redirect()->back()
            ->withInput()
            ->with('errors', $this->validator->getErrors());
    }

    public function editApiUser($tenantId, $userId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->where('user_id', $userId)
                                      ->where('tenant_id', $tenant['tenant_id'])
                                      ->asArray()
                                      ->first();
        if (!$user) {
            return redirect()->to('admin/tenants/' . $tenantId . '/users')
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
                $this->tenantUsersModel->where('user_id', $userId)->set([
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ])->update();

                return redirect()->to('admin/tenants/' . $tenantId . '/users')
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
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->where('user_id', $userId)
                                      ->where('tenant_id', $tenant['tenant_id'])
                                      ->asArray()
                                      ->first();
        if (!$user) {
            return redirect()->to('admin/tenants/' . $tenantId . '/users')
                ->with('error', 'API user not found');
        }

        // Delete API user
        $this->tenantUsersModel->where('user_id', $userId)->delete();

        return redirect()->to('admin/tenants/' . $tenantId . '/users')
            ->with('success', 'API user deleted successfully');
    }

    public function apiUserUsage($tenantId, $userId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->tenantUsersModel->where('user_id', $userId)
                                      ->where('tenant_id', $tenant['tenant_id'])
                                      ->asArray()
                                      ->first();
        if (!$user) {
            return redirect()->to('admin/tenants/' . $tenantId . '/users')
                ->with('error', 'API user not found');
        }

        // Get usage data ordered by request timestamp
        $db = \Config\Database::connect();
        $usage = $db->table('usage_logs')
                   ->where('tenant_id', $tenant['tenant_id'])
                   ->where('user_id', $user['user_id'])
                   ->orderBy('request_timestamp', 'DESC')
                   ->get()
                   ->getResultArray();

        $data = [
            'title' => 'API Usage - ' . $user['name'],
            'tenant' => $tenant,
            'user' => $user,
            'usage' => $usage
        ];

        return view('admin/tenant_user_usage', $data);
    }

    public function tenantButtons($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get buttons with usage statistics
        $buttons = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])->asArray()->findAll();
        $db = \Config\Database::connect();
        
        foreach ($buttons as &$button) {
            // Get button usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens,
                    COUNT(DISTINCT user_id) as unique_users
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                AND status = 'success'
            ", [$tenant['tenant_id'], $button['button_id']])->getRowArray();

            // Get last usage timestamp
            $lastUsage = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant['tenant_id'], $button['button_id']])->getRowArray();

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

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $data = [
            'title' => 'Create Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'providers' => [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic Claude',
                'mistral' => 'Mistral AI',
                'cohere' => 'Cohere',
                'deepseek' => 'DeepSeek',
                'google' => 'Google Gemini'
            ],
            'models' => [
                'openai' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4-vision' => 'GPT-4 Vision',
                ],
                'anthropic' => [
                    'claude-3-opus-20240229' => 'Claude 3 Opus',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                ],
                'mistral' => [
                    'mistral-small-latest' => 'Mistral Small',
                    'mistral-medium-latest' => 'Mistral Medium',
                    'mistral-large-latest' => 'Mistral Large',
                ],
                'cohere' => [
                    'command' => 'Command',
                    'command-light' => 'Command Light',
                ],
                'deepseek' => [
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder',
                ],
                'google' => [
                    'gemini-pro' => 'Gemini Pro',
                    'gemini-pro-vision' => 'Gemini Pro Vision',
                ]
            ]
        ];

        return view('admin/tenant_button_form', $data);
    }

    public function storeButton($tenantId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'domain' => 'required|valid_url_strict[https]',
            'provider' => 'required|in_list[openai,anthropic,mistral,cohere,deepseek,google]',
            'model' => 'required',
            'api_key' => 'required',
            'system_prompt' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Generate button ID using hash helper
        helper('hash');
        $buttonId = generate_hash_id('btn');

        // Encrypt API key
        $encrypter = \Config\Services::encrypter();
        $encryptedKey = base64_encode($encrypter->encrypt($this->request->getPost('api_key')));

        // Prepare button data
        $buttonData = [
            'button_id' => $buttonId,
            'tenant_id' => $tenant['tenant_id'],
            'name' => $this->request->getPost('name'),
            'domain' => $this->request->getPost('domain'),
            'provider' => $this->request->getPost('provider'),
            'model' => $this->request->getPost('model'),
            'api_key' => $encryptedKey,
            'system_prompt' => $this->request->getPost('system_prompt'),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert button
        if ($this->buttonsModel->insert($buttonData)) {
            return redirect()->to('admin/tenants/' . $tenant['tenant_id'] . '/buttons')
                ->with('success', 'Button created successfully');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create button. Please try again.');
    }

    public function editButton($tenantId, $buttonId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $button = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])
            ->where('button_id', $buttonId)
            ->asArray()
            ->first();

        if (!$button) {
            return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                ->with('error', 'Button not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'domain' => 'required|valid_domain',
                'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
                'model' => 'required',
                'api_key' => 'permit_empty|min_length[10]|max_length[255]',
                'system_prompt' => 'permit_empty|max_length[2000]'
            ];

            if ($this->validate($rules)) {
                try {
                    $updateData = [
                        'name' => $this->request->getPost('name'),
                        'domain' => $this->request->getPost('domain'),
                        'provider' => $this->request->getPost('provider'),
                        'model' => $this->request->getPost('model'),
                        'system_prompt' => $this->request->getPost('system_prompt')
                    ];

                    // Only update API key if a new one is provided
                    $newApiKey = $this->request->getPost('api_key');
                    if (!empty($newApiKey)) {
                        $encrypter = \Config\Services::encrypter();
                        $updateData['api_key'] = base64_encode($encrypter->encrypt($newApiKey));
                    }

                    $this->buttonsModel->where('button_id', $buttonId)
                                     ->where('tenant_id', $tenant['tenant_id'])
                                     ->set($updateData)
                                     ->update();

                    return redirect()->to('admin/tenants/' . $tenantId . '/buttons')
                        ->with('success', 'Button updated successfully');
                } catch (\Exception $e) {
                    log_message('error', 'Error updating button: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Failed to update button. Please try again.');
                }
            }
        }

        $data = [
            'title' => 'Edit Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'button' => $button,
            'providers' => [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic Claude',
                'mistral' => 'Mistral AI',
                'cohere' => 'Cohere',
                'deepseek' => 'DeepSeek',
                'google' => 'Google Gemini'
            ],
            'models' => [
                'openai' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4-vision' => 'GPT-4 Vision',
                ],
                'anthropic' => [
                    'claude-3-opus-20240229' => 'Claude 3 Opus',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                ],
                'mistral' => [
                    'mistral-small-latest' => 'Mistral Small',
                    'mistral-medium-latest' => 'Mistral Medium',
                    'mistral-large-latest' => 'Mistral Large',
                ],
                'cohere' => [
                    'command' => 'Command',
                    'command-light' => 'Command Light',
                ],
                'deepseek' => [
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder',
                ],
                'google' => [
                    'gemini-pro' => 'Gemini Pro',
                    'gemini-pro-vision' => 'Gemini Pro Vision',
                ]
            ]
        ];

        // Use the tenant's edit view instead of admin view
        return view('shared/buttons/edit', $data);
    }

    public function viewButton($tenantId, $buttonId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $button = $this->buttonsModel->where('button_id', $buttonId)
                                   ->where('tenant_id', $tenant['tenant_id'])
                                   ->asArray()
                                   ->first();
        if (!$button) {
            return redirect()->to('admin/tenants/' . $tenantId . '/buttons')
                ->with('error', 'Button not found');
        }

        $data = [
            'title' => 'View Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'button' => $button,
            'providers' => [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic Claude',
                'mistral' => 'Mistral AI',
                'cohere' => 'Cohere',
                'deepseek' => 'DeepSeek',
                'google' => 'Google Gemini'
            ],
            'models' => [
                'openai' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4-vision' => 'GPT-4 Vision',
                ],
                'anthropic' => [
                    'claude-3-opus-20240229' => 'Claude 3 Opus',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                ],
                'mistral' => [
                    'mistral-small-latest' => 'Mistral Small',
                    'mistral-medium-latest' => 'Mistral Medium',
                    'mistral-large-latest' => 'Mistral Large',
                ],
                'cohere' => [
                    'command' => 'Command',
                    'command-light' => 'Command Light',
                ],
                'deepseek' => [
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder',
                ],
                'google' => [
                    'gemini-pro' => 'Gemini Pro',
                    'gemini-pro-vision' => 'Gemini Pro Vision',
                ]
            ]
        ];

        // Use the tenant's view template instead of admin view
        return view('shared/buttons/view', $data);
    }

    public function deleteButton($tenantId, $buttonId)
    {
        // Check if user is logged in and is superadmin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $button = $this->buttonsModel->where('tenant_id', $tenant['tenant_id'])
            ->where('button_id', $buttonId)
            ->asArray()
            ->first();

        if (!$button) {
            return redirect()->to("admin/tenants/{$tenant['tenant_id']}/buttons")
                ->with('error', 'Button not found');
        }

        try {
            // Delete associated usage logs first
            $this->usageLogsModel->where('tenant_id', $tenant['tenant_id'])
                ->where('button_id', $button['button_id'])
                ->delete();

            // Then delete the button
            if ($this->buttonsModel->where('button_id', $buttonId)->delete()) {
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
