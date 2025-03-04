<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;

class Auth extends Controller
{
    public function __construct()
    {
        // Cargar helpers necesarios
        helper(['url', 'form']);
    }

    public function login()
    {
        // Si ya está logueado, redirigir al dashboard
        if (session()->get('isLoggedIn')) {
            return redirect()->to('usage');
        }

        $data = [
            'title' => 'Login - LLM Proxy'
        ];

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'username' => 'required',
                'password' => 'required'
            ];

            if ($this->validate($rules)) {
                $model = new UserModel();
                $user = $model->checkCredentials(
                    $this->request->getPost('username'),
                    $this->request->getPost('password')
                );

                if ($user) {
                    // Establecer datos de sesión
                    $sessionData = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'isLoggedIn' => true
                    ];

                    session()->set($sessionData);

                    // Actualizar último login
                    $model->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

                    return redirect()->to('usage');
                } else {
                    return redirect()->back()
                        ->with('error', 'Invalid username or password')
                        ->withInput();
                }
            } else {
                return redirect()->back()
                    ->with('error', $this->validator->getErrors())
                    ->withInput();
            }
        }

        return view('auth/login', $data);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('auth/login');
    }

    public function profile()
    {
        // Verificar si está logueado
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $data = [
            'title' => 'My Profile'
        ];

        $model = new UserModel();
        $data['user'] = $model->find(session()->get('id'));

        return view('auth/profile', $data);
    }

    public function updateProfile()
    {
        // Verificar si está logueado
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required',
                'email' => 'required|valid_email'
            ];

            // Si se proporciona una nueva contraseña, validarla
            if ($this->request->getPost('password')) {
                $rules['password'] = 'required|min_length[8]';
                $rules['password_confirm'] = 'matches[password]';
            }

            if ($this->validate($rules)) {
                $model = new UserModel();
                $id = session()->get('id');

                $userData = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Actualizar contraseña si se proporciona
                if ($this->request->getPost('password')) {
                    $userData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
                }

                if ($model->update($id, $userData)) {
                    // Actualizar datos de sesión
                    session()->set('name', $userData['name']);
                    session()->set('email', $userData['email']);

                    return redirect()->to('auth/profile')
                        ->with('success', 'Profile updated successfully');
                } else {
                    return redirect()->back()
                        ->with('error', 'Error updating profile')
                        ->withInput();
                }
            } else {
                return redirect()->back()
                    ->with('error', $this->validator->getErrors())
                    ->withInput();
            }
        }

        return redirect()->to('auth/profile');
    }
}
