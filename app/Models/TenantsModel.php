<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantsModel extends Model
{
    protected $table = 'tenants';
    protected $primaryKey = 'tenant_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['tenant_id', 'name', 'email', 'quota', 'active', 'api_key', 'plan_code', 'subscription_status', 'trial_ends_at', 'subscription_ends_at', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required|min_length[3]|max_length[255]|is_unique[tenants.tenant_id,tenant_id,{tenant_id}]',
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'required|valid_email'
    ];

    protected $validationMessages = [
        'tenant_id' => [
            'required' => 'El tenant_id es requerido',
            'min_length' => 'El tenant_id debe tener al menos 3 caracteres',
            'max_length' => 'El tenant_id no puede tener más de 255 caracteres',
            'is_unique' => 'Ya existe un tenant con este tenant_id'
        ],
        'name' => [
            'required' => 'El nombre es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 255 caracteres'
        ],
        'email' => [
            'required' => 'El email es requerido',
            'valid_email' => 'El email debe ser válido'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Obtiene todos los usuarios asociados a un tenant
     * 
     * @param string $tenant_id ID del tenant
     * @return array Lista de usuarios
     */
    public function getUsers($tenant_id)
    {
        log_message('debug', "TenantsModel::getUsers - Buscando usuarios para tenant_id: {$tenant_id}");

        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('tenant_id', $tenant_id);

        $result = $builder->get()->getResult();

        log_message('debug', "TenantsModel::getUsers - Encontrados " . count($result) . " usuarios");

        // Para depuración, imprimir la consulta SQL
        $lastQuery = $db->getLastQuery();
        log_message('debug', "TenantsModel::getUsers - SQL: " . (string)$lastQuery);

        return $result;
    }

    /**
     * Añade un usuario al tenant
     * 
     * @param array $userData Datos del usuario
     * @return bool Éxito o fracaso
     */
    public function addUser($userData)
    {
        log_message('debug', "TenantsModel::addUser - Datos recibidos: " . json_encode($userData));

        // Verificar que los campos requeridos estén presentes
        if (!isset($userData['tenant_id']) || !isset($userData['user_id'])) {
            log_message('error', "TenantsModel::addUser - Faltan campos requeridos: tenant_id o user_id");
            return false;
        }

        try {
            $db = db_connect();
            $result = $db->table('tenant_users')->insert($userData);

            if ($result) {
                log_message('debug', "TenantsModel::addUser - Usuario insertado correctamente");
                return true;
            } else {
                log_message('error', "TenantsModel::addUser - Error al insertar: " . json_encode($db->error()));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "TenantsModel::addUser - Excepción: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la información de un usuario
     * 
     * @param int $id ID del registro de usuario
     * @param array $userData Datos del usuario
     * @return bool Éxito o fracaso
     */
    public function updateUser($id, $userData)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('id', $id);
        return $builder->update($userData);
    }

    /**
     * Elimina un usuario del tenant
     * 
     * @param int $id ID del registro de usuario
     * @return bool Éxito o fracaso
     */
    public function deleteUser($id)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('id', $id);
        return $builder->delete();
    }

    /**
     * Verifica si un usuario tiene acceso a un tenant
     * 
     * @param string $tenant_id ID del tenant
     * @param string $user_id ID del usuario
     * @return bool Acceso permitido o no
     */
    public function checkUserAccess($tenant_id, $user_id)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('tenant_id', $tenant_id);
        $builder->where('user_id', $user_id);
        $builder->where('active', 1);
        return $builder->countAllResults() > 0;
    }

    /**
     * Genera un tenant_id único para un nuevo tenant
     * 
     * @param string $name Nombre del tenant para base del ID
     * @return string Tenant ID único de 8 caracteres
     */
    public function generateTenantId($name)
    {
        // Create a clean base from the name (lowercase, only alphanumeric)
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));

        // Take first 4 characters or pad if shorter
        $base = substr($base . 'xxxx', 0, 4);

        // Generate a random hex string for the second half
        $random = bin2hex(random_bytes(2)); // 4 hex characters

        // Combine for a unique 8-character tenant_id
        $tenant_id = $base . $random;

        // Check if it already exists, if so, generate a new one
        while ($this->isTenantIdTaken($tenant_id)) {
            $random = bin2hex(random_bytes(2));
            $tenant_id = $base . $random;
        }

        return $tenant_id;
    }

    /**
     * Check if a tenant_id is already in use
     * 
     * @param string $tenant_id Tenant ID to check
     * @return bool True if taken, false otherwise
     */
    public function isTenantIdTaken($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)->countAllResults() > 0;
    }

    /**
     * Find a tenant by tenant_id
     * 
     * @param string $tenant_id Tenant ID to find
     * @return array|null Tenant data or null if not found
     */
    public function findByTenantId($tenant_id)
    {
        return $this->where('tenant_id', $tenant_id)->first();
    }

    /**
     * Find tenant by ID and return with tenant_id
     * 
     * @param int $id Tenant primary key ID
     * @return array|null Tenant data with tenant_id or null if not found
     */
    public function findWithTenantId($id)
    {
        return $this->find($id);
    }

    /**
     * Get tenant by user ID
     * 
     * @param int $userId User ID to find tenant for
     * @return array|null Tenant data if found, null otherwise
     */
    public function getTenantByUserId(int $userId): ?array
    {
        $builder = $this->db->table('tenant_users');
        $builder->select('tenants.*')
            ->join('tenants', 'tenants.tenant_id = tenant_users.tenant_id')
            ->where('tenant_users.user_id', $userId)
            ->where('tenant_users.active', 1)
            ->where('tenants.active', 1);

        $result = $builder->get()->getRowArray();
        return $result ?: null;
    }

    /**
     * Get all active tenants
     */
    public function getActiveTenants()
    {
        return $this->where('active', 1)
            ->findAll();
    }

    /**
     * Get tenant with usage statistics
     */
    public function getTenantWithStats($tenant_id)
    {
        $tenant = $this->where('tenant_id', $tenant_id)
            ->where('active', 1)
            ->first();

        if (!$tenant) {
            return null;
        }

        // Get usage statistics
        $db = \Config\Database::connect();
        $builder = $db->table('usage_logs');
        
        // Total requests
        $tenant['total_requests'] = $builder->where('tenant_id', $tenant_id)->countAllResults();
        
        // Total tokens
        $builder->select('SUM(tokens) as total_tokens');
        $result = $builder->where('tenant_id', $tenant_id)->get()->getRow();
        $tenant['total_tokens'] = $result ? $result->total_tokens : 0;
        
        // Total cost
        $builder->select('SUM(cost) as total_cost');
        $result = $builder->where('tenant_id', $tenant_id)->get()->getRow();
        $tenant['total_cost'] = $result ? $result->total_cost : 0;

        return $tenant;
    }
}
