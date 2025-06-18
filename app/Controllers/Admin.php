<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TenantsModel;
use App\Models\UsersModel;
use App\Models\ButtonsModel;
use App\Models\UsageLogsModel;
use App\Models\TenantUsersModel;
use App\Models\ApiUsersModel;

class Admin extends BaseController
{
    /**
     * Muestra el formulario para agregar una API Key a un tenant (admin)
     */
    public function addTenantApiKey($tenantId)
    {
        // Aquí puedes cargar datos del tenant si lo deseas
        $data = [
            'title' => 'Agregar API Key',
            'tenantId' => $tenantId
        ];
        return view('admin/add_tenant_api_key', $data);
    }

    /**
     * Procesa el guardado de una nueva API Key para un tenant (admin)
     */
    public function storeTenantApiKey($tenantId)
    {
        helper(['form', 'url', 'hash']);
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->back()->withInput()->with('error', 'Tenant no encontrado');
        }

        // Verificar límite de API Keys
        $apiKeysModel = model('App\Models\ApiKeysModel');
        $current_keys = $apiKeysModel->where('tenant_id', $tenantId)->findAll();
        $maxApiKeys = $tenant['max_api_keys'] ?? 1;
        if (count($current_keys) >= $maxApiKeys) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('error', "Has alcanzado el límite de {$maxApiKeys} API Keys para este tenant.");
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
            'api_key' => 'required|min_length[10]'
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        // Generate a unique API key ID
        $api_key_id = generate_hash_id('key');

        // Encrypt the API key
        $encrypter = \Config\Services::encrypter();
        $encrypted_key = base64_encode($encrypter->encrypt($this->request->getPost('api_key')));

        $data = [
            'api_key_id' => $api_key_id,
            'tenant_id' => $tenantId,
            'name' => $this->request->getPost('name'),
            'provider' => $this->request->getPost('provider'),
            'api_key' => $encrypted_key,
            'is_default' => count($current_keys) === 0 ? 1 : 0,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($apiKeysModel->insert($data)) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('success', 'API Key agregada correctamente.');
        }
        return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
            ->withInput()
            ->with('error', 'Error al agregar la API Key: ' . implode('<br>', $apiKeysModel->errors()));
    }

    /**
     * Elimina una API Key de un tenant (admin)
     */
    public function deleteTenantApiKey($tenantId, $apiKeyId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $apiKeysModel = model('App\Models\ApiKeysModel');
        
        // Verify the API key belongs to the tenant
        $apiKey = $apiKeysModel->where('api_key_id', $apiKeyId)
                              ->where('tenant_id', $tenantId)
                              ->first();
        
        if (!$apiKey) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('error', 'API Key not found.');
        }

        // Check if API key is being used by any buttons
        $buttonsModel = model('App\Models\ButtonsModel');
        $buttonsUsingKey = $buttonsModel->where('tenant_id', $tenantId)
                                       ->where('api_key_id', $apiKeyId)
                                       ->where('status', 'active')
                                       ->findAll();

        if (!empty($buttonsUsingKey)) {
            $buttonNames = array_column($buttonsUsingKey, 'name');
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('error', 'No se puede eliminar la API Key porque está siendo usada por los siguientes botones: ' . implode(', ', $buttonNames) . '. Primero cambia la configuración de estos botones.');
        }

        // Check if this is the last API key
        $remaining_keys = $apiKeysModel->where('tenant_id', $tenantId)
                                     ->where('api_key_id !=', $apiKeyId)
                                     ->findAll();

        // If this is the default key and there are other keys, set another one as default
        if ($apiKey['is_default'] && !empty($remaining_keys)) {
            $apiKeysModel->setDefault($remaining_keys[0]['api_key_id'], $tenantId);
        }

        if ($apiKeysModel->delete($apiKeyId)) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('success', 'API Key eliminada correctamente.');
        }

        return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
            ->with('error', 'Error al eliminar la API Key.');
    }

    /**
     * Muestra el formulario de edición de una API Key (admin)
     */
    public function editTenantApiKey($tenantId, $apiKeyId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $apiKeysModel = model('App\Models\ApiKeysModel');
        $apiKey = $apiKeysModel->where('api_key_id', $apiKeyId)
                              ->where('tenant_id', $tenantId)
                              ->first();

        if (!$apiKey) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('error', 'API Key not found.');
        }

        $data = [
            'title' => 'Editar API Key',
            'tenant' => $tenant,
            'apiKey' => $apiKey,
            'providers' => [
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'cohere' => 'Cohere',
                'mistral' => 'Mistral',
                'deepseek' => 'DeepSeek',
                'google' => 'Google'
            ]
        ];

        return view('admin/edit_tenant_api_key', $data);
    }

    /**
     * Actualiza una API Key de un tenant (admin)
     */
    public function updateTenantApiKey($tenantId, $apiKeyId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        $apiKeysModel = model('App\Models\ApiKeysModel');
        
        // Verify the API key belongs to the tenant
        $apiKey = $apiKeysModel->where('api_key_id', $apiKeyId)
                              ->where('tenant_id', $tenantId)
                              ->first();
        
        if (!$apiKey) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('error', 'API Key not found.');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
            'api_key' => 'required|min_length[10]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'provider' => $this->request->getPost('provider'),
            'api_key' => $this->request->getPost('api_key') // Let the model handle encryption/decryption
        ];

        if ($apiKeysModel->update($apiKeyId, $data)) {
            return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
                ->with('success', 'API Key actualizada correctamente.');
        }

        return redirect()->to('admin/tenants/' . $tenantId . '/api_keys')
            ->withInput()
            ->with('error', 'Error al actualizar la API Key: ' . implode('<br>', $apiKeysModel->errors()));
    }
    protected $tenantsModel;
    protected $usersModel;
    protected $buttonsModel;
    protected $usageLogsModel;
    protected $tenantUsersModel;
    protected $apiUsersModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->tenantsModel = new TenantsModel();
        $this->usersModel = new UsersModel();
        $this->buttonsModel = new ButtonsModel();
        $this->usageLogsModel = new UsageLogsModel();
        $this->tenantUsersModel = new TenantUsersModel();
        $this->apiUsersModel = new ApiUsersModel();
    }

    public function index()
    {
        return redirect()->to('admin/dashboard');
    }

    public function dashboard()
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        usort($buttons, function ($a, $b) {
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'required|valid_email',
            'quota' => 'required|integer|greater_than[0]',
            'plan_code' => 'required|in_list[small,medium,large]',
            'max_domains' => 'required|integer|greater_than[0]',
            'max_api_keys' => 'required|integer|in_list[1,3,10]',
            'subscription_status' => 'required|in_list[trial,active,expired]',
            'active' => 'permit_empty|in_list[0,1]',
            'auto_create_users' => 'permit_empty|in_list[0,1]'
        ];

        if ($this->validate($rules)) {
            try {
                helper('hash');
                $tenantData = [
                    'tenant_id' => generate_hash_id('ten'),
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'plan_code' => $this->request->getPost('plan_code'),
                    'max_domains' => $this->request->getPost('max_domains'),
                    'max_api_keys' => $this->request->getPost('max_api_keys'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ? 1 : 0,
                    'auto_create_users' => $this->request->getPost('auto_create_users') ? 1 : 0,
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
            'active' => 'permit_empty|in_list[0,1]',
            'max_api_keys' => 'required|integer|in_list[1,3,10]'
        ];

        if ($this->validate($rules)) {
            try {
                // Update tenant using tenant_id
                $this->tenantsModel->where('tenant_id', $tenantId)->set([
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'subscription_status' => $this->request->getPost('subscription_status'),
                    'active' => $this->request->getPost('active') ?? 1,
                    'max_api_keys' => $this->request->getPost('max_api_keys'),
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API users (not system users) and convert to array
        log_message('debug', '[tenantApiUsers] tenantId recibido: ' . $tenantId);
        log_message('debug', '[tenantApiUsers] tenant encontrado: ' . print_r($tenant, true));
        $users = $this->apiUsersModel
            ->where('tenant_id', $tenant['tenant_id'])
            ->asArray()
            ->findAll();
        log_message('debug', '[tenantApiUsers] usuarios encontrados: ' . print_r($users, true));

        $data = [
            'title' => 'API Users - ' . $tenant['name'],
            'tenant' => $tenant,
            'users' => $users
        ];

        return view('admin/tenant_users', $data);
    }

    public function createApiUser($tenantId)
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
            'external_id' => 'required|max_length[255]',
            'name' => 'required|min_length[3]',
            'email' => 'permit_empty|valid_email',
            'quota' => 'required|integer|greater_than[0]'
        ];

        // Check if external_id already exists for this tenant
        $existingUser = $this->apiUsersModel
            ->where('tenant_id', $tenant['tenant_id'])
            ->where('external_id', $this->request->getPost('external_id'))
            ->first();

        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'An API user with this External ID already exists for this tenant');
        }

        if ($this->validate($rules)) {
            try {
                helper('hash');

                // Generate user ID
                $user_id = generate_hash_id('usr');

                // Use simple PDO connection directly to avoid model issues
                $dsn = 'sqlite:' . WRITEPATH . 'database.sqlite';
                $pdo = new \PDO($dsn);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                log_message('debug', 'Creating API user with data: ' . json_encode([
                    'user_id' => $user_id,
                    'external_id' => $this->request->getPost('external_id'),
                    'tenant_id' => $tenant['tenant_id'],
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota')
                ]));

                // Create API user with direct PDO query
                $sql = "
                    INSERT INTO tenant_users (
                        user_id,
                        external_id,
                        tenant_id,
                        name,
                        email,
                        quota,
                        active,
                        created_at,
                        updated_at,
                        role
                    ) VALUES (
                        :user_id,
                        :external_id,
                        :tenant_id,
                        :name,
                        :email,
                        :quota,
                        :active,
                        :created_at,
                        :updated_at,
                        :role
                    )
                ";

                try {
                    $stmt = $pdo->prepare($sql);
                    if (!$stmt) {
                        $error = $pdo->errorInfo();
                        log_message('error', 'Failed to prepare statement: ' . json_encode($error));
                        throw new \Exception("Failed to prepare statement: " . json_encode($error));
                    }

                    $params = [
                        ':user_id' => $user_id,
                        ':external_id' => $this->request->getPost('external_id'),
                        ':tenant_id' => $tenant['tenant_id'],
                        ':name' => $this->request->getPost('name'),
                        ':email' => $this->request->getPost('email'),
                        ':quota' => $this->request->getPost('quota'),
                        ':active' => 1,
                        ':created_at' => date('Y-m-d H:i:s'),
                        ':updated_at' => date('Y-m-d H:i:s'),
                        ':role' => 'user'
                    ];

                    log_message('debug', 'Executing query with params: ' . json_encode($params));

                    $result = $stmt->execute($params);

                    if (!$result) {
                        $error = $stmt->errorInfo();
                        log_message('error', 'Database insert failed: ' . json_encode($error));
                        throw new \Exception("Database insert failed: " . json_encode($error));
                    }

                    log_message('info', 'API user created successfully with ID: ' . $user_id);
                } catch (\Exception $e) {
                    log_message('error', 'Error creating API user: ' . $e->getMessage());
                    throw new \Exception('Failed to create API user: ' . $e->getMessage());
                }

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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->apiUsersModel->where('user_id', $userId)
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
                $this->apiUsersModel->where('user_id', $userId)->set([
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->apiUsersModel->where('user_id', $userId)
            ->where('tenant_id', $tenant['tenant_id'])
            ->asArray()
            ->first();
        if (!$user) {
            return redirect()->to('admin/tenants/' . $tenantId . '/users')
                ->with('error', 'API user not found');
        }

        // Delete API user
        $this->apiUsersModel->where('user_id', $userId)->delete();

        return redirect()->to('admin/tenants/' . $tenantId . '/users')
            ->with('success', 'API user deleted successfully');
    }

    public function apiUserUsage($tenantId, $userId)
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        // Get tenant details
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get API user details
        $user = $this->apiUsersModel->where('user_id', $userId)
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

    /**
     * Vista y gestión de API Keys de un tenant (modo admin)
     */
    public function tenantApiKeys($tenantId)
    {
        // Solo admin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }
        $apiKeys = model('App\\Models\\ApiKeysModel')->getTenantApiKeys($tenantId);
        $data = [
            'tenant' => $tenant,
            'apiKeys' => $apiKeys
        ];
        return view('admin/tenant_api_keys', $data);
    }

    /**
     * Actualiza el plan (max_api_keys) de un tenant
     */
    public function updateTenantPlan($tenantId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }
        $maxApiKeys = (int) $this->request->getPost('max_api_keys');
        $this->tenantsModel->where('tenant_id', $tenantId)->set(['max_api_keys' => $maxApiKeys])->update();
        return redirect()->to('admin/tenants/' . $tenantId)->with('success', 'Plan actualizado correctamente');
    }

    public function tenantButtons($tenantId)
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
        usort($buttons, function ($a, $b) {
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            return redirect()->to('admin/tenants')->with('error', 'Tenant not found');
        }

        // Get available API keys for the tenant
        $apiKeys = model('App\Models\ApiKeysModel')->getTenantApiKeys($tenant['tenant_id']);
        $providers = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
            'deepseek' => 'DeepSeek',
            'google' => 'Google Gemini'
        ];
        $models = [
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
        ];
        // Obtener dominios del tenant
        $domains = model('App\\Models\\TenantsModel')->getDomains($tenant['tenant_id']);
        $data = [
            'title' => 'Create Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'apiKeys' => $apiKeys,
            'providers' => $providers,
            'models' => $models,
            'domains' => $domains
        ];
        return view('admin/tenant_button_form', $data);
    }

    public function storeButton($tenantId)
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            log_message('error', '[storeButton] Redirigido: usuario no logueado');
            return redirect()->to('/auth/login')->with('error', 'No estás logueado.');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            log_message('error', '[storeButton] Redirigido: permisos insuficientes. Role actual: ' . $role . ', tenant en sesión: ' . $sessionTenantId . ', tenantId: ' . $tenantId);
            return redirect()->to('/auth/login')->with('error', 'No tienes permisos para crear botones en este tenant.');
        }
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'superadmin') {
            log_message('error', '[storeButton] Redirigido: no es superadmin. Role actual: ' . session()->get('role'));
            return redirect()->to('/auth/login')->with('error', 'Solo el superadmin puede crear botones para tenants.');
        }
        $tenant = $this->tenantsModel->where('tenant_id', $tenantId)->asArray()->first();
        if (!$tenant) {
            log_message('error', '[storeButton] Redirigido: tenant no encontrado. tenantId: ' . $tenantId);
            return redirect()->to('admin/tenants')->with('error', 'Tenant no encontrado.');
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'domain' => 'required',
            'api_key_id' => 'required',
            'model' => 'required',
            'system_prompt' => 'required',
            'temperature' => 'permit_empty|decimal',
            'auto_create_api_users' => 'permit_empty|in_list[0,1]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Get the selected API key to determine provider
        $apiKeyId = $this->request->getPost('api_key_id');
        $apiKey = model('App\Models\ApiKeysModel')->where('tenant_id', $tenant['tenant_id'])
            ->where('api_key_id', $apiKeyId)
            ->first();

        if (!$apiKey) {
            return redirect()->back()->withInput()->with('error', 'Invalid API key selected.');
        }

        // Generate button ID using hash helper
        helper('hash');
        $buttonId = generate_hash_id('btn');

        // Prepare button data
        $buttonData = [
            'button_id' => $buttonId,
            'tenant_id' => $tenant['tenant_id'],
            'name' => $this->request->getPost('name'),
            'domain' => $this->request->getPost('domain'),
            'provider' => $apiKey['provider'],
            'model' => $this->request->getPost('model'),
            'api_key_id' => $apiKeyId,
            'system_prompt' => $this->request->getPost('system_prompt'),
            'temperature' => $this->request->getPost('temperature') ?: 0.7,
            'status' => $this->request->getPost('status') ? 'active' : 'inactive',
            'auto_create_api_users' => $this->request->getPost('auto_create_api_users') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert button
        if ($this->buttonsModel->insert($buttonData)) {
            log_message('debug', '[storeButton] Botón creado correctamente: ' . print_r($buttonData, true));
            return redirect()->to('admin/tenants/' . $tenant['tenant_id'] . '/buttons')
                ->with('success', 'Button created successfully');
        } else {
            log_message('error', '[storeButton] Error al crear botón: ' . print_r($this->buttonsModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Error al crear botón: ' . implode('<br>', $this->buttonsModel->errors()))
                ->with('errors', $this->buttonsModel->errors());
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create button. Please try again.');
    }

    public function editButton($tenantId, $buttonId)
    {
        // LOG: Inicio de edición de botón
        log_message('debug', '[editButton] INICIO - user_id: ' . session()->get('user_id') . ', role: ' . session()->get('role') . ', session_tenant_id: ' . session()->get('tenant_id'));
        log_message('debug', '[editButton] tenantId recibido: ' . $tenantId . ', buttonId recibido: ' . $buttonId);

        // LOG: Inicio de edición de botón
        log_message('debug', '[editButton] INICIO - user_id: ' . session()->get('user_id') . ', role: ' . session()->get('role') . ', session_tenant_id: ' . session()->get('tenant_id'));
        log_message('debug', '[editButton] tenantId recibido: ' . $tenantId . ', buttonId recibido: ' . $buttonId);
        //

        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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

        // Obtener las API Keys del tenant para el select
        $apiKeys = model('App\Models\ApiKeysModel')->getTenantApiKeys($tenant['tenant_id']);

        $data = [
            'title' => 'Edit Button - ' . $tenant['name'],
            'tenant' => $tenant,
            'button' => $button,
            'apiKeys' => $apiKeys,
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
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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

        // Get API key information if available
        $apiKey = null;
        if (!empty($button['api_key_id'])) {
            $apiKey = model('App\Models\ApiKeysModel')->where('api_key_id', $button['api_key_id'])->first();
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
            ],
            'api_key' => $apiKey
        ];

        // Use the tenant's view template instead of admin view
        return view('shared/buttons/view', $data);
    }

    public function deleteButton($tenantId, $buttonId)
    {
        // Permitir acceso a superadmin o al tenant propietario
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        $role = session()->get('role');
        $sessionTenantId = session()->get('tenant_id');
        if ($role !== 'superadmin' && !($role === 'tenant' && $sessionTenantId === $tenantId)) {
            // No es superadmin ni es el tenant propietario
            return redirect()->to('/auth/login');
        }
        //
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
