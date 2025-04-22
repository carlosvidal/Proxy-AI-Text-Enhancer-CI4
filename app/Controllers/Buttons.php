<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ButtonsModel;
use App\Models\TenantsModel;
use App\Models\ApiKeysModel;
use App\Models\DomainsModel;

class Buttons extends Controller
{
    protected $db;
    protected $buttonsModel;
    protected $tenantsModel;
    protected $apiKeysModel;
    protected $domainsModel;
    protected $providers;
    protected $models;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->buttonsModel = new ButtonsModel();
        $this->tenantsModel = new TenantsModel();
        $this->apiKeysModel = new ApiKeysModel();
        $this->domainsModel = new DomainsModel();

        // Define available providers
        $this->providers = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
            'deepseek' => 'DeepSeek',
            'google' => 'Google Gemini'
        ];

        // Define available models per provider (actualizado a abril 2025)
        $this->models = [
            'openai' => [
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-4o' => 'GPT-4o (Omni)',
                'gpt-4-vision-preview' => 'GPT-4 Vision Preview',
                'gpt-4' => 'GPT-4 (Legacy)',
            ],
            'anthropic' => [
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            ],
            'mistral' => [
                'mistral-tiny' => 'Mistral Tiny',
                'mistral-small' => 'Mistral Small',
                'mistral-medium' => 'Mistral Medium',
                'mistral-large' => 'Mistral Large',
                'mistral-next' => 'Mistral Next',
            ],
            'cohere' => [
                'command' => 'Command',
                'command-light' => 'Command Light',
                'command-r' => 'Command-R',
                'command-r-plus' => 'Command-R Plus',
            ],
            'deepseek' => [
                'deepseek-coder' => 'DeepSeek Coder',
                'deepseek-coder-v2' => 'DeepSeek Coder v2',
                'deepseek-chat' => 'DeepSeek Chat',
            ],
            'google' => [
                'gemini-1.5-pro-latest' => 'Gemini 1.5 Pro (Latest)',
                'gemini-1.0-pro' => 'Gemini 1.0 Pro',
                'gemini-1.0-pro-vision' => 'Gemini 1.0 Pro Vision',
                'gemini-pro' => 'Gemini Pro (Legacy)',
            ]
        ];
    }

    /**
     * List all buttons for the current tenant
     */
    public function index()
    {
        $tenant_id = session()->get('tenant_id');
        $buttons = $this->buttonsModel->getButtonsWithStatsByTenant($tenant_id);

        return view('shared/buttons/index', [
            'title' => 'Buttons',
            'buttons' => $buttons,
            'tenant' => $this->tenantsModel->find($tenant_id)
        ]);
    }

    /**
     * Show the form for creating a new button
     */
    public function create()
    {
        $tenant_id = session()->get('tenant_id');
        $tenant = $this->tenantsModel->find($tenant_id);

        // Get available API keys for the tenant
        $apiKeys = $this->apiKeysModel->getTenantApiKeys($tenant_id);

        // Filter providers based on available API keys
        $availableProviders = [];
        foreach ($apiKeys as $key) {
            if (isset($this->providers[$key['provider']])) {
                $availableProviders[$key['provider']] = $this->providers[$key['provider']];
            }
        }

        // Get models for available providers
        $availableModels = [];
        foreach (array_keys($availableProviders) as $provider) {
            if (isset($this->models[$provider])) {
                $availableModels[$provider] = $this->models[$provider];
            }
        }

        return view('shared/buttons/create', [
            'title' => 'Create Button',
            'tenant' => $tenant,
            'providers' => $availableProviders,
            'models' => $availableModels,
            'apiKeys' => $apiKeys
        ]);
    }

    /**
     * Store a new button
     */
    public function store()
    {
        helper('hash');
        $tenant_id = session()->get('tenant_id');

        // Obtener el API key seleccionado
        $api_key_id = $this->request->getPost('api_key_id');
        $apiKey = $this->apiKeysModel->where('tenant_id', $tenant_id)
            ->where('api_key_id', $api_key_id)
            ->first();

        if (!$apiKey) {
            return redirect()->back()->withInput()->with('error', 'Invalid API key selected.');
        }

        $provider = $apiKey['provider'];
        $model = $this->request->getPost('model');

        // Validar modelo para el provider
        if (!isset($this->models[$provider][$model])) {
            return redirect()->back()->withInput()->with('error', 'Invalid model selected for the provider.');
        }

        $data = [
            'tenant_id' => $tenant_id,
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'domain' => $this->request->getPost('domain'),
            'provider' => $provider,
            'model' => $model,
            'prompt' => $this->request->getPost('prompt'),
            'api_key_id' => $api_key_id,
            'status' => 'active'
        ];

        try {
            log_message('debug', 'Creating button with data: ' . json_encode($data));
            $result = $this->buttonsModel->insert($data);
            log_message('debug', 'Button created with ID: ' . $result);
            return redirect()->to('/buttons')->with('success', 'Button created successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error creating button: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create button. Please try again.');
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
        $button = $this->buttonsModel->getButtonWithDetails($button_id, $tenant_id);

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        // Get API key for the button
        $apiKey = $this->apiKeysModel->where('tenant_id', $tenant_id)
            ->where('api_key_id', $button['api_key_id'])
            ->first();

        // If no custom API key, get default one for provider
        if (!$apiKey) {
            $apiKey = $this->apiKeysModel->where('tenant_id', $tenant_id)
                ->where('provider', $button['provider'])
                ->where('is_default', 1)
                ->first();
        }

        return view('shared/buttons/view', [
            'title' => 'View Button',
            'button' => $button,
            'tenant' => $this->tenantsModel->find($tenant_id),
            'provider_name' => $this->providers[$button['provider']] ?? $button['provider'],
            'model_name' => $this->models[$button['provider']][$button['model']] ?? $button['model'],
            'api_key' => $apiKey,
            'providers' => $this->providers
        ]);
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
        $button = $this->buttonsModel->getButtonWithDetails($button_id, $tenant_id);

        if (!$button) {
            return redirect()->to('/buttons')->with('error', 'Button not found.');
        }

        $tenant = $this->tenantsModel->find($tenant_id);

        // Get available API keys for the tenant
        $apiKeys = $this->apiKeysModel->getTenantApiKeys($tenant_id);

        // Get tenant domains
        $domains = $this->domainsModel->where('tenant_id', $tenant_id)->findAll();

        // Filter providers based on available API keys
        $availableProviders = [];
        foreach ($apiKeys as $key) {
            if (isset($this->providers[$key['provider']])) {
                $availableProviders[$key['provider']] = $this->providers[$key['provider']];
            }
        }

        // Get models for available providers
        $availableModels = [];
        foreach (array_keys($availableProviders) as $provider) {
            if (isset($this->models[$provider])) {
                $availableModels[$provider] = $this->models[$provider];
            }
        }

        return view('shared/buttons/edit', [
            'title' => 'Edit Button',
            'button' => $button,
            'tenant' => $tenant,
            'providers' => $availableProviders,
            'models' => $availableModels,
            'apiKeys' => $apiKeys,
            'domains' => $domains
        ]);
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

        // Obtener el API key seleccionado
        $api_key_id = $this->request->getPost('api_key_id');
        $apiKey = $this->apiKeysModel->where('tenant_id', $tenant_id)
            ->where('api_key_id', $api_key_id)
            ->first();

        if (!$apiKey) {
            return redirect()->back()->withInput()->with('error', 'Invalid API key selected.');
        }

        $provider = $apiKey['provider'];
        $model = $this->request->getPost('model');

        // Validar que el modelo exista para el provider del API key
        if (!isset($this->models[$provider][$model])) {
            return redirect()->back()->withInput()->with('error', 'Invalid model selected for the provider.');
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'domain' => $this->request->getPost('domain'),
            'provider' => $provider,
            'model' => $model,
            'prompt' => $this->request->getPost('prompt'),
            'status' => 'active'
        ];

        try {
            $this->buttonsModel->update($button['id'], $data);
            return redirect()->to('/buttons')->with('success', 'Button updated successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error updating button: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update button. Please try again.');
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
            $this->buttonsModel->where('button_id', $button_id)->delete();
            return redirect()->to('/buttons')->with('success', 'Button deleted successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error deleting button: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete button. Please try again.');
        }
    }
}
