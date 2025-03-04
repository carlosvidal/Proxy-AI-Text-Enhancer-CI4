<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ApiTokenModel;
use App\Models\UserModel;

class ApiToken extends Controller
{
    protected $apiTokenModel;

    public function __construct()
    {
        helper(['url', 'form', 'jwt']);
        $this->apiTokenModel = new ApiTokenModel();
    }

    /**
     * List API tokens for the current user
     */
    public function index()
    {
        // Check if logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $data = [
            'title' => 'API Tokens'
        ];

        $userId = session()->get('id');
        $data['tokens'] = $this->apiTokenModel->getUserTokens($userId);

        return view('api_tokens/index', $data);
    }

    /**
     * Create token form
     */
    public function create()
    {
        // Check if logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $data = [
            'title' => 'Create API Token'
        ];

        // Load tenants for dropdown if admin
        if (session()->get('role') === 'admin') {
            $tenantsModel = new \App\Models\TenantsModel();
            $data['tenants'] = $tenantsModel->findAll();
        }

        return view('api_tokens/create', $data);
    }

    /**
     * Store new token
     */
    public function store()
    {
        // Check if logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'expires' => 'permit_empty|integer',
        ];

        if ($this->validate($rules)) {
            $userId = session()->get('id');
            $name = $this->request->getPost('name');
            $tenantId = $this->request->getPost('tenant_id');
            $expires = $this->request->getPost('expires');

            // Convert expiry to seconds if provided
            $expiresIn = 0;
            if (!empty($expires)) {
                $expiresIn = (int)$expires * 86400; // Convert days to seconds
            }

            // Determine scopes (can be extended based on your requirements)
            $scopes = ['api:access'];

            $token = $this->apiTokenModel->generateToken($userId, $name, $tenantId, $scopes, $expiresIn);

            if ($token) {
                // Show token once
                session()->setFlashdata('token', $token['token']);
                session()->setFlashdata('refresh_token', $token['refresh_token']);

                return redirect()->to('api/tokens')->with('success', 'Token created successfully');
            } else {
                return redirect()->back()->with('error', 'Error creating token')->withInput();
            }
        } else {
            return redirect()->back()->with('error', $this->validator->getErrors())->withInput();
        }
    }

    /**
     * Revoke/delete token
     */
    public function revoke($id = null)
    {
        // Check if logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        if (!$id) {
            return redirect()->to('api/tokens')->with('error', 'Invalid token ID');
        }

        // Verify token belongs to current user (or user is admin)
        $userId = session()->get('id');
        $token = $this->apiTokenModel->find($id);

        if (!$token || ($token['user_id'] != $userId && session()->get('role') !== 'admin')) {
            return redirect()->to('api/tokens')->with('error', 'Unauthorized action');
        }

        // Revoke token
        if ($this->apiTokenModel->revokeToken($id)) {
            return redirect()->to('api/tokens')->with('success', 'Token revoked successfully');
        } else {
            return redirect()->to('api/tokens')->with('error', 'Error revoking token');
        }
    }

    /**
     * API endpoint for token validation (for testing)
     */
    public function validateToken()
    {
        $token = get_jwt_from_header();

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No token provided'
            ]);
        }

        $tokenData = validate_jwt($token);

        if (!$tokenData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid token'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Token is valid',
            'data' => $tokenData->data
        ]);
    }
}
