<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ButtonsModel;
use App\Models\TenantsModel;

class Buttons extends Controller
{
    protected $db;
    protected $buttonsModel;
    protected $tenantsModel;
    protected $providers;
    protected $models;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->buttonsModel = new \App\Models\ButtonsModel();
        $this->tenantsModel = new \App\Models\TenantsModel();
        
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

        return view('buttons/index', $data);
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

        $data = [
            'title' => 'Create Button',
            'tenant' => $tenant
        ];

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'domain' => 'required|min_length[3]|max_length[255]',
                'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
                'model' => 'required',
                'api_key' => 'required'
            ];

            if ($this->validate($rules)) {
                try {
                    // Create button data
                    $buttonData = [
                        'tenant_id' => $tenant_id,
                        'name' => $this->request->getPost('name'),
                        'domain' => $this->request->getPost('domain'),
                        'provider' => $this->request->getPost('provider'),
                        'model' => $this->request->getPost('model'),
                        'api_key' => $this->request->getPost('api_key'),
                        'system_prompt' => $this->request->getPost('system_prompt'),
                        'active' => 1
                    ];

                    if ($this->buttonsModel->insert($buttonData)) {
                        return redirect()->to('/buttons')
                            ->with('success', 'Button created successfully');
                    }

                    // If we get here, there was a validation error
                    $errors = $this->buttonsModel->errors();
                    return redirect()->back()
                        ->with('error', 'Validation failed: ' . implode(', ', $errors))
                        ->withInput();

                } catch (\Exception $e) {
                    log_message('error', '[Button Creation] ' . $e->getMessage());
                    return redirect()->back()
                        ->with('error', 'Error creating button: ' . $e->getMessage())
                        ->withInput();
                }
            }

            // If we get here, controller validation failed
            return redirect()->back()
                ->with('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()))
                ->withInput();
        }

        // Get available providers and models for dropdown
        $data['providers'] = $this->providers;
        $data['models'] = $this->models;

        return view('buttons/create', $data);
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

        return view('buttons/view', $data);
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
                    $updateData['api_key'] = $newApiKey;
                }

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

        $data = [
            'title' => 'Edit Button',
            'button' => $button,
            'tenant' => $this->tenantsModel->find($tenant_id),
            'providers' => $this->providers,
            'models' => $this->models
        ];

        return view('buttons/edit', $data);
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
