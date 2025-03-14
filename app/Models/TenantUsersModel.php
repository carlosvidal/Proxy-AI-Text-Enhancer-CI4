<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TenantUsersModel
 * 
 * This model manages API users (not system authentication users).
 * API users are used only for tracking API consumption and quotas.
 * They do not have passwords and email is optional.
 */
class TenantUsersModel extends Model
{
    protected $table = 'tenant_users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id',      // ID of the tenant this API user belongs to
        'user_id',        // Unique identifier for the API user
        'name',           // Display name for the API user
        'email',          // Optional email for the API user
        'quota',          // Token quota for API consumption
        'active',         // Whether this API user is active
        'created_at',     // When this API user was created
        'updated_at'      // When this API user was last updated
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required',
        'user_id' => 'required|min_length[3]|max_length[50]|is_unique[tenant_users.user_id,id,{id}]',
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'permit_empty|valid_email',
        'quota' => 'required|numeric|greater_than[0]',
        'active' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'tenant_id' => [
            'required' => 'El tenant_id es requerido'
        ],
        'user_id' => [
            'required' => 'El user_id es requerido',
            'min_length' => 'El user_id debe tener al menos 3 caracteres',
            'max_length' => 'El user_id no puede tener más de 50 caracteres',
            'is_unique' => 'Este user_id ya está en uso'
        ],
        'name' => [
            'required' => 'El nombre es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 255 caracteres'
        ],
        'email' => [
            'valid_email' => 'El email no es válido'
        ],
        'quota' => [
            'required' => 'La cuota es requerida',
            'numeric' => 'La cuota debe ser un número',
            'greater_than' => 'La cuota debe ser mayor a 0'
        ],
        'active' => [
            'required' => 'El estado es requerido',
            'in_list' => 'El estado debe ser 0 o 1'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get all API users for a tenant
     * 
     * @param string $tenant_id The tenant ID to get users for
     * @return array Array of API user objects with their quotas and status
     */
    public function getUsersByTenant($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)
            ->findAll();
    }

    /**
     * Get API user by their unique identifier
     * 
     * @param string $user_id The API user's unique identifier
     * @param string $tenant_id Optional tenant ID to verify ownership
     * @return object|null The API user object if found, null otherwise
     */
    public function getUser($user_id, $tenant_id = null)
    {
        $query = $this->where('user_id', $user_id);
        
        if ($tenant_id) {
            $query->where('tenant_id', $tenant_id);
        }
        
        return $query->first();
    }
}
