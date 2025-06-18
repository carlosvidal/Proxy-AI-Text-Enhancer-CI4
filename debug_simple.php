<?php

// Simple debug script without CodeIgniter bootstrap
$tenant_id = 'ten-67d88d1d-111ae225';
$button_id = 'BTN00001';

echo "=== DEBUG BUTTON CONFIGURATION (Simple Version) ===\n\n";

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database connection - check for SQLite first
$dbPath = getenv('database.default.database');
if (!$dbPath) {
    $dbPath = 'writable/database.sqlite';  // Default path based on WRITEPATH
}

echo "Environment database setting: " . (getenv('database.default.database') ?: 'not set') . "\n";
echo "Attempting to connect to SQLite database: $dbPath\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Database file exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";

// Try common SQLite paths - prioritize writable/database.sqlite
$possiblePaths = [
    'writable/database.sqlite',  // Most likely location based on config
    $dbPath,
    '../writable/database.sqlite',
    'database.sqlite',
    'app/writable/database.sqlite'
];

echo "Checking possible database paths:\n";
foreach ($possiblePaths as $path) {
    echo "  - $path: " . (file_exists($path) ? 'EXISTS' : 'not found') . "\n";
}
echo "\n";

try {
    // Find the first existing SQLite database
    $foundPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $foundPath = $path;
            break;
        }
    }
    
    if ($foundPath) {
        echo "Using SQLite database: $foundPath\n\n";
        $pdo = new PDO("sqlite:$foundPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "SQLite connection successful!\n\n";
    } else {
        echo "No SQLite database found, trying MySQL...\n";
        // Fallback to MySQL if SQLite file doesn't exist
        $host = getenv('database.default.hostname') ?: 'localhost';
        $database = getenv('database.default.database') ?: 'llmproxy';
        $username = getenv('database.default.username') ?: 'root';
        $password = getenv('database.default.password') ?: '';
        
        echo "Trying MySQL: $host/$database as $username\n\n";
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "MySQL connection successful!\n\n";
    }
    
    echo "Database connection successful!\n\n";
    
    // First, let's see what tables exist
    echo "Available tables in database:\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   No tables found in database!\n";
        echo "   This means migrations need to be run.\n\n";
        echo "Please run the following command on your server:\n";
        echo "   php spark migrate\n\n";
        return;
    } else {
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
    }
    echo "\n";
    
    // Check if required tables exist
    $requiredTables = ['buttons', 'api_keys', 'tenants'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "Missing required tables: " . implode(', ', $missingTables) . "\n";
        echo "Please run: php spark migrate\n\n";
        return;
    }
    
    // Check all buttons for this tenant
    echo "1. All buttons for tenant $tenant_id:\n";
    $stmt = $pdo->prepare("SELECT button_id, status, provider, model, api_key_id, name FROM buttons WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $buttons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($buttons)) {
        echo "   No buttons found for tenant $tenant_id\n";
    } else {
        foreach ($buttons as $button) {
            echo "   - ID: {$button['button_id']}, Status: {$button['status']}, Provider: {$button['provider']}, Model: {$button['model']}, API Key ID: {$button['api_key_id']}, Name: {$button['name']}\n";
        }
    }
    
    echo "\n2. Searching for specific button $button_id:\n";
    $stmt = $pdo->prepare("SELECT * FROM buttons WHERE button_id = ? AND status = 'active'");
    $stmt->execute([$button_id]);
    $button = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($button) {
        echo "   Button found:\n";
        foreach ($button as $key => $value) {
            echo "   - $key: $value\n";
        }
    } else {
        echo "   Button $button_id not found or not active\n";
        
        // Check if button exists but inactive
        $stmt = $pdo->prepare("SELECT * FROM buttons WHERE button_id = ?");
        $stmt->execute([$button_id]);
        $inactiveButton = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inactiveButton) {
            echo "   Button $button_id exists but is inactive (status: {$inactiveButton['status']})\n";
        }
    }
    
    echo "\n3. All API keys for tenant $tenant_id:\n";
    $stmt = $pdo->prepare("SELECT api_key_id, name, provider, is_default, active, api_key FROM api_keys WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($apiKeys)) {
        echo "   No API keys found for tenant $tenant_id\n";
    } else {
        foreach ($apiKeys as $key) {
            echo "   - ID: {$key['api_key_id']}, Name: {$key['name']}, Provider: {$key['provider']}, Default: {$key['is_default']}, Active: {$key['active']}\n";
            echo "     API Key (first 10 chars): " . substr($key['api_key'], 0, 10) . "...\n";
            echo "     API Key length: " . strlen($key['api_key']) . " chars\n";
            echo "     Looks encrypted: " . (preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $key['api_key']) && strlen($key['api_key']) > 50 ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n4. Check if button BTN00001 exists anywhere:\n";
    $stmt = $pdo->prepare("SELECT * FROM buttons WHERE button_id = ?");
    $stmt->execute([$button_id]);
    $anyButton = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($anyButton) {
        echo "   Button $button_id exists:\n";
        foreach ($anyButton as $key => $value) {
            echo "   - $key: $value\n";
        }
    } else {
        echo "   Button $button_id does not exist in database\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";