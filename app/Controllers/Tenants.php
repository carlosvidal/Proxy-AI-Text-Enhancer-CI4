<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\TenantsModel;

class Tenants extends Controller
{
    protected $tenantsModel;

    public function __construct()
    {
        helper(['url', 'form', 'logger']);
        $this->tenantsModel = new TenantsModel();
    }

    /**
     * Listado de todos los tenants
     */
    public function index()
    {
        $data['title'] = 'Tenant Management';
        $data['tenants'] = $this->tenantsModel->findAll();

        return view('tenants/index', $data);
    }

    /**
     * Ver detalles de un tenant
     */
    public function view($tenant_id)
    {
        $data['title'] = 'Tenant Details';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        // Get users for this tenant using the tenant_id string
        $data['users'] = $this->tenantsModel->getUsers($data['tenant']['tenant_id']);

        // Get buttons for this tenant
        $buttonsModel = new \App\Models\ButtonsModel();
        $data['buttons'] = $buttonsModel->getButtonsByTenant($data['tenant']['tenant_id']);

        return view('tenants/view', $data);
    }
    /**
     * Crear un nuevo tenant
     */
    public function create()
    {
        $data['title'] = 'Create Tenant';

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'email' => 'required|valid_email',
                'quota' => 'required|numeric'
            ];

            if ($this->validate($rules)) {
                // Generate a unique tenant_id based on the name
                $tenant_id = $this->tenantsModel->generateTenantId($this->request->getPost('name'));

                $tenantData = [
                    'tenant_id' => $tenant_id,
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->insert($tenantData)) {
                    return redirect()->to('/tenants')->with('success', 'Tenant created successfully with ID: ' . $tenant_id);
                } else {
                    return redirect()->back()->with('error', 'Error creating tenant')->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        return view('tenants/create', $data);
    }

    /**
     * Editar un tenant existente
     */
    public function edit($tenant_id)
    {
        $data['title'] = 'Edit Tenant';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'email' => 'required|valid_email',
                'quota' => 'required|numeric'
            ];

            if ($this->validate($rules)) {
                $tenantData = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->update($tenant_id, $tenantData)) {
                    return redirect()->to("/tenants/view/$tenant_id")->with('success', 'Tenant updated successfully');
                } else {
                    return redirect()->back()->with('error', 'Error updating tenant')->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        return view('tenants/edit', $data);
    }

    /**
     * Eliminar un tenant
     */
    public function delete($tenant_id)
    {
        $tenant = $this->tenantsModel->find($tenant_id);

        if (empty($tenant)) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        if ($this->tenantsModel->delete($tenant_id)) {
            return redirect()->to('/tenants')->with('success', 'Tenant deleted successfully');
        } else {
            return redirect()->to('/tenants')->with('error', 'Error deleting tenant');
        }
    }

    /**
     * Gestionar usuarios de un tenant
     */
    public function users($tenant_id)
    {
        log_message('debug', "TenantsController::users - ID del tenant: {$tenant_id}");

        $data['title'] = 'Tenant Users';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        log_message('debug', "TenantsController::users - Datos del tenant: " . json_encode($data['tenant']));

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        // Usar el tenant_id del tenant, no el ID numérico
        $tenant_id_str = $data['tenant']['tenant_id'];
        log_message('debug', "TenantsController::users - Buscando usuarios con tenant_id: {$tenant_id_str}");

        $data['users'] = $this->tenantsModel->getUsers($tenant_id_str);
        log_message('debug', "TenantsController::users - Usuarios encontrados: " . count($data['users']));

        // Para depuración, imprimir los usuarios encontrados
        if (count($data['users']) > 0) {
            log_message('debug', "TenantsController::users - Primer usuario: " . json_encode($data['users'][0]));
        } else {
            // Verificar directamente en la base de datos como último recurso
            $db = db_connect();
            $query = $db->query("SELECT * FROM tenant_users WHERE tenant_id = ?", [$tenant_id_str]);
            $direct_results = $query->getResultArray();

            log_message('debug', "TenantsController::users - Consulta directa SQL encontró " . count($direct_results) . " usuarios");
            if (count($direct_results) > 0) {
                log_message('debug', "TenantsController::users - Hay un problema con la función getUsers.");
                $data['users'] = $direct_results; // Asignar los resultados directos como fallback
            }
        }

        return view('tenants/users', $data);
    }

    /**
     * Añadir un usuario a un tenant
     */
    public function add_user($tenant_id)
    {
        $data['title'] = 'Add User to Tenant';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'user_id' => 'required|min_length[3]|max_length[100]',
                'email' => 'required|valid_email',
                'name' => 'required|min_length[3]|max_length[255]',
                'quota' => 'required|numeric'
            ];

            if ($this->validate($rules)) {
                $tenant_id_str = $data['tenant']['tenant_id'];
                $user_id = $this->request->getPost('user_id');

                // Verificar si el usuario ya existe para este tenant
                $db = db_connect();
                $existing = $db->table('tenant_users')
                    ->where('tenant_id', $tenant_id_str)
                    ->where('user_id', $user_id)
                    ->get()
                    ->getRow();

                if ($existing) {
                    return redirect()->back()
                        ->with('error', 'A user with this ID already exists for this tenant')
                        ->withInput();
                }

                $userData = [
                    'tenant_id' => $tenant_id_str,
                    'user_id' => $user_id,
                    'email' => $this->request->getPost('email'),
                    'name' => $this->request->getPost('name'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->addUser($userData)) {
                    // Crear también en user_quotas
                    $db->table('user_quotas')->insert([
                        'tenant_id' => $tenant_id_str,
                        'user_id' => $user_id,
                        'total_quota' => $this->request->getPost('quota'),
                        'reset_period' => 'monthly',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    return redirect()->to("/tenants/users/$tenant_id")->with('success', 'User added successfully');
                } else {
                    return redirect()->back()->with('error', 'Error adding user')->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        return view('tenants/add_user', $data);
    }

    /**
     * Editar un usuario existente de un tenant
     */
    public function edit_user($user_id)
    {
        $data['title'] = 'Edit User';
        $db = db_connect();

        // Obtener información del usuario
        $builder = $db->table('tenant_users');
        $builder->where('id', $user_id);
        $user = $builder->get()->getRow();

        if (empty($user)) {
            return redirect()->to('/tenants')->with('error', 'User not found');
        }

        $data['user'] = $user;

        // Buscar el tenant usando el tenant_id string
        $tenant = $this->tenantsModel->where('tenant_id', $user->tenant_id)->first();

        if (empty($tenant)) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        $data['tenant'] = $tenant;

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'email' => 'required|valid_email',
                'name' => 'required|min_length[3]|max_length[255]',
                'quota' => 'required|numeric'
            ];

            if ($this->validate($rules)) {
                $old_user_data = (array)$user;
                $userData = [
                    'email' => $this->request->getPost('email'),
                    'name' => $this->request->getPost('name'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Actualizar en tenant_users
                if ($this->tenantsModel->updateUser($user_id, $userData)) {
                    // Sincronizar con user_quotas
                    $builder = $db->table('user_quotas');
                    $builder->where('tenant_id', $user->tenant_id);
                    $builder->where('user_id', $user->user_id);
                    $quota_record = $builder->get()->getRow();

                    if ($quota_record) {
                        // Si existe, actualizar
                        $db->table('user_quotas')
                            ->where('tenant_id', $user->tenant_id)
                            ->where('user_id', $user->user_id)
                            ->update([
                                'total_quota' => $this->request->getPost('quota'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);

                        log_info('QUOTAS', 'Actualizada cuota en user_quotas para sincronizar', [
                            'tenant_id' => $user->tenant_id,
                            'user_id' => $user->user_id,
                            'new_quota' => $this->request->getPost('quota')
                        ]);
                    } else {
                        // Si no existe, crear
                        $db->table('user_quotas')->insert([
                            'tenant_id' => $user->tenant_id,
                            'user_id' => $user->user_id,
                            'total_quota' => $this->request->getPost('quota'),
                            'reset_period' => 'monthly',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                        log_info('QUOTAS', 'Creada cuota en user_quotas', [
                            'tenant_id' => $user->tenant_id,
                            'user_id' => $user->user_id,
                            'quota' => $this->request->getPost('quota')
                        ]);
                    }

                    return redirect()->to("/tenants/users/{$tenant['id']}")->with('success', 'User updated successfully');
                } else {
                    return redirect()->back()->with('error', 'Error updating user')->withInput();
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }

        return view('tenants/edit_user', $data);
    }

    /**
     * Eliminar un usuario de un tenant
     */
    public function delete_user($user_id)
    {
        $db = db_connect();

        // Obtener información del usuario para saber a qué tenant volver
        $builder = $db->table('tenant_users');
        $builder->where('id', $user_id);
        $user = $builder->get()->getRow();

        if (empty($user)) {
            return redirect()->to('/tenants')->with('error', 'User not found');
        }

        $tenant_id = $user->tenant_id;

        // Eliminar el usuario
        if ($this->tenantsModel->deleteUser($user_id)) {
            return redirect()->to("/tenants/users/{$tenant_id}")->with('success', 'User deleted successfully');
        } else {
            return redirect()->to("/tenants/users/{$tenant_id}")->with('error', 'Error deleting user');
        }
    }
}
