<?php

// Debug script to check button configuration
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once FCPATH . 'vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = FCPATH . 'app/Config/Paths.php';
require $pathsConfig;
$paths = new Config\Paths();

$bootstrap = FCPATH . 'app/Config/Boot/production.php';
require $bootstrap;

$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

$tenant_id = 'ten-67d88d1d-111ae225';
$button_id = 'BTN00001';

echo "=== DEBUG BUTTON CONFIGURATION ===\n\n";

// Check all buttons for this tenant
echo "1. All buttons for tenant $tenant_id:\n";
$allButtonsQuery = $db->query("SELECT button_id, status, provider, model, api_key_id, name FROM buttons WHERE tenant_id = ?", [$tenant_id]);
$allButtons = $allButtonsQuery ? $allButtonsQuery->getResultArray() : [];

if (empty($allButtons)) {
    echo "   No buttons found for tenant $tenant_id\n";
} else {
    foreach ($allButtons as $button) {
        echo "   - ID: {$button['button_id']}, Status: {$button['status']}, Provider: {$button['provider']}, Model: {$button['model']}, API Key ID: {$button['api_key_id']}, Name: {$button['name']}\n";
    }
}

echo "\n2. Searching for specific button $button_id:\n";
$buttonQuery = $db->query("SELECT * FROM buttons WHERE button_id = ? AND status = 'active'", [$button_id]);
$button = $buttonQuery ? $buttonQuery->getRowArray() : null;

if ($button) {
    echo "   Button found:\n";
    foreach ($button as $key => $value) {
        echo "   - $key: $value\n";
    }
} else {
    echo "   Button $button_id not found or not active\n";
}

echo "\n3. All API keys for tenant $tenant_id:\n";
$apiKeysModel = new \App\Models\ApiKeysModel();
$apiKeys = $apiKeysModel->getTenantApiKeys($tenant_id);

if (empty($apiKeys)) {
    echo "   No API keys found for tenant $tenant_id\n";
} else {
    foreach ($apiKeys as $key) {
        echo "   - ID: {$key['api_key_id']}, Name: {$key['name']}, Provider: {$key['provider']}, Default: {$key['is_default']}, Active: {$key['active']}\n";
        echo "     API Key (first 10 chars): " . substr($key['api_key'], 0, 10) . "...\n";
    }
}

echo "\n=== END DEBUG ===\n";