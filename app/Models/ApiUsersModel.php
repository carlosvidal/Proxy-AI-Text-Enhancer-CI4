<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiUsersModel extends Model
{
    protected $table = 'api_users';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['tenant_id', 'user_id', 'external_id', 'name', 'email', 'quota', 'daily_quota', 'active', 'created_at', 'updated_at', 'last_activity'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Generate a unique user_id for a new API user
     */
    protected function generateUserId($data)
    {
        $prefix = 'usr';
        $timestamp = dechex(time());
        $random = bin2hex(random_bytes(4));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Override insert method to handle user_id generation
     */
    public function insert($data = null, bool $returnID = true)
    {
        // Generate user_id if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = $this->generateUserId($data);
        }

        // Set default daily_quota if not provided
        if (!isset($data['daily_quota'])) {
            $data['daily_quota'] = 10000; // Default daily quota
        }

        // Verify tenant exists
        $tenantsModel = model('App\Models\TenantsModel');
        if (!$tenantsModel->find($data['tenant_id'])) {
            return false;
        }

        // Call parent insert
        if (parent::insert($data, true)) {
            return $data['user_id'];
        }

        return false;
    }

    /**
     * Get API users for a specific tenant with usage data
     */
    public function getApiUsersByTenant($tenant_id)
    {
        $users = $this->where('tenant_id', $tenant_id)->findAll();
        
        // Add monthly and daily usage data and format last_activity
        foreach ($users as &$user) {
            $user['monthly_usage'] = 0; // We'll implement actual usage tracking later
            $user['daily_usage'] = 0; // We'll implement actual usage tracking later
            $user['daily_quota'] = $user['daily_quota'] ?? 10000; // Default if not set
            $user['last_activity'] = $user['last_activity'] ?? null;
        }
        
        return $users;
    }

    /**
     * Update last activity timestamp for a user
     */
    public function updateLastActivity($user_id)
    {
        return $this->update($user_id, [
            'last_activity' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get a specific API user by ID with usage data
     */
    public function getApiUserById($id)
    {
        $user = $this->find($id);
        if ($user) {
            $user['daily_quota'] = $user['daily_quota'] ?? 10000; // Default if not set
            $user['monthly_usage'] = 0; // We'll implement actual usage tracking later
            $user['daily_usage'] = 0; // We'll implement actual usage tracking later
        }
        return $user;
    }

    // Validation rules
    protected $validationRules = [
        'tenant_id' => 'required|regex_match[/^ten-[a-f0-9]{8}-[a-f0-9]{8}$/]',
        'external_id' => 'required|max_length[255]|is_unique[api_users.external_id,tenant_id,{tenant_id}]',
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'permit_empty|valid_email',
        'quota' => 'required|integer|greater_than[0]',
        'daily_quota' => 'permit_empty|integer|greater_than[0]',
        'active' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'tenant_id' => [
            'required' => 'Tenant ID is required',
            'regex_match' => 'Invalid tenant ID format'
        ],
        'external_id' => [
            'required' => 'External ID is required',
            'max_length' => 'External ID cannot exceed 255 characters',
            'is_unique' => 'This External ID is already in use for this tenant'
        ],
        'name' => [
            'required' => 'Name is required',
            'min_length' => 'Name must be at least 3 characters long',
            'max_length' => 'Name cannot exceed 255 characters'
        ],
        'email' => [
            'valid_email' => 'Please enter a valid email address'
        ],
        'quota' => [
            'required' => 'Monthly quota is required',
            'integer' => 'Monthly quota must be a whole number',
            'greater_than' => 'Monthly quota must be greater than 0'
        ],
        'daily_quota' => [
            'integer' => 'Daily quota must be a whole number',
            'greater_than' => 'Daily quota must be greater than 0'
        ],
        'active' => [
            'required' => 'Status is required',
            'in_list' => 'Invalid status value'
        ]
    ];
}
