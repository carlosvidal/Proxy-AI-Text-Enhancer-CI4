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

        // If admin, get list of tenants
        $data = [
            'title' => 'Create API User',
            'tenant_id' => $tenant_id
        ];

        if (session()->get('is_admin')) {
            $data['tenants'] = $this->tenantsModel->findAll();
        }

        return view('shared/api_users/create', $data);
    }

    public function store()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        // Validate input
        $rules = [
            'external_id' => 'required|min_length[3]|max_length[255]|is_unique[api_users.external_id,tenant_id,'.$tenant_id.']',
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'permit_empty|valid_email|max_length[255]',
            'quota' => 'required|is_natural_no_zero'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $user_id = generate_hash_id('usr');
            
            $data = [
                'user_id' => $user_id,
                'external_id' => $this->request->getPost('external_id'),
                'tenant_id' => $tenant_id,
                'name' => $this->request->getPost('name'),
                'email' => $this->request->getPost('email'),
                'quota' => $this->request->getPost('quota'),
                'active' => 1,
                'role' => 'user'
            ];

            if (!$this->apiUsersModel->insert($data)) {
                throw new \Exception('Failed to create API user');
            }

            return redirect()->to('api-users')
                ->with('success', 'API user created successfully');
        } catch (\Exception $e) {
            log_message('error', '[ApiUsers::store] Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create API user. Please try again.');
        }
    }

    public function view($id = null)
    {
        if (!$id) {
            return redirect()->to('api-users')->with('error', 'No API user specified');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->getApiUserById($id);
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('api-users')->with('error', 'API user not found');
        }

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
