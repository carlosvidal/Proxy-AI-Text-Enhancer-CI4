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
        'active'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'tenant_id' => 'required',
        'name' => 'required|min_length[3]|max_length[255]',
        'domain' => 'required|min_length[3]|max_length[255]',
        'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
        'model' => 'required',
        'api_key' => 'required',
        'system_prompt' => 'permit_empty',
        'active' => 'permit_empty'
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
        'domain' => [
            'required' => 'Domain is required',
            'min_length' => 'Domain must be at least 3 characters',
            'max_length' => 'Domain cannot exceed 255 characters'
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

    /**
     * Generate a unique button ID using the format btn-{timestamp}-{random}
     * Following the established ID format pattern from the system
     */
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
     * Get all buttons for a tenant with usage statistics
     */
    public function getButtonsWithStatsByTenant($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)->findAll();
    }
}
