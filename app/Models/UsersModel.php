<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'username', 
        'email', 
        'password', 
        'name', 
        'role',
        'tenant_id',
        'active',
        'quota',
        'last_login',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[3]',
        'name' => 'required|min_length[3]|max_length[255]',
        'role' => 'required|in_list[superadmin,tenant]',
        'tenant_id' => 'permit_empty',
        'active' => 'required|in_list[0,1]',
        'quota' => 'permit_empty|numeric|greater_than[0]'
    ];

    protected $validationMessages = [
        'username' => [
            'required' => 'El nombre de usuario es requerido',
            'min_length' => 'El nombre de usuario debe tener al menos 3 caracteres',
            'max_length' => 'El nombre de usuario no puede tener más de 50 caracteres',
            'is_unique' => 'Este nombre de usuario ya está en uso'
        ],
        'email' => [
            'required' => 'El email es requerido',
            'valid_email' => 'El email no es válido',
            'is_unique' => 'Este email ya está en uso'
        ],
        'password' => [
            'required' => 'La contraseña es requerida',
            'min_length' => 'La contraseña debe tener al menos 3 caracteres'
        ],
        'name' => [
            'required' => 'El nombre es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede tener más de 255 caracteres'
        ],
        'role' => [
            'required' => 'El rol es requerido',
            'in_list' => 'El rol debe ser superadmin o tenant'
        ],
        'active' => [
            'required' => 'El estado es requerido',
            'in_list' => 'El estado debe ser 0 o 1'
        ],
        'quota' => [
            'numeric' => 'La cuota debe ser un número',
            'greater_than' => 'La cuota debe ser mayor a 0'
        ]
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPasswordIfSet'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    protected function hashPasswordIfSet(array $data)
    {
        if (!empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Verify if the given password matches the user's password
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
