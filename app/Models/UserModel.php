<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'username',
        'email',
        'password',
        'name',
        'role',
        'tenant_id',
        'active',
        'last_login',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [
        'username' => 'required|min_length[3]|is_unique[users.username,id,{id}]',
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'name'     => 'required',
        'role'     => 'required|in_list[superadmin,tenant]',
        'tenant_id' => 'permit_empty'
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Verifica las credenciales del usuario
     * 
     * @param string $username Username o email
     * @param string $password Contraseña sin hashear
     * @return array|null Datos del usuario o null si no es válido
     */
    public function checkCredentials($username, $password)
    {
        // Buscar por username o email
        $user = $this->where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user) {
            return null;
        }

        // Verificar si la cuenta está activa
        if (!$user['active']) {
            return null;
        }

        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            unset($user['password']); // No devolver el hash de la contraseña
            return $user;
        }

        return null;
    }

    /**
     * Actualiza el último login de un usuario
     * 
     * @param int $userId ID del usuario
     * @return bool Éxito o fracaso
     */
    public function updateLastLogin($userId)
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user with tenant details
     */
    public function getUserWithTenant($userId)
    {
        $builder = $this->db->table('users u');
        $builder->select('u.*, t.name as tenant_name, t.plan_code');
        $builder->join('tenants t', 't.tenant_id = u.tenant_id', 'left');
        $builder->where('u.id', $userId);
        
        return $builder->get()->getRowArray();
    }
}
