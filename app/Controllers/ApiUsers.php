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
        helper('hash'); // Cargar el helper
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
                'daily_quota' => 10000, // Default daily quota
                'active' => 1
            ];

            // Log the data being inserted
            log_message('debug', '[ApiUsers::store] Inserting data: ' . json_encode($data));

            // Get the database connection
            $db = \Config\Database::connect();
            
            // Start transaction
            $db->transStart();

            try {
                // Insert manually
                $sql = "INSERT INTO api_users (user_id, external_id, tenant_id, name, email, quota, daily_quota, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
                
                // Log the SQL and parameters
                log_message('debug', '[ApiUsers::store] SQL: ' . $sql);
                log_message('debug', '[ApiUsers::store] Params: ' . json_encode([
                    $data['user_id'],
                    $data['external_id'],
                    $data['tenant_id'],
                    $data['name'],
                    $data['email'],
                    $data['quota'],
                    $data['daily_quota'],
                    $data['active']
                ]));

                $result = $db->query($sql, [
                    $data['user_id'],
                    $data['external_id'],
                    $data['tenant_id'],
                    $data['name'],
                    $data['email'],
                    $data['quota'],
                    $data['daily_quota'],
                    $data['active']
                ]);

                // Log the query result
                log_message('debug', '[ApiUsers::store] Query result: ' . json_encode($result));
                
                // Log any database errors
                if ($db->error()) {
                    log_message('error', '[ApiUsers::store] Database error: ' . json_encode($db->error()));
                }
            } catch (\Exception $e) {
                log_message('error', '[ApiUsers::store] Query error: ' . $e->getMessage());
                throw $e;
            }

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Failed to create API user - Transaction failed');
            }

            return redirect()->to('api-users')
                ->with('success', 'API user created successfully');
        } catch (\Exception $e) {
            log_message('error', '[ApiUsers::store] Error: ' . $e->getMessage());
            log_message('error', '[ApiUsers::store] Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create API user: ' . $e->getMessage());
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
