<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiTokenModel extends Model
{
    protected $table      = 'api_tokens';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'tenant_id',
        'name',
        'token',
        'refresh_token',
        'scopes',
        'last_used_at',
        'expires_at',
        'revoked',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [
        'user_id'     => 'required|integer',
        'name'        => 'required|min_length[3]|max_length[255]',
        'token'       => 'required|min_length[32]|max_length[64]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Generate a new API token for a user
     *
     * @param int $userId User ID
     * @param string $name Token name/description
     * @param string|null $tenantId Optional tenant ID
     * @param array|null $scopes Optional permission scopes
     * @param int $expiresIn Seconds until token expires (0 = never)
     * @return array|null The token data or null on failure
     */
    public function generateToken($userId, $name, $tenantId = null, $scopes = null, $expiresIn = 0)
    {
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        // Format scopes as JSON if provided
        $scopesJson = null;
        if (!empty($scopes) && is_array($scopes)) {
            $scopesJson = json_encode($scopes);
        }

        // Set expiration date if needed
        $expiresAt = null;
        if ($expiresIn > 0) {
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        }

        $data = [
            'user_id'      => $userId,
            'tenant_id'    => $tenantId,
            'name'         => $name,
            'token'        => $token,
            'refresh_token' => $refreshToken,
            'scopes'       => $scopesJson,
            'expires_at'   => $expiresAt,
            'revoked'      => 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $id = $this->insert($data);

        if ($id) {
            $data['id'] = $id;
            return $data;
        }

        return null;
    }

    /**
     * Validate an API token
     *
     * @param string $token The token to validate
     * @return array|null The token data or null if invalid
     */
    public function validateToken($token)
    {
        $tokenData = $this->where('token', $token)
            ->where('revoked', 0)
            ->first();

        if (!$tokenData) {
            return null;
        }

        // Check if token has expired
        if (!empty($tokenData['expires_at'])) {
            $expiryDate = strtotime($tokenData['expires_at']);
            if ($expiryDate < time()) {
                return null;
            }
        }

        // Update last used timestamp
        $this->update($tokenData['id'], [
            'last_used_at' => date('Y-m-d H:i:s')
        ]);

        return $tokenData;
    }

    /**
     * Refresh a token using its refresh token
     *
     * @param string $refreshToken The refresh token
     * @return array|null New token data or null if refresh failed
     */
    public function refreshToken($refreshToken)
    {
        $tokenData = $this->where('refresh_token', $refreshToken)
            ->where('revoked', 0)
            ->first();

        if (!$tokenData) {
            return null;
        }

        // Generate new token
        $newToken = bin2hex(random_bytes(32));
        $newRefreshToken = bin2hex(random_bytes(32));

        // Calculate new expiry if the original token had one
        $expiresAt = null;
        if (!empty($tokenData['expires_at'])) {
            // Set new expiry to same duration from now
            $originalDuration = strtotime($tokenData['expires_at']) - strtotime($tokenData['created_at']);
            $expiresAt = date('Y-m-d H:i:s', time() + $originalDuration);
        }

        $this->update($tokenData['id'], [
            'token' => $newToken,
            'refresh_token' => $newRefreshToken,
            'expires_at' => $expiresAt,
            'updated_at' => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s')
        ]);

        // Get updated token data
        $updatedToken = $this->find($tokenData['id']);

        return $updatedToken;
    }

    /**
     * Revoke a token
     *
     * @param int $tokenId Token ID
     * @return bool Success or failure
     */
    public function revokeToken($tokenId)
    {
        return $this->update($tokenId, [
            'revoked' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get all active tokens for a user
     *
     * @param int $userId User ID
     * @return array List of active tokens
     */
    public function getUserTokens($userId)
    {
        return $this->where('user_id', $userId)
            ->where('revoked', 0)
            ->findAll();
    }
}
