<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeysModel extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['tenant_id', 'name', 'provider', 'api_key', 'is_default', 'active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'tenant_id' => 'required',
        'name' => 'required|min_length[3]|max_length[255]',
        'provider' => 'required|min_length[3]|max_length[50]',
        'api_key' => 'required'
    ];

    /**
     * Get all API keys for a tenant
     */
    public function getTenantApiKeys($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)
            ->orderBy('is_default', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Count API keys for a tenant
     */
    public function countTenantApiKeys($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)->countAllResults();
    }

    /**
     * Set an API key as default for a tenant
     */
    public function setDefault($api_key_id, $tenant_id)
    {
        // Start transaction
        $this->db->transStart();

        // Remove default from all keys for this tenant
        $this->where('tenant_id', $tenant_id)
            ->set(['is_default' => 0])
            ->update();

        // Set the specified key as default
        $this->update($api_key_id, ['is_default' => 1]);

        // Complete transaction
        $this->db->transComplete();

        return $this->db->transStatus();
    }
}
