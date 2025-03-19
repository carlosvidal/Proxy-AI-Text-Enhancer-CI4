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
        'domain',
        'provider',
        'model',
        'api_key',
        'system_prompt',
        'active',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'button_id' => 'required|regex_match[/^btn-[0-9a-f]{8}-[0-9a-f]{8}$/]',
        'tenant_id' => 'required|regex_match[/^ten-[0-9a-f]{8}-[0-9a-f]{8}$/]',
        'name' => 'required|min_length[3]|max_length[255]',
        'domain' => 'required|valid_url_strict[https]',
        'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
        'model' => 'required',
        'api_key' => 'required',
        'system_prompt' => 'permit_empty',
        'active' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'button_id' => [
            'required' => 'Button ID is required',
            'regex_match' => 'Invalid button ID format'
        ],
        'tenant_id' => [
            'required' => 'Tenant ID is required',
            'regex_match' => 'Invalid tenant ID format'
        ],
        'name' => [
            'required' => 'Button name is required',
            'min_length' => 'Button name must be at least 3 characters',
            'max_length' => 'Button name cannot exceed 255 characters'
        ],
        'domain' => [
            'required' => 'Domain is required',
            'valid_url_strict' => 'Domain must be a valid HTTPS URL'
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
        ],
        'active' => [
            'in_list' => 'Active must be 0 or 1'
        ]
    ];

    protected $beforeInsert = ['generateButtonId', 'encryptApiKey'];
    protected $beforeUpdate = ['encryptApiKey'];
    protected $afterFind = ['decryptApiKey'];

    /**
     * Generate a unique button ID using the format btn-{timestamp}-{random}
     * Following the established ID format pattern from the system
     */
    protected function generateButtonId(array $data)
    {
        if (!isset($data['data']['button_id'])) {
            helper('hash');
            $data['data']['button_id'] = generate_hash_id('btn');
        }
        return $data;
    }

    protected function encryptApiKey(array $data)
    {
        if (isset($data['data']['api_key']) && !empty($data['data']['api_key'])) {
            $encrypter = \Config\Services::encrypter();
            try {
                // Check if the key is already encrypted (base64 format)
                base64_decode($data['data']['api_key'], true);
                if (strpos($data['data']['api_key'], 'error:') === false) {
                    return $data;
                }
            } catch (\Exception $e) {
                // Not base64, encrypt it
                $data['data']['api_key'] = base64_encode($encrypter->encrypt($data['data']['api_key']));
            }
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
     * Get all buttons for a tenant with usage statistics
     */
    public function getButtonsWithStatsByTenant($tenant_id)
    {
        $buttons = $this->where('tenant_id', $tenant_id)->findAll();

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
     * Get button configuration by domain
     * 
     * @param string $domain Domain name
     * @param string|null $tenant_id Optional tenant ID to filter by
     * @return array|null Button configuration or null if not found
     */
    public function getButtonByDomain(string $domain, string $tenant_id = null)
    {
        $query = $this->where('domain', $domain);
        
        if (!empty($tenant_id)) {
            $query->where('tenant_id', $tenant_id);
        }
        
        return $query->first();
    }
}
