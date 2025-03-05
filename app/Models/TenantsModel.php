<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantsModel extends Model
{
    protected $table      = 'tenants';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tenant_id',  // Added tenant_id to allowedFields
        'name',
        'email',
        'quota',
        'active',
        'api_key',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

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
}
