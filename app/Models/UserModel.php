<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'admin_users';
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
        'active',
        'last_login',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [
        'username' => 'required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[admin_users.username,id,{id}]',
        'email'    => 'required|valid_email|is_unique[admin_users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'name'     => 'required',
        'role'     => 'required|in_list[admin,user]',
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
     * Obtiene la lista de todos los administradores
     * 
     * @param bool $active Filtrar solo usuarios activos
     * @return array Lista de administradores
     */
    public function getAdmins($active = true)
    {
        $builder = $this->builder();
        $builder->where('role', 'admin');

        if ($active) {
            $builder->where('active', 1);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Crea un nuevo usuario administrador
     * 
     * @param array $userData Datos del usuario
     * @return int|false ID del nuevo usuario o false si falla
     */
    public function createAdmin($userData)
    {
        // Asegurar que el rol es admin
        $userData['role'] = 'admin';

        // Hashear contraseña
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }

        // Establecer timestamps
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');

        return $this->insert($userData);
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
}
