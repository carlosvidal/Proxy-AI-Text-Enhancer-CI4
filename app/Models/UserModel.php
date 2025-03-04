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
        'active'
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
}
