<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantsModel extends Model
{
    /**
     * Flag to enable automatic user creation
     * 
     * @var bool
     */
    protected $autoCreateUsers = false;
    protected $table = 'tenants';
    protected $primaryKey = 'tenant_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['name', 'email', 'quota', 'active', 'api_key', 'plan_code', 'subscription_status', 'trial_ends_at', 'subscription_ends_at', 'created_at', 'updated_at', 'max_domains', 'max_api_keys', 'auto_create_users'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'required|valid_email',
        'quota' => 'required|integer|greater_than[0]',
        'active' => 'required|in_list[0,1]',
        'max_api_keys' => 'required|integer|greater_than_equal_to[0]',
        'auto_create_users' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'El nombre es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 255 caracteres'
        ],
        'email' => [
            'required' => 'El email es requerido',
            'valid_email' => 'El email debe ser válido'
        ],
        'quota' => [
            'required' => 'Quota is required',
            'integer' => 'Quota must be a whole number',
            'greater_than' => 'Quota must be greater than 0'
        ],
        'active' => [
            'required' => 'Active status is required',
            'in_list' => 'Active status must be either 0 or 1'
        ],
        'max_api_keys' => [
            'required' => 'Maximum API keys is required',
            'integer' => 'Maximum API keys must be a whole number',
            'greater_than_equal_to' => 'Maximum API keys must be 0 or greater'
        ],
        'auto_create_users' => [
            'required' => 'Auto-create users setting is required',
            'in_list' => 'Auto-create users must be either 0 or 1'
        ]
    ];

    protected $skipValidation = false;

    protected $beforeInsert = ['generateTenantId'];

    /**
     * Generates a unique hash-based tenant ID before insert
     * Format: ten-{timestamp}-{random}
     */
    protected function generateTenantId(array $data)
    {
        helper('hash');
        $data['data']['tenant_id'] = generate_hash_id('ten');
        return $data;
    }

    /**
     * Get all users associated with a tenant
     * 
     * @param string $tenant_id Tenant ID
     * @return array List of users
     */
    public function getUsers($tenant_id)
    {
        log_message('debug', "TenantsModel::getUsers - Looking for users with tenant_id: {$tenant_id}");

        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('tenant_id', $tenant_id);

        $result = $builder->get()->getResult();

        log_message('debug', "TenantsModel::getUsers - Found " . count($result) . " users");

        // Debug: print SQL query
        $lastQuery = $db->getLastQuery();
        log_message('debug', "TenantsModel::getUsers - SQL: " . (string)$lastQuery);

        return $result;
    }

    /**
     * Add a user to the tenant
     * 
     * @param array $userData User data
     * @return bool Success or failure
     */
    public function addUser($userData)
    {
        log_message('debug', "TenantsModel::addUser - Received data: " . json_encode($userData));

        // Verify required fields
        if (!isset($userData['tenant_id']) || !isset($userData['user_id'])) {
            log_message('error', "TenantsModel::addUser - Missing required fields: tenant_id or user_id");
            return false;
        }

        try {
            $db = db_connect();
            $result = $db->table('tenant_users')->insert($userData);

            if ($result) {
                log_message('debug', "TenantsModel::addUser - User inserted successfully");
                return true;
            } else {
                log_message('error', "TenantsModel::addUser - Insert error: " . json_encode($db->error()));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "TenantsModel::addUser - Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user information
     * 
     * @param int $id User record ID
     * @param array $userData User data
     * @return bool Success or failure
     */
    public function updateUser($id, $userData)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('id', $id);
        return $builder->update($userData);
    }

    /**
     * Delete a user from the tenant
     * 
     * @param int $id User record ID
     * @return bool Success or failure
     */
    public function deleteUser($id)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('id', $id);
        return $builder->delete();
    }

    /**
     * Check if a user has access to a tenant
     * 
     * @param string $tenant_id Tenant ID
     * @param string $user_id User ID
     * @return bool Access allowed or not
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
     * Enable or disable automatic user creation for this tenant
     * 
     * @param bool $enable Whether to enable or disable auto-creation
     * @return void
     */
    public function setAutoCreateUsers(bool $enable = true): void
    {
        $this->autoCreateUsers = $enable;
    }
    
    /**
     * Check if automatic user creation is enabled for a specific tenant
     * 
     * @param string|null $tenant_id Tenant ID to check (optional)
     * @return bool True if auto-creation is enabled
     */
    public function isAutoCreateUsersEnabled(string $tenant_id = null): bool
    {
        // If tenant_id is provided, check the database setting
        if ($tenant_id !== null) {
            $tenant = $this->findByTenantId($tenant_id);
            if ($tenant) {
                return (bool)($tenant['auto_create_users'] ?? false);
            }
            return false;
        }
        
        // Otherwise use the instance property
        return $this->autoCreateUsers;
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

    /**
     * Get domains for a tenant
     * 
     * @param string $tenantId Tenant ID
     * @return array List of domains
     */
    public function getDomains($tenantId)
    {
        return $this->db->table('domains')
            ->where('tenant_id', $tenantId)
            ->get()
            ->getResultArray();
    }

    /**
     * Add a domain to the tenant
     * 
     * @param array $data Domain data
     * @return int Domain ID
     */
    public function addDomain($data)
    {
        $this->db->table('domains')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Check if a tenant can create more API users
     *
     * @param string $tenantId The tenant ID to check
     * @return bool True if the tenant can create more API users, false otherwise
     */
    public function canCreateApiUser(string $tenantId): bool
    {
        try {
            // Get tenant details
            $tenant = $this->find($tenantId);
            if (!$tenant) {
                log_message('error', '[TenantsModel::canCreateApiUser] Tenant not found: ' . $tenantId);
                return false;
            }

            $maxUsers = $tenant['max_api_keys'] ?? 1;

            // Get current count of API users
            $db = \Config\Database::connect();
            $currentCount = $db->table('tenant_users')
                ->where('tenant_id', $tenantId)
                ->countAllResults();

            log_message('debug', '[TenantsModel::canCreateApiUser] Tenant: ' . $tenantId . ', Max: ' . $maxUsers . ', Current: ' . $currentCount);

            return $currentCount < $maxUsers;
        } catch (\Exception $e) {
            log_message('error', '[TenantsModel::canCreateApiUser] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tenant by ID
     *
     * @param string $tenantId
     * @return array|null
     */
    public function getTenantById(string $tenantId)
    {
        return $this->find($tenantId);
    }
}
