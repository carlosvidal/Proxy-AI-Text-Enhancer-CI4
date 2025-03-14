<?php

if (!function_exists('generate_hash_id')) {
    /**
     * Generates a unique hash ID for database records
     * Format: prefix-timestamp-random
     * Example: ten-1234567890-abc123
     * 
     * @param string $prefix The prefix for the hash (e.g., 'ten' for tenant, 'btn' for button)
     * @return string The generated hash ID
     */
    function generate_hash_id(string $prefix): string {
        $timestamp = dechex(time());
        $random = bin2hex(random_bytes(4));
        return sprintf('%s-%s-%s', $prefix, $timestamp, $random);
    }
}
