<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

/**
 * Generate JWT token
 *
 * @param array $payload Data to be encoded in the token
 * @param int $expiration Expiration time in seconds (default: 1 hour)
 * @return string The generated token
 */
function generate_jwt($payload, $expiration = 3600)
{
    $time = time();

    // Setting the JWT token data
    $token_data = [
        'iat'  => $time,             // Issued at: time when the token was generated
        'nbf'  => $time,             // Not before: time before which the token is not valid
        'exp'  => $time + $expiration, // Expiration: token expiration time
        'data' => $payload           // User data
    ];

    // Get the JWT key from environment
    $key = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';

    // Generate the JWT token
    return JWT::encode($token_data, $key, 'HS256');
}

/**
 * Validate JWT token
 *
 * @param string $token The token to validate
 * @return object|false The token payload or false if invalid
 */
function validate_jwt($token)
{
    try {
        // Get the JWT key from environment
        $key = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';

        // Decode the token
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        return $decoded;
    } catch (ExpiredException $e) {
        // Token has expired
        log_message('error', 'Token expired: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        // Other errors
        log_message('error', 'Token validation error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get JWT token from Authorization header
 *
 * @return string|null The token or null if not found
 */
function get_jwt_from_header()
{
    $request = service('request');
    $authHeader = $request->getHeaderLine('Authorization');

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Get data from JWT token
 *
 * @return object|false The token data or false if invalid
 */
function get_jwt_data()
{
    $token = get_jwt_from_header();

    if (!$token) {
        return false;
    }

    $tokenData = validate_jwt($token);

    if (!$tokenData) {
        return false;
    }

    return $tokenData->data;
}
