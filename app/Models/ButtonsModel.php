<?php

namespace App\Models;

use CodeIgniter\Model;

class ButtonsModel extends Model
{
    protected $table = 'buttons';
    protected $primaryKey = 'button_id';
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

    // Note: button_id is NOT in allowedFields to prevent modification

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'button_id' => 'required|min_length[3]|max_length[255]|regex_match[/^[a-z0-9-]+$/]|is_unique[buttons.button_id,button_id,{button_id}]',
        'tenant_id' => 'required',
        'name' => 'required|min_length[3]|max_length[255]',
        'description' => 'permit_empty|max_length[1000]',
        'type' => 'required|in_list[standard,custom]',
        'prompt' => 'required',
        'system_prompt' => 'permit_empty',
        'temperature' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[2]',
        'max_tokens' => 'required|integer|greater_than[0]|less_than_equal_to[4096]',
        'provider' => 'required|in_list[openai,anthropic,cohere]',
        'model' => 'required',
        'api_key' => 'required'
    ];

    protected $validationMessages = [
        'button_id' => [
            'required' => 'Button ID is required',
            'min_length' => 'Button ID must be at least 3 characters',
            'max_length' => 'Button ID cannot exceed 255 characters',
            'regex_match' => 'Button ID can only contain lowercase letters, numbers, and hyphens',
            'is_unique' => 'This Button ID is already in use'
        ],
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
        ],
        'api_key' => [
            'required' => 'API key is required'
        ]
    ];

    protected $beforeInsert = ['generateButtonId', 'encryptApiKey'];
    protected $beforeUpdate = ['encryptApiKey'];
    protected $afterFind = ['decryptApiKey'];

    protected function generateButtonId(array $data)
    {
        helper('hash');
        $data['data']['button_id'] = generate_hash_id('btn');
        return $data;
    }

    protected function encryptApiKey(array $data)
    {
        if (isset($data['data']['api_key'])) {
            $encrypter = \Config\Services::encrypter();
            $data['data']['api_key'] = base64_encode($encrypter->encrypt($data['data']['api_key']));
        }
        return $data;
    }

    protected function decryptApiKey(array $data)
    {
        $encrypter = \Config\Services::encrypter();
        
        // Handle single result
        if (isset($data['api_key'])) {
            try {
                $data['api_key'] = $encrypter->decrypt(base64_decode($data['api_key']));
            } catch (\Exception $e) {
                // If decryption fails, return encrypted value
                log_message('error', 'Failed to decrypt API key: ' . $e->getMessage());
            }
        }
        
        // Handle multiple results
        if (isset($data['data'])) {
            foreach ($data['data'] as &$row) {
                if (isset($row['api_key'])) {
                    try {
                        $row['api_key'] = $encrypter->decrypt(base64_decode($row['api_key']));
                    } catch (\Exception $e) {
                        // If decryption fails, return encrypted value
                        log_message('error', 'Failed to decrypt API key: ' . $e->getMessage());
                    }
                }
            }
        }
        
        return $data;
    }

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
    public function update($button_id = null, $data = null): bool
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

        return parent::update($button_id, $data);
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
            ", [$tenant_id, $button['button_id']])->getRowArray();

            $button['usage'] = $stats;
        }

        return $buttons;
    }

    /**
     * Get a button by ID with usage statistics
     */
    public function getButtonWithStats($button_id, $tenant_id)
    {
        $button = $this->where('button_id', $button_id)
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
            ", [$tenant_id, $button['button_id']])->getRowArray();

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
