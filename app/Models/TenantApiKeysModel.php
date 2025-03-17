<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantApiKeysModel extends Model
{
    protected $table = 'tenant_api_keys';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'api_key_id', 'tenant_id', 'provider', 'name', 'api_key', 
        'is_default', 'active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'api_key_id' => 'required|is_unique[tenant_api_keys.api_key_id]',
        'tenant_id' => 'required',
        'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
        'name' => 'required|min_length[3]|max_length[100]',
        'api_key' => 'required|min_length[10]'
    ];
    
    protected $validationMessages = [
        'api_key_id' => [
            'required' => 'El ID de la API Key es obligatorio',
            'is_unique' => 'Este ID de API Key ya existe'
        ],
        'tenant_id' => [
            'required' => 'El ID del tenant es obligatorio'
        ],
        'provider' => [
            'required' => 'El proveedor es obligatorio',
            'in_list' => 'El proveedor seleccionado no es válido'
        ],
        'name' => [
            'required' => 'El nombre es obligatorio',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 100 caracteres'
        ],
        'api_key' => [
            'required' => 'La API Key es obligatoria',
            'min_length' => 'La API Key debe tener al menos 10 caracteres'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Generate a unique API Key ID
     * 
     * @return string
     */
    public function generateApiKeyId()
    {
        helper('hash');
        return generate_hash_id('key');
    }

    /**
     * Get API keys for a specific tenant
     * 
     * @param string $tenant_id
     * @return array
     */
    public function getTenantApiKeys($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)
                    ->where('active', 1)
                    ->findAll();
    }

    /**
     * Get API keys for a specific tenant and provider
     * 
     * @param string $tenant_id
     * @param string $provider
     * @return array
     */
    public function getTenantProviderApiKeys($tenant_id, $provider)
    {
        return $this->where('tenant_id', $tenant_id)
                    ->where('provider', $provider)
                    ->where('active', 1)
                    ->findAll();
    }

    /**
     * Get default API key for a tenant and provider
     * 
     * @param string $tenant_id
     * @param string $provider
     * @return array|null
     */
    public function getDefaultApiKey($tenant_id, $provider)
    {
        return $this->where('tenant_id', $tenant_id)
                    ->where('provider', $provider)
                    ->where('is_default', 1)
                    ->where('active', 1)
                    ->first();
    }

    /**
     * Set an API key as default for a tenant and provider
     * 
     * @param string $api_key_id
     * @param string $tenant_id
     * @param string $provider
     * @return bool
     */
    public function setAsDefault($api_key_id, $tenant_id, $provider)
    {
        // First, unset any existing default
        $this->where('tenant_id', $tenant_id)
             ->where('provider', $provider)
             ->where('is_default', 1)
             ->set(['is_default' => 0])
             ->update();
        
        // Then set the new default
        return $this->where('api_key_id', $api_key_id)
                    ->where('tenant_id', $tenant_id)
                    ->set(['is_default' => 1])
                    ->update();
    }

    /**
     * Count API keys for a tenant
     * 
     * @param string $tenant_id
     * @return int
     */
    public function countTenantApiKeys($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)
                    ->where('active', 1)
                    ->countAllResults();
    }
}
