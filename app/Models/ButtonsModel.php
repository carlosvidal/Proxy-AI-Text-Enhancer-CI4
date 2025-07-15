<?php

namespace App\Models;

use CodeIgniter\Model;

class ButtonsModel extends Model
{
    protected $table = 'buttons';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'button_id',
        'tenant_id',
        'name',
        'description',
        'domain',
        'system_prompt',
        'prompt',
        'provider',
        'model',
        'api_key_id',
        'status',
        'active',
        'temperature',
        'auto_create_api_users',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required',
        'name' => 'required|min_length[3]|max_length[255]',
        'domain' => 'required',
        'provider' => 'required|in_list[openai,anthropic,google,azure]',
        'model' => 'required',
        'api_key_id' => 'required',
        'status' => 'required|in_list[active,inactive]'
    ];

    protected $validationMessages = [
        'tenant_id' => [
            'required' => 'Tenant ID is required',
        ],
        'name' => [
            'required' => 'Button name is required',
            'min_length' => 'Button name must be at least 3 characters',
            'max_length' => 'Button name cannot exceed 255 characters'
        ],
        'domain' => [
            'required' => 'Domain is required',
        ],
        'provider' => [
            'required' => 'Provider is required',
            'in_list' => 'Invalid provider selected'
        ],
        'model' => [
            'required' => 'Model is required'
        ],
        'api_key_id' => [
            'required' => 'API Key ID is required',
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Invalid status'
        ]
    ];

    protected $beforeInsert = ['generateButtonId', 'setDefaultStatus', 'formatDomain'];
    protected $beforeUpdate = ['formatDomain'];
    protected $beforeDelete = ['checkUsageAndCleanup'];

    /**
     * Generate a unique button ID using the format btn-{timestamp}-{random}
     */
    protected function generateButtonId(array $data)
    {
        helper('hash');
        $data['data']['button_id'] = generate_hash_id('btn');
        log_message('debug', 'Generated button_id: ' . $data['data']['button_id']);
        return $data;
    }

    /**
     * Set default status for new buttons
     */
    protected function setDefaultStatus(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'active';
        }
        return $data;
    }

    /**
     * Format domain to ensure it has https:// prefix
     */
    protected function formatDomain(array $data)
    {
        if (isset($data['data']['domain'])) {
            $domain = $data['data']['domain'];
            if (!preg_match('~^(?:f|ht)tps?://~i', $domain)) {
                $data['data']['domain'] = 'https://' . $domain;
            }
            log_message('debug', 'Formatted domain: ' . $data['data']['domain']);
        }
        return $data;
    }

    /**
     * Check for usage logs and clean them up before deleting a button
     */
    protected function checkUsageAndCleanup(array $data)
    {
        $buttonId = $data['id'];
        $db = \Config\Database::connect();

        // Delete usage logs directly
        $db->table('usage_logs')
           ->where('button_id', $buttonId)
           ->delete();

        return $data;
    }

    /**
     * Override insert method to add debug logging
     */
    public function insert($data = null, bool $returnID = true)
    {
        log_message('debug', 'ButtonsModel::insert - Data before hooks: ' . json_encode($data));
        
        // Run hooks manually to ensure they execute in order
        $data = $this->trigger('beforeInsert', ['data' => $data]);
        log_message('debug', 'ButtonsModel::insert - Data after hooks: ' . json_encode($data));
        
        $result = parent::insert($data['data'], $returnID);
        log_message('debug', 'ButtonsModel::insert - Result: ' . json_encode($result));
        
        if (!$result) {
            log_message('error', 'ButtonsModel::insert - Validation errors: ' . json_encode($this->errors()));
            log_message('error', 'ButtonsModel::insert - Last query: ' . $this->db->getLastQuery());
        }
        return $result;
    }

    /**
     * Get all buttons for a tenant with usage statistics
     */
    public function getButtonsWithStatsByTenant($tenant_id)
    {
        log_message('debug', 'Getting buttons for tenant: ' . $tenant_id);
        
        $buttons = $this->where('tenant_id', $tenant_id)
                       ->where('status', 'active')
                       ->findAll();

        log_message('debug', 'Found ' . count($buttons) . ' buttons');
        log_message('debug', 'SQL: ' . $this->getLastQuery()->getQuery());

        // Get usage statistics for each button
        $db = \Config\Database::connect();
        foreach ($buttons as &$button) {
            // Get button usage statistics
            $usage = $db->query("
                SELECT 
                    COUNT(DISTINCT id) as total_requests,
                    COALESCE(SUM(tokens), 0) as total_tokens,
                    COALESCE(AVG(tokens), 0) as avg_tokens_per_request,
                    COALESCE(MAX(tokens), 0) as max_tokens,
                    COUNT(DISTINCT user_id) as unique_users
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                AND status = 'success'
            ", [$tenant_id, $button['button_id']])->getRowArray();

            // Get last usage timestamp
            $lastUsage = $db->query("
                SELECT created_at
                FROM usage_logs
                WHERE tenant_id = ? 
                AND button_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$tenant_id, $button['button_id']])->getRowArray();

            $button['usage'] = $usage;
            $button['last_used'] = $lastUsage['created_at'] ?? null;
        }

        return $buttons;
    }

    /**
     * Get button with all its details
     */
    public function getButtonWithDetails($button_id, $tenant_id)
    {
        return $this->where('button_id', $button_id)
            ->where('tenant_id', $tenant_id)
            ->first();
    }

    /**
     * Get button by button_id
     */
    public function getButtonByButtonId($button_id)
    {
        return $this->where('button_id', $button_id)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get button configuration by domain
     */
    public function getButtonByDomain(string $domain, string $tenant_id = null)
    {
        $query = $this->where('domain', $domain)
            ->where('status', 'active');
        
        if (!empty($tenant_id)) {
            $query->where('tenant_id', $tenant_id);
        }
        
        return $query->first();
    }
}
