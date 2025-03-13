<?php

namespace App\Models;

use CodeIgniter\Model;

class ButtonsModel extends Model
{
    protected $table = 'buttons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id',
        'name',
        'description',
        'type',
        'prompt',
        'system_prompt',
        'temperature',
        'max_tokens',
        'provider',
        'model',
        'api_key',
        'active',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required',
        'name' => 'required|min_length[3]|max_length[255]',
        'description' => 'permit_empty|max_length[1000]',
        'type' => 'required|in_list[standard,custom]',
        'prompt' => 'required',
        'system_prompt' => 'permit_empty',
        'temperature' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[2]',
        'max_tokens' => 'required|integer|greater_than[0]|less_than_equal_to[4096]',
        'provider' => 'required|in_list[openai,anthropic,cohere]',
        'model' => 'required'
    ];

    protected $validationMessages = [
        'tenant_id' => [
            'required' => 'Tenant ID is required'
        ],
        'name' => [
            'required' => 'Button name is required',
            'min_length' => 'Button name must be at least 3 characters',
            'max_length' => 'Button name cannot exceed 255 characters'
        ],
        'description' => [
            'max_length' => 'Description cannot exceed 1000 characters'
        ],
        'type' => [
            'required' => 'Button type is required',
            'in_list' => 'Invalid button type'
        ],
        'prompt' => [
            'required' => 'Main prompt is required'
        ],
        'temperature' => [
            'required' => 'Temperature is required',
            'decimal' => 'Temperature must be a decimal number',
            'greater_than_equal_to' => 'Temperature must be at least 0',
            'less_than_equal_to' => 'Temperature cannot exceed 2'
        ],
        'max_tokens' => [
            'required' => 'Max tokens is required',
            'integer' => 'Max tokens must be a whole number',
            'greater_than' => 'Max tokens must be greater than 0',
            'less_than_equal_to' => 'Max tokens cannot exceed 4096'
        ],
        'provider' => [
            'required' => 'Provider is required',
            'in_list' => 'Invalid provider selected'
        ],
        'model' => [
            'required' => 'Model is required'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Override the insert method to encrypt API keys
     */
    public function insert($data = null, bool $returnID = true)
    {
        // Check if data contains an API key
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            helper('api_key');
            $data['api_key'] = encrypt_api_key($data['api_key']);
        }

        return parent::insert($data, $returnID);
    }

    /**
     * Override the update method to handle API key encryption
     */
    public function update($id = null, $data = null): bool
    {
        // Check if data contains an API key and it's not empty
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            // If it's the special string "delete", set it to null instead
            if ($data['api_key'] === 'delete') {
                $data['api_key'] = null;
            } else {
                // Otherwise, encrypt the new API key
                helper('api_key');
                $data['api_key'] = encrypt_api_key($data['api_key']);
            }
        } elseif (isset($data['api_key']) && empty($data['api_key'])) {
            // If an empty API key is provided, don't update it
            // This allows keeping the existing key
            unset($data['api_key']);
        }

        return parent::update($id, $data);
    }

    /**
     * Get all buttons for a tenant with usage statistics
     */
    public function getButtonsWithStatsByTenant($tenant_id)
    {
        $db = \Config\Database::connect();
        $buttons = $this->where('tenant_id', $tenant_id)->findAll();

        foreach ($buttons as &$button) {
            // Get usage statistics
            $stats = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COUNT(DISTINCT user_id) as unique_users,
                    COALESCE(SUM(tokens_used), 0) as total_tokens,
                    COALESCE(AVG(tokens_used), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens_used), 0) as max_tokens,
                    MAX(created_at) as last_used
                FROM usage_logs 
                WHERE tenant_id = ? AND button_id = ?
            ", [$tenant_id, $button['id']])->getRowArray();

            $button['usage'] = $stats;
        }

        return $buttons;
    }

    /**
     * Get a button by ID with usage statistics
     */
    public function getButtonWithStats($id, $tenant_id)
    {
        $button = $this->where('id', $id)
                      ->where('tenant_id', $tenant_id)
                      ->first();

        if ($button) {
            $db = \Config\Database::connect();
            
            // Get usage statistics
            $stats = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COUNT(DISTINCT user_id) as unique_users,
                    COALESCE(SUM(tokens_used), 0) as total_tokens,
                    COALESCE(AVG(tokens_used), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens_used), 0) as max_tokens,
                    MAX(created_at) as last_used
                FROM usage_logs 
                WHERE tenant_id = ? AND button_id = ?
            ", [$tenant_id, $button['id']])->getRowArray();

            $button['usage'] = $stats;

            // If button has an API key, decrypt it
            if (!empty($button['api_key'])) {
                helper('api_key');
                $button['api_key'] = decrypt_api_key($button['api_key']);
            }
        }

        return $button;
    }
}
