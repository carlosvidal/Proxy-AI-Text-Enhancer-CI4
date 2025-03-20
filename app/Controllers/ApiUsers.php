<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ApiUsersModel;
use App\Models\TenantsModel;
use App\Models\UsageLogsModel;

class ApiUsers extends BaseController
{
    protected $apiUsersModel;
    protected $tenantsModel;
    protected $usageLogsModel;

    public function __construct()
    {
        $this->apiUsersModel = model('App\Models\ApiUsersModel');
        $this->tenantsModel = model('App\Models\TenantsModel');
        $this->usageLogsModel = model('App\Models\UsageLogsModel');
        helper('hash'); // Cargar el helper
    }

    public function index()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        // Obtener usuarios del tenant
        $users = $this->apiUsersModel->getApiUsersByTenant($tenant_id);

        // Obtener el primer día del mes actual
        $start_of_month = date('Y-m-01 00:00:00');

        // Obtener todos los botones activos del tenant una sola vez
        $db = \Config\Database::connect();
        $tenant_buttons = $db->table('buttons')
            ->where('tenant_id', $tenant_id)
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        foreach ($users as &$user) {
            // Obtener uso mensual
            $monthly_stats = $this->usageLogsModel
                ->select('COALESCE(SUM(tokens), 0) as total_tokens')
                ->where('external_id', $user['external_id'])
                ->where('created_at >=', $start_of_month)
                ->first();
            
            $user['monthly_usage'] = (int)$monthly_stats['total_tokens'];

            // Obtener estadísticas totales
            $total_stats = $this->usageLogsModel
                ->select([
                    'COUNT(*) as total_requests',
                    'COALESCE(SUM(tokens), 0) as total_tokens',
                    'COALESCE(AVG(tokens), 0) as avg_tokens_per_request'
                ])
                ->where('external_id', $user['external_id'])
                ->first();

            $user['usage'] = [
                'total_tokens' => (int)$total_stats['total_tokens'],
                'total_requests' => (int)$total_stats['total_requests'],
                'avg_tokens_per_request' => round((float)$total_stats['avg_tokens_per_request'], 1)
            ];

            // Asignar los botones del tenant
            $user['buttons'] = $tenant_buttons;
        }

        $data = [
            'title' => lang('App.api_users_title'),
            'users' => $users
        ];

        return view('shared/api_users/index', $data);
    }

    public function create()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $data = [
            'title' => lang('App.api_users_create')
        ];

        return view('shared/api_users/create', $data);
    }

    public function store()
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        // Validar campos
        $rules = [
            'external_id' => 'required|min_length[3]|max_length[255]',
            'quota' => 'required|integer|greater_than[0]',
            'daily_quota' => 'required|integer|greater_than[0]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        // Crear usuario
        $data = [
            'tenant_id' => $tenant_id,
            'user_id' => 'usr-' . bin2hex(random_bytes(8)),
            'external_id' => $this->request->getPost('external_id'),
            'quota' => $this->request->getPost('quota'),
            'daily_quota' => $this->request->getPost('daily_quota'),
            'active' => (bool)$this->request->getPost('active'),
        ];

        if ($this->apiUsersModel->insert($data)) {
            return redirect()->to('/api-users')
                ->with('success', lang('App.api_users_created'));
        }

        return redirect()->back()
            ->withInput()
            ->with('error', lang('App.api_users_create_error'));
    }

    public function view($external_id = null)
    {
        if (!$external_id) {
            return redirect()->to('/api-users')->with('error', 'No API user specified');
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        $user = $this->apiUsersModel->where('external_id', $external_id)->first();
        if (!$user || $user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        // Get usage statistics
        $monthly_usage = [];
        $daily_usage = [];

        // Get total usage statistics
        $total_stats = $this->usageLogsModel
            ->select([
                'COUNT(*) as total_requests',
                'COALESCE(SUM(tokens), 0) as total_tokens',
                'COALESCE(AVG(tokens), 0) as avg_tokens_per_request'
            ])
            ->where('external_id', $external_id)
            ->first();

        // Get monthly usage for the last 6 months
        $start_date = date('Y-m-d H:i:s', strtotime('-6 months'));
        $monthly_stats = $this->usageLogsModel
            ->select("strftime('%Y-%m', created_at) as month, SUM(tokens) as total_tokens")
            ->where('external_id', $external_id)
            ->where('created_at >=', $start_date)
            ->groupBy("strftime('%Y-%m', created_at)")
            ->orderBy("month", "ASC")
            ->findAll();

        // Format monthly data
        foreach ($monthly_stats as $stat) {
            $month = date('M Y', strtotime($stat['month'] . '-01'));
            $monthly_usage[$month] = (int)$stat['total_tokens'];
        }

        // Get daily usage for the last 30 days
        $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $daily_stats = $this->usageLogsModel
            ->select("strftime('%Y-%m-%d', created_at) as date, SUM(tokens) as total_tokens")
            ->where('external_id', $external_id)
            ->where('created_at >=', $start_date)
            ->groupBy("strftime('%Y-%m-%d', created_at)")
            ->orderBy("date", "ASC")
            ->findAll();

        // Format daily data
        foreach ($daily_stats as $stat) {
            $date = date('d M', strtotime($stat['date']));
            $daily_usage[$date] = (int)$stat['total_tokens'];
        }

        // Add usage data to user array
        $user['usage'] = [
            'monthly_usage' => $monthly_usage,
            'daily_usage' => $daily_usage,
            'total_tokens' => (int)$total_stats['total_tokens'],
            'total_requests' => (int)$total_stats['total_requests'],
            'avg_tokens_per_request' => round((float)$total_stats['avg_tokens_per_request'], 1)
        ];

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

        // Buscar el usuario por user_id
        $api_user = $this->apiUsersModel->where('user_id', $id)->first();
        
        if (!$api_user || $api_user['tenant_id'] !== $tenant_id) {
            return redirect()->to('/api-users')->with('error', 'API user not found');
        }

        $data = [
            'title' => 'Edit API User',
            'api_user' => $api_user
        ];

        return view('shared/api_users/edit', $data);
    }

    public function update($user_id)
    {
        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return redirect()->to('/dashboard')->with('error', 'No tenant selected');
        }

        // Validar que el usuario pertenezca al tenant
        $user = $this->apiUsersModel->where('user_id', $user_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$user) {
            return redirect()->to('/api-users')->with('error', lang('App.api_users_not_found'));
        }

        // Validar campos
        $rules = [
            'external_id' => 'required|min_length[3]|max_length[255]',
            'quota' => 'required|integer|greater_than[0]',
            'daily_quota' => 'required|integer|greater_than[0]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        // Actualizar usuario
        $data = [
            'external_id' => $this->request->getPost('external_id'),
            'quota' => $this->request->getPost('quota'),
            'daily_quota' => $this->request->getPost('daily_quota'),
            'active' => (bool)$this->request->getPost('active'),
        ];

        if ($this->apiUsersModel->update($user['id'], $data)) {
            return redirect()->to('/api-users')
                ->with('success', lang('App.api_users_updated'));
        }

        return redirect()->back()
            ->withInput()
            ->with('error', lang('App.api_users_update_error'));
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

    public function toggleStatus($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);
        }

        $tenant_id = session()->get('tenant_id');
        if (!$tenant_id) {
            return $this->response->setJSON(['success' => false, 'error' => 'No tenant selected']);
        }

        // Obtener el usuario actual
        $user = $this->apiUsersModel->where('id', $id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'error' => 'User not found']);
        }

        // Cambiar el estado
        $success = $this->apiUsersModel->update($id, [
            'active' => !$user['active']
        ]);

        if ($success) {
            return $this->response->setJSON([
                'success' => true,
                'active' => !$user['active']
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => 'Error updating status'
        ]);
    }
}
