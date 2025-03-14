<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiUsersModel extends Model
{
    protected $table = 'api_users';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'external_id',
        'tenant_id',
        'name',
        'email',
        'quota',
        'active',
        'created_at',
        'updated_at'
    ];

    // Validation rules
    protected $validationRules = [
        'user_id' => 'required|is_unique[api_users.user_id]|regex_match[/^usr-[a-f0-9]{8}-[a-f0-9]{8}$/]',
        'external_id' => 'required|max_length[255]|is_unique[api_users.external_id,tenant_id,{tenant_id}]',
        'tenant_id' => 'required|regex_match[/^ten-[a-f0-9]{8}-[a-f0-9]{8}$/]',
        'name' => 'permit_empty|min_length[3]|max_length[255]',
        'email' => 'permit_empty|valid_email',
        'quota' => 'required|integer|greater_than[0]',
        'active' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required',
            'is_unique' => 'This User ID already exists',
            'regex_match' => 'Invalid user ID format'
        ],
        'external_id' => [
            'required' => 'External ID is required for API consumption',
            'is_unique' => 'This External ID is already in use within this tenant',
            'max_length' => 'External ID cannot exceed 255 characters'
        ],
        'tenant_id' => [
            'required' => 'Tenant ID is required',
            'regex_match' => 'Invalid tenant ID format'
        ],
        'name' => [
            'min_length' => 'Name must be at least 3 characters long',
            'max_length' => 'Name cannot exceed 255 characters'
        ],
        'email' => [
            'valid_email' => 'Please enter a valid email address'
        ],
        'quota' => [
            'required' => 'Monthly token quota is required',
            'integer' => 'Monthly token quota must be a whole number',
            'greater_than' => 'Monthly token quota must be greater than 0'
        ],
        'active' => [
            'required' => 'Status is required',
            'in_list' => 'Invalid status selected'
        ]
    ];

    // Before insert hook to generate user_id
    protected $beforeInsert = ['generateUserId'];

    protected function generateUserId(array $data)
    {
        if (!isset($data['data']['user_id'])) {
            helper('hash');
            $data['data']['user_id'] = generate_hash_id('usr');
        }
        return $data;
    }

    // Get all API users for a tenant with their usage statistics
    public function getApiUsersByTenant($tenant_id)
    {
        $db = \Config\Database::connect();
        
        // Get all API users for this tenant
        $users = $this->where('tenant_id', $tenant_id)
                     ->orderBy('created_at', 'DESC')
                     ->findAll();

        // Get usage statistics for each user
        foreach ($users as &$user) {
            // Get button access
            $buttonAccess = $db->table('api_user_buttons')
                ->select('buttons.*, api_user_buttons.created_at as access_granted_at')
                ->join('buttons', 'buttons.button_id = api_user_buttons.button_id')
                ->where('api_user_buttons.user_id', $user['user_id'])
                ->get()->getResultArray();
            
            $user['buttons'] = $buttonAccess;

            // Get usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens
                FROM usage_logs
                WHERE tenant_id = ? 
                AND user_id = ?
                AND status = 'success'
            ", [$tenant_id, $user['user_id']])->getRowArray();

            // Get this month's usage
            $monthlyUsage = $db->query("
                SELECT COALESCE(SUM(tokens), 0) as monthly_tokens
                FROM usage_logs
                WHERE tenant_id = ? 
                AND user_id = ?
                AND status = 'success'
                AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')
            ", [$tenant_id, $user['user_id']])->getRowArray();

            // Get last activity timestamp
            $lastActivity = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant_id, $user['user_id']])->getRowArray();

            $user['usage'] = $usage;
            $user['monthly_usage'] = $monthlyUsage['monthly_tokens'];
            $user['last_activity'] = $lastActivity['created_at'] ?? null;
        }

        return $users;
    }
}
