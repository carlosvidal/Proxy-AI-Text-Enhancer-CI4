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
     * List all buttons for a tenant
     */
    public function index($tenant_id)
    {
        $data['title'] = 'Buttons Management';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        $data['buttons'] = $this->buttonsModel->getButtonsByTenant($data['tenant']['tenant_id']);

        return view('buttons/index', $data);
    }

    /**
     * Create a new button
     */
    public function create($tenant_id)
    {
        $data['title'] = 'Create Button';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
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

                // Check if domain already exists for this tenant
                if ($this->buttonsModel->domainExists($domain, $data['tenant']['tenant_id'])) {
                    return redirect()->back()
                        ->with('error', 'A button with this domain already exists for this tenant')
                        ->withInput();
                }

                // Get API key if provided
                $api_key = $this->request->getPost('api_key');

                // Generate a unique button_id
                $button_id = $this->buttonsModel->generateButtonId();

                $buttonData = [
                    'tenant_id' => $data['tenant']['tenant_id'],
                    'button_id' => $button_id,
                    'name' => $this->request->getPost('name'),
                    'domain' => $domain,
                    'provider' => $this->request->getPost('provider'),
                    'model' => $this->request->getPost('model'),
                    'api_key' => $api_key,
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if ($this->buttonsModel->insert($buttonData)) {
                    return redirect()->to('/buttons/' . $tenant_id)
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
    public function edit($button_id)
    {
        $data['title'] = 'Edit Button';
        $data['button'] = $this->buttonsModel->find($button_id);

        if (empty($data['button'])) {
            return redirect()->to('/tenants')->with('error', 'Button not found');
        }

        $data['tenant'] = $this->tenantsModel->where('tenant_id', $data['button']['tenant_id'])->first();

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
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
                if ($this->buttonsModel->domainExists($domain, $data['button']['tenant_id'], $button_id)) {
                    return redirect()->back()
                        ->with('error', 'A button with this domain already exists for this tenant')
                        ->withInput();
                }

                // Handle API key updates
                $api_key = $data['button']['api_key'];
                $new_api_key = $this->request->getPost('api_key');

                // If a new API key is provided
                if (!empty($new_api_key)) {
                    // Check for "delete" keyword to remove the API key
                    if (strtolower($new_api_key) === 'delete') {
                        $api_key = null;
                    } else {
                        $api_key = $new_api_key;
                    }
                }

                $buttonData = [
                    'name' => $this->request->getPost('name'),
                    'domain' => $domain,
                    'provider' => $this->request->getPost('provider'),
                    'model' => $this->request->getPost('model'),
                    'api_key' => $api_key,
                    'system_prompt' => $this->request->getPost('system_prompt'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->buttonsModel->update($button_id, $buttonData)) {
                    return redirect()->to('/buttons/' . $data['tenant']['id'])
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
    public function delete($button_id)
    {
        $button = $this->buttonsModel->find($button_id);

        if (empty($button)) {
            return redirect()->to('/tenants')->with('error', 'Button not found');
        }

        $tenant = $this->tenantsModel->where('tenant_id', $button['tenant_id'])->first();

        if (empty($tenant)) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        if ($this->buttonsModel->delete($button_id)) {
            return redirect()->to('/buttons/' . $tenant['id'])
                ->with('success', 'Button deleted successfully');
        } else {
            return redirect()->to('/buttons/' . $tenant['id'])
                ->with('error', 'Error deleting button');
        }
    }

    /**
     * View button details
     */
    public function view($button_id)
    {
        $data['title'] = 'Button Details';
        $data['button'] = $this->buttonsModel->find($button_id);

        if (empty($data['button'])) {
            return redirect()->to('/tenants')->with('error', 'Button not found');
        }

        $data['tenant'] = $this->tenantsModel->where('tenant_id', $data['button']['tenant_id'])->first();

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        return view('buttons/view', $data);
    }
}
