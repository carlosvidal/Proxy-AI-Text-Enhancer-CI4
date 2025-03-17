<?php

// Autoload the application
require 'vendor/autoload.php';
require 'app/Config/Paths.php';

// Use the application configuration
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '/ ') . '/bootstrap.php';

// Create a database connection
$db = \Config\Database::connect();

// Check if the table already exists
$query = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tenant_api_keys'");
$tableExists = (count($query->getResultArray()) > 0);

if (!$tableExists) {
    // Create the tenant_api_keys table
    $db->query("CREATE TABLE `tenant_api_keys` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `api_key_id` VARCHAR(50) NOT NULL UNIQUE,
        `tenant_id` VARCHAR(50) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `provider` VARCHAR(50) NOT NULL,
        `api_key` TEXT NOT NULL,
        `is_default` TINYINT(1) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL
    )");
    
    echo "Table tenant_api_keys created successfully.\n";
} else {
    echo "Table tenant_api_keys already exists.\n";
}

// Check if max_api_keys column exists in tenants table
$query = $db->query("PRAGMA table_info(tenants)");
$columns = $query->getResultArray();
$columnExists = false;

foreach ($columns as $column) {
    if ($column['name'] === 'max_api_keys') {
        $columnExists = true;
        break;
    }
}

if (!$columnExists) {
    // Add max_api_keys column to tenants table
    $db->query("ALTER TABLE tenants ADD COLUMN max_api_keys INTEGER NOT NULL DEFAULT 1");
    
    // Update existing tenants based on their plan
    $db->query("UPDATE tenants SET max_api_keys = 1 WHERE plan = 'free'");
    $db->query("UPDATE tenants SET max_api_keys = 3 WHERE plan = 'basic'");
    $db->query("UPDATE tenants SET max_api_keys = 5 WHERE plan = 'pro'");
    $db->query("UPDATE tenants SET max_api_keys = 10 WHERE plan = 'enterprise'");
    
    echo "Column max_api_keys added to tenants table and updated based on plans.\n";
} else {
    echo "Column max_api_keys already exists in tenants table.\n";
}

echo "Database setup completed.\n";
