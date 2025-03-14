<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ButtonsModel;
use App\Models\TenantsModel;

class Buttons extends Controller
{
    protected $buttonsModel;
    protected $tenantsModel;

    public function __construct()
    {
        helper(['url', 'form', 'logger', 'api_key']);
        $this->buttonsModel = new ButtonsModel();
        $this->tenantsModel = new TenantsModel();
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
                'provider' => 'required',
                'model' => 'required',
            ];

            if ($this->validate($rules)) {
                // Create button data
                $buttonData = [
                    'tenant_id' => $tenant_id,
                    'name' => $this->request->getPost('name'),
                    'provider' => $this->request->getPost('provider'),
                    'model' => $this->request->getPost('model'),
                    'api_key' => $this->request->getPost('api_key'),
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'active' => 1
                ];

                if ($this->buttonsModel->insert($buttonData)) {
                    return redirect()->to('/buttons')
                        ->with('success', 'Button created successfully');
                } else {
                    return redirect()->back()
                        ->with('error', 'Error creating button')
                        ->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        // Get available providers and models for dropdown
        $data['providers'] = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
            'deepseek' => 'DeepSeek',
            'google' => 'Google Gemini'
        ];

        $data['models'] = [
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
            ],
        ];

        return view('buttons/create', $data);
    }

    /**
     * Edit an existing button
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

        $data = [
            'title' => 'Edit Button',
            'tenant' => $tenant,
            'button' => $this->buttonsModel->where('tenant_id', $tenant_id)
                ->find($id)
        ];

        if (empty($data['button'])) {
            return redirect()->to('/buttons')
                ->with('error', 'Button not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'domain' => 'required|min_length[3]|max_length[255]',
                'provider' => 'required',
                'model' => 'required',
            ];

            if ($this->validate($rules)) {
                $domain = $this->request->getPost('domain');

                // Check if domain already exists for this tenant (excluding this button)
                if ($this->buttonsModel->domainExists($domain, $tenant_id, $id)) {
                    return redirect()->back()
                        ->with('error', 'A button with this domain already exists for this tenant')
                        ->withInput();
                }

                $buttonData = [
                    'name' => $this->request->getPost('name'),
                    'domain' => $domain,
                    'provider' => $this->request->getPost('provider'),
                    'model' => $this->request->getPost('model'),
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Handle API key updates
                $api_key = $this->request->getPost('api_key');
                if (!empty($api_key)) {
                    if (strtolower($api_key) === 'delete') {
                        $buttonData['api_key'] = null;
                    } else {
                        $buttonData['api_key'] = $api_key;
                    }
                }

                if ($this->buttonsModel->update($id, $buttonData)) {
                    return redirect()->to('/buttons')
                        ->with('success', 'Button updated successfully');
                } else {
                    return redirect()->back()
                        ->with('error', 'Error updating button')
                        ->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        // Get available providers and models for dropdown
        $data['providers'] = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
            'deepseek' => 'DeepSeek',
            'google' => 'Google Gemini'
        ];

        $data['models'] = [
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
            ],
        ];

        return view('buttons/edit', $data);
    }

    /**
     * Delete a button
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

        // Get tenant information
        $tenant = $this->tenantsModel->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        if (!$tenant) {
            return redirect()->to('/auth/login')
                ->with('error', 'Tenant not found');
        }

        // Check if button exists and belongs to this tenant
        $button = $this->buttonsModel->where('tenant_id', $tenant_id)
            ->find($id);

        if (!$button) {
            return redirect()->to('/buttons')
                ->with('error', 'Button not found');
        }

        if ($this->buttonsModel->delete($id)) {
            return redirect()->to('/buttons')
                ->with('success', 'Button deleted successfully');
        }

        return redirect()->to('/buttons')
            ->with('error', 'Error deleting button');
    }

    /**
     * View button details
     */
    public function view($button_id)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $tenant_id = session()->get('tenant_id');
        $data['title'] = 'Button Details';
        $data['button'] = $this->buttonsModel->where('tenant_id', $tenant_id)->find($button_id);

        if (empty($data['button'])) {
            return redirect()->to('/buttons')->with('error', 'Button not found');
        }

        return view('buttons/view', $data);
    }
}
