<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\TenantsModel;
use App\Models\TenantApiKeysModel;

class ApiKeys extends BaseController
{
    protected $tenantsModel;
    protected $apiKeysModel;
    protected $providers = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'cohere' => 'Cohere',
        'mistral' => 'Mistral AI',
        'deepseek' => 'DeepSeek',
        'google' => 'Google AI'
    ];

    public function __construct()
    {
        $this->tenantsModel = new TenantsModel();
        $this->apiKeysModel = new TenantApiKeysModel();
        
        helper(['form', 'url', 'hash', 'api_key']);
    }

    /**
     * List all API keys for the current tenant
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
            'title' => 'API Keys',
            'tenant' => $tenant,
            'apiKeys' => $this->apiKeysModel->getTenantApiKeys($tenant_id),
            'providers' => $this->providers
        ];

        return view('shared/api_keys/index', $data);
    }

    /**
     * Create a new API key
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

        // Check if tenant has reached their API key limit
        $apiKeyCount = $this->apiKeysModel->countTenantApiKeys($tenant_id);
        $maxApiKeys = isset($tenant['max_api_keys']) ? $tenant['max_api_keys'] : 1;
        
        if ($apiKeyCount >= $maxApiKeys) {
            return redirect()->to('/api-keys')
                ->with('error', "Has alcanzado el límite de {$maxApiKeys} API Keys para tu plan. Actualiza tu plan para agregar más.");
        }

        $data = [
            'title' => 'Create API Key',
            'tenant' => $tenant,
            'providers' => $this->providers
        ];

        return view('shared/api_keys/create', $data);
    }

    /**
     * Store a new API key
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

        // Check if tenant has reached their API key limit
        $apiKeyCount = $this->apiKeysModel->countTenantApiKeys($tenant_id);
        $maxApiKeys = isset($tenant['max_api_keys']) ? $tenant['max_api_keys'] : 1;
        
        if ($apiKeyCount >= $maxApiKeys) {
            return redirect()->to('/api-keys')
                ->with('error', "Has alcanzado el límite de {$maxApiKeys} API Keys para tu plan. Actualiza tu plan para agregar más.");
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
            'api_key' => 'required|min_length[10]|max_length[255]',
            'is_default' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'Por favor, verifica los errores en el formulario.')
                ->withInput()
                ->with('validation', $this->validator);
        }

        // Generate a unique API key ID
        $api_key_id = $this->apiKeysModel->generateApiKeyId();

        // Encrypt the API key
        $encrypter = \Config\Services::encrypter();
        $encryptedKey = base64_encode($encrypter->encrypt($this->request->getPost('api_key')));

        // Check if this is the first API key for this provider, make it default
        $isDefault = $this->request->getPost('is_default') ? 1 : 0;
        $existingKeys = $this->apiKeysModel->getTenantProviderApiKeys($tenant_id, $this->request->getPost('provider'));
        if (empty($existingKeys)) {
            $isDefault = 1; // First key for this provider is automatically default
        }

        $data = [
            'api_key_id' => $api_key_id,
            'tenant_id' => $tenant_id,
            'name' => $this->request->getPost('name'),
            'provider' => $this->request->getPost('provider'),
            'api_key' => $encryptedKey,
            'is_default' => $isDefault,
            'active' => 1
        ];

        try {
            $this->apiKeysModel->insert($data);

            // If this key is set as default, unset any other default for this provider
            if ($isDefault) {
                $this->apiKeysModel->setAsDefault($api_key_id, $tenant_id, $this->request->getPost('provider'));
            }

            return redirect()->to('/api-keys')
                ->with('success', 'API Key creada correctamente');
        } catch (\Exception $e) {
            log_message('error', 'Error creating API key: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear la API Key. Por favor, intenta de nuevo.')
                ->withInput();
        }
    }

    /**
     * Set an API key as default
     */
    public function setDefault($api_key_id = null)
    {
        if (!$api_key_id) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
                                   ->where('tenant_id', $tenant_id)
                                   ->first();

        if (!$apiKey) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key not found.');
        }

        try {
            $this->apiKeysModel->setAsDefault($api_key_id, $tenant_id, $apiKey['provider']);
            return redirect()->to('/api-keys')
                ->with('success', 'API Key establecida como predeterminada.');
        } catch (\Exception $e) {
            log_message('error', 'Error setting API key as default: ' . $e->getMessage());
            return redirect()->to('/api-keys')
                ->with('error', 'Error al establecer la API Key como predeterminada.');
        }
    }

    /**
     * Delete an API key
     */
    public function delete($api_key_id = null)
    {
        if (!$api_key_id) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key ID is required.');
        }

        $tenant_id = session()->get('tenant_id');
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
                                   ->where('tenant_id', $tenant_id)
                                   ->first();

        if (!$apiKey) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key not found.');
        }

        try {
            // If this is the default key, find another key to set as default
            if ($apiKey['is_default']) {
                $otherKey = $this->apiKeysModel->where('tenant_id', $tenant_id)
                                             ->where('provider', $apiKey['provider'])
                                             ->where('api_key_id !=', $api_key_id)
                                             ->where('active', 1)
                                             ->first();
                
                if ($otherKey) {
                    $this->apiKeysModel->setAsDefault($otherKey['api_key_id'], $tenant_id, $apiKey['provider']);
                }
            }

            // Delete the API key
            $this->apiKeysModel->where('api_key_id', $api_key_id)
                             ->where('tenant_id', $tenant_id)
                             ->delete();

            return redirect()->to('/api-keys')
                ->with('success', 'API Key eliminada correctamente.');
        } catch (\Exception $e) {
            log_message('error', 'Error deleting API key: ' . $e->getMessage());
            return redirect()->to('/api-keys')
                ->with('error', 'Error al eliminar la API Key.');
        }
    }
}
