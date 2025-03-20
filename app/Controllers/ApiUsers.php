<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ApiUsers extends BaseController
{
    protected $apiUsersModel;
    protected $tenantsModel;

    public function __construct()
    {
        $this->apiUsersModel = model('App\Models\ApiUsersModel');
        $this->tenantsModel = model('App\Models\TenantsModel');
    }

    public function index()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $data = [
            'title' => 'API Users',
            'users' => $this->apiUsersModel->getApiUsersByTenant($tenant_id)
        ];

        return view('shared/api_users/index', $data);
    }

    public function create()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        // Check if tenant can create more API users
        if (!$this->tenantsModel->canCreateApiUser($tenant_id)) {
            return redirect()->to('/api-users')->with('error', 'Maximum number of API users reached');
        }

        $data = [
            'title' => 'Create API User',
            'tenant_id' => $tenant_id
        ];

        return view('shared/api_users/create', $data);
    }

    public function store()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        log_message('debug', '[ApiUsers::store] Starting API user creation. Session tenant_id: ' . $tenant_id);

        // Get form data
        $formData = $this->request->getPost();
        log_message('debug', '[ApiUsers::store] Form data received: ' . json_encode($formData));

        // Validation rules
        $rules = [
            'tenant_id' => 'required',
            'external_id' => 'required|max_length[255]|is_unique[api_users.external_id,tenant_id,{tenant_id}]',
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'permit_empty|valid_email',
            'quota' => 'required|integer|greater_than[0]'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', '[ApiUsers::store] Validation failed: ' . json_encode($errors));
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $errors);
        }

        // Prepare data for insertion
        $data = [
            'tenant_id' => $tenant_id,
            'external_id' => $this->request->getPost('external_id'),
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'quota' => $this->request->getPost('quota'),
            'daily_quota' => $this->request->getPost('daily_quota') ?? 10000,
            'active' => 1
        ];

        log_message('debug', '[ApiUsers::store] Attempting to insert API user with data: ' . json_encode($data));

        try {
            $user_id = $this->apiUsersModel->insert($data);
            if ($user_id) {
                log_message('info', '[ApiUsers::store] API user created successfully with user_id: ' . $user_id);
                return redirect()->to('/api-users')->with('success', 'API user created successfully');
            }
            
            log_message('error', '[ApiUsers::store] Failed to insert API user. DB Error: ' . json_encode($this->apiUsersModel->errors()));
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to create API user');
        } catch (\Exception $e) {
            log_message('error', '[ApiUsers::store] Exception occurred: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to create API user');
        }
    }

    public function view($id)
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->getApiUserById($id);
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        // Get usage statistics
        $usage = [
            'total_requests' => 0,
            'total_tokens' => 0,
            'avg_tokens_per_request' => 0,
            'daily_usage' => [],
            'monthly_usage' => []
        ];

        // Add usage data to user array
        $user['usage'] = $usage;

        $data = [
            'title' => 'View API User',
            'user' => $user
        ];

        return view('shared/api_users/view', $data);
    }

    public function edit($id)
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->getApiUserById($id);
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        $data = [
            'title' => 'Edit API User',
            'user' => $user
        ];

        return view('shared/api_users/edit', $data);
    }

    public function update($id)
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->getApiUserById($id);
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        // Validation rules
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'permit_empty|valid_email',
            'quota' => 'required|integer|greater_than[0]',
            'daily_quota' => 'required|integer|greater_than[0]',
            'active' => 'required|in_list[0,1]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'quota' => $this->request->getPost('quota'),
            'daily_quota' => $this->request->getPost('daily_quota'),
            'active' => $this->request->getPost('active')
        ];

        if ($this->apiUsersModel->update($id, $data)) {
            return redirect()->to('/api-users')->with('success', 'API user updated successfully');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to update API user');
    }

    public function delete($id)
    {
        if (!$this->request->isAJAX() && !$this->request->getMethod() === 'post') {
            return redirect()->back()->with('error', 'Invalid request method');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->getApiUserById($id);
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        try {
            if ($this->apiUsersModel->delete($id)) {
                return redirect()->to('/api-users')->with('success', 'API user deleted successfully');
            }
            return redirect()->to('/api-users')->with('error', 'Failed to delete API user');
        } catch (\Exception $e) {
            log_message('error', '[ApiUsers::delete] Exception occurred: ' . $e->getMessage());
            return redirect()->to('/api-users')->with('error', 'Failed to delete API user');
        }
    }
}
