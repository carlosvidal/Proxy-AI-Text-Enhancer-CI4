<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;

class Auth extends Controller
{
    public function __construct()
    {
        // Load necessary helpers
        helper(['url', 'form', 'jwt']);
    }

    public function login()
    {
        // If already logged in, redirect to dashboard
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
                    // Set session data
                    $sessionData = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'isLoggedIn' => true
                    ];

                    session()->set($sessionData);

                    // Check if the last_login column exists before updating it
                    $db = db_connect();
                    $tableInfo = $db->getFieldData('admin_users');
                    $hasLastLoginColumn = false;

                    foreach ($tableInfo as $field) {
                        if ($field->name === 'last_login') {
                            $hasLastLoginColumn = true;
                            break;
                        }
                    }

                    // Only update last_login if the column exists
                    if ($hasLastLoginColumn) {
                        $model->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
                    }

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

    /**
     * API Login endpoint - Returns JWT token
     */
    public function apiLogin()
    {
        // Get JSON data
        $json = $this->request->getJSON();

        if (!isset($json->username) || !isset($json->password)) {
            return $this->response->setStatusCode(400)
                ->setJSON([
                    'error' => [
                        'message' => 'Username and password are required'
                    ]
                ]);
        }

        $model = new UserModel();
        $user = $model->checkCredentials(
            $json->username,
            $json->password
        );

        if (!$user) {
            return $this->response->setStatusCode(401)
                ->setJSON([
                    'error' => [
                        'message' => 'Invalid username or password'
                    ]
                ]);
        }

        // Generate JWT token
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        $token = generate_jwt($payload);

        // Generate refresh token with longer expiration
        $refreshToken = generate_jwt([
            'id' => $user['id'],
            'type' => 'refresh'
        ], 86400 * 30); // 30 days

        // Update last login time
        $model->updateLastLogin($user['id']);

        return $this->response->setJSON([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken()
    {
        // Get JSON data
        $json = $this->request->getJSON();

        if (!isset($json->refresh_token)) {
            return $this->response->setStatusCode(400)
                ->setJSON([
                    'error' => [
                        'message' => 'Refresh token is required'
                    ]
                ]);
        }

        // Validate refresh token
        $tokenData = validate_jwt($json->refresh_token);

        if (!$tokenData || !isset($tokenData->data->id) || !isset($tokenData->data->type) || $tokenData->data->type !== 'refresh') {
            return $this->response->setStatusCode(401)
                ->setJSON([
                    'error' => [
                        'message' => 'Invalid refresh token'
                    ]
                ]);
        }

        // Get user data
        $model = new UserModel();
        $user = $model->find($tokenData->data->id);

        if (!$user) {
            return $this->response->setStatusCode(401)
                ->setJSON([
                    'error' => [
                        'message' => 'User not found'
                    ]
                ]);
        }

        // Generate new access token
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        $token = generate_jwt($payload);

        return $this->response->setJSON([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600
        ]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('auth/login');
    }

    public function profile()
    {
        // Check if logged in
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
        // Check if logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required',
                'email' => 'required|valid_email'
            ];

            // If a new password is provided, validate it
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

                // Update password if provided
                if ($this->request->getPost('password')) {
                    $userData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
                }

                if ($model->update($id, $userData)) {
                    // Update session data
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
