<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeysModel extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'api_key_id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'api_key_id',
        'tenant_id',
        'name',
        'provider',
        'api_key',
        'is_default',
        'active'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = '';

    protected $validationRules = [
        'api_key_id' => 'required|alpha_numeric_punct|min_length[10]|max_length[100]',
        'tenant_id' => 'required|alpha_numeric_punct|min_length[3]|max_length[100]',
        'name' => 'required|min_length[3]|max_length[100]',
        'provider' => 'required|in_list[openai,anthropic,cohere,mistral,deepseek,google]',
        'api_key' => 'required',
        'is_default' => 'required|in_list[0,1]',
        'active' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'api_key_id' => [
            'required' => 'El ID de la API Key es requerido',
            'alpha_numeric_punct' => 'El ID de la API Key solo puede contener letras, números y guiones',
            'min_length' => 'El ID de la API Key debe tener al menos 10 caracteres',
            'max_length' => 'El ID de la API Key no puede tener más de 100 caracteres'
        ],
        'tenant_id' => [
            'required' => 'El ID del tenant es requerido',
            'alpha_numeric_punct' => 'El ID del tenant solo puede contener letras, números y guiones',
            'min_length' => 'El ID del tenant debe tener al menos 10 caracteres',
            'max_length' => 'El ID del tenant no puede tener más de 100 caracteres'
        ],
        'name' => [
            'required' => 'El nombre es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 100 caracteres'
        ],
        'provider' => [
            'required' => 'El proveedor es requerido',
            'in_list' => 'El proveedor debe ser uno de los siguientes: OpenAI, Anthropic, Cohere, Mistral, DeepSeek o Google'
        ],
        'api_key' => [
            'required' => 'La API Key es requerida'
        ],
        'is_default' => [
            'required' => 'El campo predeterminado es requerido',
            'in_list' => 'El campo predeterminado debe ser 0 o 1'
        ],
        'active' => [
            'required' => 'El campo activo es requerido',
            'in_list' => 'El campo activo debe ser 0 o 1'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get all API keys for a tenant
     */
    public function getTenantApiKeys(string $tenant_id): array
    {
        return $this->where('tenant_id', $tenant_id)
                   ->orderBy('is_default', 'DESC')
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }

    /**
     * Set an API key as default for a tenant
     */
    public function setDefault(string $api_key_id, string $tenant_id): bool
    {
        // First, unset all default keys for this tenant
        $this->where('tenant_id', $tenant_id)
             ->set(['is_default' => 0])
             ->update();

        // Then set the selected key as default
        return $this->where('api_key_id', $api_key_id)
                   ->where('tenant_id', $tenant_id)
                   ->set(['is_default' => 1])
                   ->update();
    }

    /**
     * Get the default API key for a tenant and provider
     */
    public function getDefaultKey(string $tenant_id, string $provider): ?array
    {
        return $this->where('tenant_id', $tenant_id)
                   ->where('provider', $provider)
                   ->where('is_default', 1)
                   ->where('active', 1)
                   ->first();
    }

    /**
     * Count active API keys for a tenant
     */
    public function countTenantApiKeys(string $tenant_id): int
    {
        return $this->where('tenant_id', $tenant_id)
                   ->where('active', 1)
                   ->countAllResults();
    }

    /**
     * Encrypt API key before saving
     */
    protected function beforeInsert(array $data): array
    {
        if (isset($data['data']['api_key'])) {
            $encrypter = \Config\Services::encrypter();
            $data['data']['api_key'] = base64_encode($encrypter->encrypt($data['data']['api_key']));
        }
        return $data;
    }

    /**
     * Decrypt API key after retrieving
     */
    protected function afterFind($data)
    {
        if (empty($data)) {
            return $data;
        }

        $encrypter = \Config\Services::encrypter();

        if (is_array($data)) {
            foreach ($data as &$row) {
                if (isset($row['api_key'])) {
                    try {
                        $row['api_key'] = $encrypter->decrypt(base64_decode($row['api_key']));
                    } catch (\Exception $e) {
                        log_message('error', 'Error decrypting API key: ' . $e->getMessage());
                    }
                }
            }
        }

        return $data;
    }
}
