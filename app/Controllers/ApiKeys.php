<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ApiKeysModel;
use App\Models\TenantsModel;

class ApiKeys extends BaseController
{
    protected $apiKeysModel;
    protected $tenantsModel;
    protected $providers = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'cohere' => 'Cohere',
        'mistral' => 'Mistral',
        'deepseek' => 'DeepSeek',
        'google' => 'Google'
    ];

    public function __construct()
    {
        $this->apiKeysModel = new ApiKeysModel();
        $this->tenantsModel = new TenantsModel();
    }

    public function index()
    {
        $tenant_id = session()->get('tenant_id');
        $tenant = $this->tenantsModel->find($tenant_id);
        
        $data = [
            'tenant' => $tenant,
            'apiKeys' => $this->apiKeysModel->getTenantApiKeys($tenant_id),
            'providers' => $this->providers
        ];
        
        return view('shared/api_keys/index', $data);
    }

    public function store()
    {
        $tenant_id = session()->get('tenant_id');
        $tenant = $this->tenantsModel->find($tenant_id);

        // Verificar límite de API keys según el plan
        $current_keys = $this->apiKeysModel->getTenantApiKeys($tenant_id);
        $maxApiKeys = $tenant['max_api_keys'] ?? 1; // Default to 1 for free plan
        
        if (count($current_keys) >= $maxApiKeys) {
            return redirect()->to('/api-keys')
                ->with('error', "Has alcanzado el límite de {$maxApiKeys} API Keys para tu plan. Actualiza tu plan para agregar más.");
        }

        helper('hash');

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

        // Generate a unique API key ID
        $api_key_id = generate_hash_id('key');

        $data = [
            'api_key_id' => $api_key_id,
            'tenant_id' => $tenant_id,
            'name' => $this->request->getPost('name'),
            'provider' => $this->request->getPost('provider'),
            'api_key' => $this->request->getPost('api_key'), // Let the model handle encryption
            'is_default' => count($current_keys) === 0 ? 1 : 0, // Set as default if it's the first key
            'active' => 1
        ];

        if ($this->apiKeysModel->insert($data)) {
            return redirect()->to('/api-keys')
                ->with('success', 'API Key agregada correctamente.');
        }

        return redirect()->to('/api-keys')
            ->withInput()
            ->with('error', 'Error al agregar la API Key: ' . implode('<br>', $this->apiKeysModel->errors()));
    }

    public function delete($api_key_id)
    {
        $tenant_id = session()->get('tenant_id');
        
        // Verify the API key belongs to the tenant
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
                                   ->where('tenant_id', $tenant_id)
                                   ->first();
        
        if (!$apiKey) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key not found.');
        }

        // Check if this is the last API key
        $remaining_keys = $this->apiKeysModel->where('tenant_id', $tenant_id)
                                           ->where('api_key_id !=', $api_key_id)
                                           ->findAll();

        // If this is the default key and there are other keys, set another one as default
        if ($apiKey['is_default'] && !empty($remaining_keys)) {
            $this->apiKeysModel->setDefault($remaining_keys[0]['api_key_id'], $tenant_id);
        }

        if ($this->apiKeysModel->delete($api_key_id)) {
            return redirect()->to('/api-keys')
                ->with('success', 'API Key eliminada correctamente.');
        }

        return redirect()->to('/api-keys')
            ->with('error', 'Error al eliminar la API Key.');
    }

    public function setDefault($api_key_id)
    {
        $tenant_id = session()->get('tenant_id');
        
        // Verify the API key belongs to the tenant
        $apiKey = $this->apiKeysModel->where('api_key_id', $api_key_id)
                                   ->where('tenant_id', $tenant_id)
                                   ->first();

        if (!$apiKey) {
            return redirect()->to('/api-keys')
                ->with('error', 'API Key not found.');
        }

        if ($this->apiKeysModel->setDefault($api_key_id, $tenant_id)) {
            return redirect()->to('/api-keys')
                ->with('success', 'API Key establecida como predeterminada.');
        }

        return redirect()->to('/api-keys')
            ->with('error', 'Error al establecer la API Key como predeterminada.');
    }
}
