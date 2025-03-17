<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ButtonsModel;
use App\Models\TenantsModel;
use App\Models\DomainsModel;
use App\Models\TenantApiKeysModel;

class Buttons extends Controller
{
    protected $db;
    protected $buttonsModel;
    protected $tenantsModel;
    protected $domainsModel;
    protected $apiKeysModel;
    protected $providers;
    protected $models;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->buttonsModel = new \App\Models\ButtonsModel();
        $this->tenantsModel = new \App\Models\TenantsModel();
        $this->domainsModel = new \App\Models\DomainsModel();
        $this->apiKeysModel = new \App\Models\TenantApiKeysModel();

        // Define available providers
        $this->providers = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
            'deepseek' => 'DeepSeek',
            'google' => 'Google Gemini'
        ];

        // Define available models per provider
        $this->models = [
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
    }

    /**
     * List all buttons for the current tenant
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

        $data = [
            'title' => 'Buttons Management',
            'tenant' => $tenant,
            'buttons' => $this->buttonsModel->getButtonsWithStatsByTenant($tenant_id)
        ];

        return view('shared/buttons/index', $data);
    }

    /**
     * Create a new button
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

        // Get tenant API keys
        $tenantApiKeys = $this->apiKeysModel->getTenantApiKeys($tenant_id);

        // If no API keys, redirect to API keys page
        if (empty($tenantApiKeys)) {
            return redirect()->to('/api-keys')
                ->with('error', 'Necesitas configurar al menos una API Key antes de crear un botón');
        }

        // Get available providers (only those with API keys)
        $availableProviders = [];
        foreach ($tenantApiKeys as $apiKey) {
            if (isset($this->providers[$apiKey['provider']])) {
                $availableProviders[$apiKey['provider']] = $this->providers[$apiKey['provider']];
            }
        }

        // Get available models (only for providers with API keys)
        $availableModels = [];
        foreach ($availableProviders as $provider => $label) {
            if (isset($this->models[$provider])) {
                $availableModels[$provider] = $this->models[$provider];
            }
        }

        $data = [
            'title' => 'Create Button',
            'tenant' => $tenant,
            'domains' => $this->tenantsModel->getDomains($tenant_id),
            'providers' => $availableProviders,
            'models' => $availableModels,
            'apiKeys' => $tenantApiKeys
        ];

        return view('shared/buttons/create', $data);
    }

    /**
     * Store a new button
     */
    public function store()
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

        // Verificar que el dominio pertenezca al tenant y esté verificado
        $domain = $this->request->getPost('domain');
        $domains = $this->tenantsModel->getDomains($tenant_id);
        $allowedDomains = array_column($domains, 'domain');

        if (!in_array($domain, $allowedDomains)) {
            return redirect()->back()
                ->with('error', 'El dominio seleccionado no está permitido para tu cuenta')
                ->withInput();
        }

        // Comentado para permitir periodo de gracia para verificación de dominios
        /*
        // Verificar si el dominio está verificado
        $verifiedDomains = array_column(
            array_filter($domains, function($d) { return $d['verified'] == 1; }), 
            'domain'
        );
        
        if (!in_array($domain, $verifiedDomains)) {
            return redirect()->back()
                ->with('error', 'El dominio seleccionado no está verificado. Por favor, verifica el dominio primero.')
                ->withInput();
        }
        */

        // Verificar que el provider seleccionado tenga una API key configurada
        $provider = $this->request->getPost('provider');
        $api_key_id = $this->request->getPost('api_key_id');

        // Obtener la API key seleccionada
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
            ->where('tenant_id', $tenant_id)
            ->where('provider', $provider)
            ->where('active', 1)
            ->first();

        if (!$apiKey) {
            return redirect()->back()
                ->with('error', 'La API Key seleccionada no es válida o no pertenece al proveedor seleccionado')
                ->withInput();
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'domain' => 'required',
            'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
            'model' => 'required',
            'api_key_id' => 'required',
            'system_prompt' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'Please check the form for errors.')
                ->withInput()
                ->with('validation', $this->validator);
        }

        try {
            // Generate button ID using hash helper
            helper('hash');
            $buttonId = generate_hash_id('btn');

            // Get the encrypted API key from the tenant's API key
            $encryptedKey = $apiKey['api_key'];

            // Create button data
            $buttonData = [
                'button_id' => $buttonId,
                'tenant_id' => $tenant_id,
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

            if ($this->buttonsModel->insert($buttonData)) {
                return redirect()->to('/buttons')
                    ->with('success', 'Button created successfully');
            }

            return redirect()->back()
                ->with('error', 'Failed to create button. Please try again.')
                ->withInput();
        } catch (\Exception $e) {
            log_message('error', '[Button Creation] ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating button: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * View a button's details
     */
    public function view($button_id = null)
    {
        if (!$button_id) {
            return redirect()->to('/buttons')->with('error', 'Button ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $button = $this->buttonsModel->where('button_id', $button_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        $data = [
            'title' => 'View Button',
            'button' => $button,
            'tenant' => $this->tenantsModel->find($tenant_id),
            'providers' => $this->providers,
            'models' => $this->models
        ];

        return view('shared/buttons/view', $data);
    }

    /**
     * Edit an existing button
     */
    public function edit($button_id = null)
    {
        if (!$button_id) {
            return redirect()->to('/buttons')->with('error', 'Button ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $button = $this->buttonsModel->where('button_id', $button_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        // Get tenant API keys
        $tenantApiKeys = $this->apiKeysModel->getTenantApiKeys($tenant_id);

        // If no API keys, redirect to API keys page
        if (empty($tenantApiKeys)) {
            return redirect()->to('/api-keys')
                ->with('error', 'Necesitas configurar al menos una API Key antes de editar un botón');
        }

        // Get available providers (only those with API keys)
        $availableProviders = [];
        foreach ($tenantApiKeys as $apiKey) {
            if (isset($this->providers[$apiKey['provider']])) {
                $availableProviders[$apiKey['provider']] = $this->providers[$apiKey['provider']];
            }
        }

        // Get available models (only for providers with API keys)
        $availableModels = [];
        foreach ($availableProviders as $provider => $label) {
            if (isset($this->models[$provider])) {
                $availableModels[$provider] = $this->models[$provider];
            }
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'domain' => 'required',
                'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
                'model' => 'required',
                'api_key_id' => 'required',
                'system_prompt' => 'permit_empty|max_length[2000]'
            ];

            if ($this->validate($rules)) {
                // Verificar que el dominio pertenezca al tenant
                $domain = $this->request->getPost('domain');
                $domains = $this->tenantsModel->getDomains($tenant_id);
                $allowedDomains = array_column($domains, 'domain');

                if (!in_array($domain, $allowedDomains)) {
                    return redirect()->back()
                        ->with('error', 'El dominio seleccionado no está permitido para tu cuenta')
                        ->withInput();
                }

                // Verificar que el provider seleccionado tenga una API key configurada
                $provider = $this->request->getPost('provider');
                $api_key_id = $this->request->getPost('api_key_id');

                // Obtener la API key seleccionada
                $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
                    ->where('tenant_id', $tenant_id)
                    ->where('provider', $provider)
                    ->where('active', 1)
                    ->first();

                if (!$apiKey) {
                    return redirect()->back()
                        ->with('error', 'La API Key seleccionada no es válida o no pertenece al proveedor seleccionado')
                        ->withInput();
                }

                $updateData = [
                    'name' => $this->request->getPost('name'),
                    'domain' => $this->request->getPost('domain'),
                    'provider' => $this->request->getPost('provider'),
                    'model' => $this->request->getPost('model'),
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'api_key' => $apiKey['api_key']
                ];

                try {
                    $this->buttonsModel->where('button_id', $button_id)
                        ->where('tenant_id', $tenant_id)
                        ->set($updateData)
                        ->update();

                    return redirect()->to('/buttons')->with('success', 'Button updated successfully.');
                } catch (\Exception $e) {
                    log_message('error', 'Error updating button: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Failed to update button. Please try again.');
                }
            }
        }

        $apiKeyModel = new \App\Models\ApiKeyModel();
        $apiKeys = $apiKeyModel->where('tenant_id', session()->get('tenant_id'))->findAll();

        $data = [
            'title' => 'Edit Button',
            'button' => $button,
            'tenant' => $this->tenantsModel->find($tenant_id),
            'providers' => $availableProviders,
            'models' => $availableModels,
            'domains' => $this->tenantsModel->getDomains($tenant_id),
            'apiKeys' => $apiKeys
        ];

        return view('shared/buttons/edit', $data);
    }

    /**
     * Update an existing button
     */
    public function update($button_id = null)
    {
        if (!$button_id) {
            return redirect()->to('/buttons')->with('error', 'Button ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $button = $this->buttonsModel->where('button_id', $button_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'domain' => 'required',
            'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
            'model' => 'required',
            'api_key_id' => 'required',
            'system_prompt' => 'permit_empty|max_length[2000]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'Please check the form for errors.')
                ->withInput()
                ->with('validation', $this->validator);
        }

        // Verificar que el dominio pertenezca al tenant
        $domain = $this->request->getPost('domain');
        $domains = $this->tenantsModel->getDomains($tenant_id);
        $allowedDomains = array_column($domains, 'domain');

        if (!in_array($domain, $allowedDomains)) {
            return redirect()->back()
                ->with('error', 'El dominio seleccionado no está permitido para tu cuenta')
                ->withInput();
        }

        // Verificar que el provider seleccionado tenga una API key configurada
        $provider = $this->request->getPost('provider');
        $api_key_id = $this->request->getPost('api_key_id');

        // Obtener la API key seleccionada
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
            ->where('tenant_id', $tenant_id)
            ->where('provider', $provider)
            ->where('active', 1)
            ->first();

        if (!$apiKey) {
            return redirect()->back()
                ->with('error', 'La API Key seleccionada no es válida o no pertenece al proveedor seleccionado')
                ->withInput();
        }

        $updateData = [
            'name' => $this->request->getPost('name'),
            'domain' => $this->request->getPost('domain'),
            'provider' => $this->request->getPost('provider'),
            'model' => $this->request->getPost('model'),
            'system_prompt' => $this->request->getPost('system_prompt'),
            'api_key' => $apiKey['api_key'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->buttonsModel->where('button_id', $button_id)
                ->where('tenant_id', $tenant_id)
                ->set($updateData)
                ->update();

            return redirect()->to('/buttons')->with('success', 'Button updated successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error updating button: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update button. Please try again.');
        }
    }

    /**
     * Delete a button
     */
    public function delete($button_id = null)
    {
        if (!$button_id) {
            return redirect()->to('/buttons')->with('error', 'Button ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $button = $this->buttonsModel->where('button_id', $button_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        try {
            // Delete button and associated usage logs
            $this->db->transStart();

            // Delete usage logs first (foreign key constraint)
            $this->db->table('usage_logs')
                ->where('button_id', $button_id)
                ->delete();

            // Delete the button
            $this->buttonsModel->where('button_id', $button_id)
                ->where('tenant_id', $tenant_id)
                ->delete();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Failed to delete button and its usage logs.');
            }

            return redirect()->to('/buttons')->with('success', 'Button deleted successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error deleting button: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete button. Please try again.');
        }
    }
}
