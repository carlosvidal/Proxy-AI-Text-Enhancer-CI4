<?php

namespace App\Controllers;

use App\Models\TenantsModel;
use App\Models\UsersModel;

class AdminUsers extends BaseController
{
    protected $tenantsModel;
    protected $usersModel;

    public function __construct()
    {
        helper(['url', 'form', 'logger']); // Añadido 'logger' al array de helpers
        $this->tenantsModel = new TenantsModel();
        $this->usersModel = new UsersModel();
    }

    /**
     * Lista todos los usuarios de autenticación del sistema
     */
    public function index()
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Obtener todos los usuarios
        $users = $this->usersModel->findAll();

        // Obtener información adicional de tenants para cada usuario
        foreach ($users as &$user) {
            if (!empty($user['tenant_id'])) {
                $tenant = $this->tenantsModel->where('tenant_id', $user['tenant_id'])->first();
                $user['tenant_name'] = $tenant ? $tenant['name'] : 'Sin tenant';
            } else {
                $user['tenant_name'] = 'Sin tenant';
            }
        }

        $data = [
            'title' => 'Gestión de Usuarios',
            'users' => $users
        ];

        return view('admin/users/index', $data);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario de autenticación
     */
    public function create()
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Obtener lista de tenants para el dropdown
        $tenants = $this->tenantsModel->where('active', 1)->findAll();

        $data = [
            'title' => 'Crear Usuario',
            'tenants' => $tenants
        ];

        return view('admin/users/create', $data);
    }

    /**
     * Procesa la creación de un nuevo usuario de autenticación
     */
    public function store()
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Validar formulario
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]|max_length[255]',
            'name' => 'required|min_length[3]|max_length[255]',
            'role' => 'required|in_list[superadmin,tenant]',
            'tenant_id' => 'permit_empty',
            'active' => 'required|in_list[0,1]'
        ];

        // Añadir logs antes de la validación
        log_debug('USERS', 'Iniciando creación de usuario', [
            'post_data' => $this->request->getPost()
        ]);

        if (!$this->validate($rules)) {
            log_error('USERS', 'Error de validación en creación', [
                'errors' => $this->validator->getErrors()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Preparar datos
        $userData = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'name' => $this->request->getPost('name'),
            'role' => $this->request->getPost('role'),
            'tenant_id' => $this->request->getPost('tenant_id'),
            'active' => $this->request->getPost('active'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        log_debug('USERS', 'Datos preparados para creación', [
            'userData' => array_diff_key($userData, ['password' => '']) // Excluir password del log
        ]);

        // Guardar usuario
        try {
            $result = $this->usersModel->insert($userData);

            log_debug('USERS', 'Resultado de creación', [
                'success' => $result !== false,
                'user_id' => $this->usersModel->insertID(),
                'affected_rows' => $this->usersModel->db->affectedRows()
            ]);

            return redirect()->to('/admin/users')
                ->with('success', 'Usuario creado exitosamente');
        } catch (\Exception $e) {
            log_error('USERS', 'Error al crear usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear usuario: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para editar un usuario de autenticación existente
     */
    public function edit($id)
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Obtener usuario
        $user = $this->usersModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'Usuario no encontrado');
        }

        // Obtener lista de tenants para el dropdown
        $tenants = $this->tenantsModel->where('active', 1)->findAll();

        $data = [
            'title' => 'Editar Usuario',
            'user' => $user,
            'tenants' => $tenants
        ];

        return view('admin/users/edit', $data);
    }

    /**
     * Procesa la actualización de un usuario de autenticación existente
     */
    public function update($id)
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Verificar si existe el usuario
        $existingUser = $this->usersModel->find($id);
        log_debug('USERS', 'Usuario existente', [
            'user_id' => $id,
            'exists' => !empty($existingUser),
            'user_data' => $existingUser ?? null
        ]);

        if (!$existingUser) {
            return redirect()->back()->with('error', 'Usuario no encontrado');
        }

        // Establecer reglas de validación con el ID actual
        $this->usersModel->setValidationRules($id);

        // Preparar datos
        $userData = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'name' => $this->request->getPost('name'),
            'role' => $this->request->getPost('role'),
            'tenant_id' => $this->request->getPost('tenant_id'),
            'active' => $this->request->getPost('active'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Si se proporcionó una nueva contraseña, incluirla
        if ($password = $this->request->getPost('password')) {
            if (!empty($password)) {
                $userData['password'] = $password;
            }
        }

        log_debug('USERS', 'Datos preparados para actualización', [
            'user_id' => $id,
            'userData' => $userData
        ]);

        try {
            // Intentar actualizar
            $result = $this->usersModel->update($id, $userData);

            log_debug('USERS', 'Resultado de actualización', [
                'user_id' => $id,
                'success' => $result !== false,
                'affected_rows' => $this->usersModel->db->affectedRows()
            ]);

            if ($result === false) {
                $errors = $this->usersModel->errors();
                log_error('USERS', 'Error de validación al actualizar', [
                    'user_id' => $id,
                    'validation_errors' => $errors
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $errors);
            }

            return redirect()->to('/admin/users')
                ->with('success', 'Usuario actualizado exitosamente');
        } catch (\Exception $e) {
            log_error('USERS', 'Excepción al actualizar usuario', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    /**
     * Muestra detalles de un usuario de autenticación
     */
    public function view($id)
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Obtener usuario
        $user = $this->usersModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'Usuario no encontrado');
        }

        // Obtener información de tenant si aplica
        $tenant = null;
        if (!empty($user['tenant_id'])) {
            $tenant = $this->tenantsModel->where('tenant_id', $user['tenant_id'])->first();
        }

        $data = [
            'title' => 'Detalles de Usuario',
            'user' => $user,
            'tenant' => $tenant
        ];

        return view('admin/users/view', $data);
    }

    /**
     * Elimina un usuario de autenticación
     */
    public function delete($id)
    {
        // Verificar permisos de admin
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        // Obtener usuario
        $user = $this->usersModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'Usuario no encontrado');
        }

        // No permitir eliminar el propio usuario
        if ($id == session()->get('id')) {
            return redirect()->to('/admin/users')->with('error', 'No puedes eliminar tu propio usuario');
        }

        try {
            // Eliminar usuario
            $this->usersModel->delete($id);
            return redirect()->to('/admin/users')
                ->with('success', 'Usuario eliminado exitosamente');
        } catch (\Exception $e) {
            log_message('error', 'Error al eliminar usuario: ' . $e->getMessage());
            return redirect()->to('/admin/users')
                ->with('error', 'Error al eliminar usuario: ' . $e->getMessage());
        }
    }
}
