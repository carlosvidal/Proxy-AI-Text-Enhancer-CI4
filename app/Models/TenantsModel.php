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
        $db = db_connect();
        $builder = $db->table('tenant_users');
        $builder->where('tenant_id', $tenant_id);
        return $builder->get()->getResult();
    }

    /**
     * Añade un usuario al tenant
     * 
     * @param array $userData Datos del usuario
     * @return bool Éxito o fracaso
     */
    public function addUser($userData)
    {
        $db = db_connect();
        $builder = $db->table('tenant_users');
        return $builder->insert($userData);
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
     * @return string Tenant ID único
     */
    public function generateTenantId($name)
    {
        // Create a base tenant_id from the name
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $base = substr($base, 0, 10); // Limit length

        // Add a random string to ensure uniqueness
        $random = substr(md5(uniqid(mt_rand(), true)), 0, 6);

        return $base . $random;
    }
}
