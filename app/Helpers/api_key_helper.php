<?php

/**
 * API Key Helper functions
 * 
 * Helper functions for securely handling API keys
 */

if (!function_exists('encrypt_api_key')) {
    /**
     * Encrypts an API key for secure storage
     * 
     * @param string $api_key The API key to encrypt
     * @return string The encrypted API key
     */
    function encrypt_api_key($api_key)
    {
        // Get encryption key from env (should be set in production)
        $encryption_key = getenv('ENCRYPTION_KEY') ?: 'default-encryption-key-change-in-production';

        // Generate an initialization vector
        $iv = random_bytes(16); // AES block size in CBC mode

        // Encrypt the API key using AES-256-CBC
        $encrypted = openssl_encrypt(
            $api_key,
            'AES-256-CBC',
            $encryption_key,
            0,
            $iv
        );

        // Combine the IV and encrypted data
        $encrypted_with_iv = base64_encode($iv . $encrypted);

        return $encrypted_with_iv;
    }
}

if (!function_exists('decrypt_api_key')) {
    /**
     * Decrypts a stored API key
     * 
     * @param string $encrypted_api_key The encrypted API key
     * @return string|false The decrypted API key or false on failure
     */
    function decrypt_api_key($encrypted_api_key)
    {
        // Get encryption key from env
        $encryption_key = getenv('ENCRYPTION_KEY') ?: 'default-encryption-key-change-in-production';

        // Decode the combined string
        $decoded = base64_decode($encrypted_api_key);

        // Extract the IV (first 16 bytes) and the encrypted data
        $iv = substr($decoded, 0, 16);
        $encrypted_data = substr($decoded, 16);

        // Decrypt the data
        $decrypted = openssl_decrypt(
            $encrypted_data,
            'AES-256-CBC',
            $encryption_key,
            0,
            $iv
        );

        return $decrypted;
    }
}

if (!function_exists('mask_api_key')) {
    /**
     * Creates a masked version of an API key for display
     * 
     * @param string $api_key The API key to mask
     * @param int $visible_chars Number of characters to show at the end
     * @return string The masked API key
     */
    function mask_api_key($api_key, $visible_chars = 4)
    {
        if (empty($api_key) || strlen($api_key) <= $visible_chars) {
            return $api_key;
        }

        $masked_length = strlen($api_key) - $visible_chars;
        $masked_part = str_repeat('•', min($masked_length, 10)); // No more than 10 bullets
        $visible_part = substr($api_key, -$visible_chars);

        return $masked_part . $visible_part;
    }
}
