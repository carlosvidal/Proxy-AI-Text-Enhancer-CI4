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

        // Obtener usuarios del tenant
        $data['users'] = $this->tenantsModel->getUsers($tenant_id);

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
                $tenantData = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->insert($tenantData)) {
                    return redirect()->to('/tenants')->with('success', 'Tenant created successfully');
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
        $data['title'] = 'Tenant Users';
        $data['tenant'] = $this->tenantsModel->find($tenant_id);

        if (empty($data['tenant'])) {
            return redirect()->to('/tenants')->with('error', 'Tenant not found');
        }

        $data['users'] = $this->tenantsModel->getUsers($tenant_id);

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
                $userData = [
                    'tenant_id' => $tenant_id,
                    'user_id' => $this->request->getPost('user_id'),
                    'email' => $this->request->getPost('email'),
                    'name' => $this->request->getPost('name'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->addUser($userData)) {
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
        $data['tenant'] = $this->tenantsModel->find($user->tenant_id);

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'email' => 'required|valid_email',
                'name' => 'required|min_length[3]|max_length[255]',
                'quota' => 'required|numeric'
            ];

            if ($this->validate($rules)) {
                $userData = [
                    'email' => $this->request->getPost('email'),
                    'name' => $this->request->getPost('name'),
                    'quota' => $this->request->getPost('quota'),
                    'active' => $this->request->getPost('active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->tenantsModel->updateUser($user_id, $userData)) {
                    return redirect()->to("/tenants/users/{$user->tenant_id}")->with('success', 'User updated successfully');
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
