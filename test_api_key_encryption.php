<?php

// Test script to verify API key encryption/decryption
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsPath = __DIR__ . '/app/Config/Paths.php';
require realpath($pathsPath) ?: $pathsPath;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

// Test data
$test_api_key = 'sk-test-api-key-1234567890abcdef';
$test_tenant_id = 'test-tenant-123';
$test_provider = 'openai';

echo "Testing API Key Encryption/Decryption\n";
echo "=====================================\n";

try {
    // Test manual encryption/decryption
    $encrypter = \Config\Services::encrypter();
    $encrypted = base64_encode($encrypter->encrypt($test_api_key));
    $decrypted = $encrypter->decrypt(base64_decode($encrypted));
    
    echo "Manual encryption test:\n";
    echo "Original: {$test_api_key}\n";
    echo "Encrypted: {$encrypted}\n";
    echo "Decrypted: {$decrypted}\n";
    echo "Match: " . ($test_api_key === $decrypted ? 'YES' : 'NO') . "\n\n";
    
    // Test through model
    $apiKeysModel = new \App\Models\ApiKeysModel();
    
    // Check if test data already exists
    $existing = $apiKeysModel->where('tenant_id', $test_tenant_id)
                            ->where('provider', $test_provider)
                            ->first();
    
    if ($existing) {
        echo "Cleaning up existing test data...\n";
        $apiKeysModel->where('tenant_id', $test_tenant_id)->delete();
    }
    
    // Insert test data
    helper('hash');
    $test_data = [
        'api_key_id' => generate_hash_id('key'),
        'tenant_id' => $test_tenant_id,
        'name' => 'Test API Key',
        'provider' => $test_provider,
        'api_key' => $test_api_key,
        'is_default' => 1,
        'active' => 1
    ];
    
    echo "Inserting test data through model...\n";
    $result = $apiKeysModel->insert($test_data);
    
    if ($result) {
        echo "Insert successful!\n";
        
        // Retrieve and test decryption
        $retrieved = $apiKeysModel->getDefaultKey($test_tenant_id, $test_provider);
        
        echo "\nRetrieved data:\n";
        echo "Found: " . (!empty($retrieved) ? 'YES' : 'NO') . "\n";
        
        if ($retrieved) {
            echo "Name: " . ($retrieved['name'] ?? 'N/A') . "\n";
            echo "Provider: " . ($retrieved['provider'] ?? 'N/A') . "\n";
            echo "Decrypted API Key: " . ($retrieved['api_key'] ?? 'N/A') . "\n";
            echo "Match: " . (($retrieved['api_key'] ?? '') === $test_api_key ? 'YES' : 'NO') . "\n";
        }
        
        // Clean up
        echo "\nCleaning up test data...\n";
        $apiKeysModel->where('tenant_id', $test_tenant_id)->delete();
        echo "Cleanup complete!\n";
        
    } else {
        echo "Insert failed!\n";
        echo "Errors: " . json_encode($apiKeysModel->errors()) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";