<?php
// Simple script to check buttons table structure without CodeIgniter dependencies
// Run this with: php check_buttons_simple.php

// Find the database file
$possibleDbFiles = [
    'writable/database.db',
    'database.db',
    'app/database.db',
    'writable/database/database.db'
];

$dbFile = null;
foreach ($possibleDbFiles as $file) {
    if (file_exists($file)) {
        $dbFile = $file;
        break;
    }
}

if (!$dbFile) {
    echo "ERROR: Could not find database file. Please specify the correct path.\n";
    echo "Common locations:\n";
    foreach ($possibleDbFiles as $file) {
        echo "  - $file\n";
    }
    exit(1);
}

echo "Using database file: $dbFile\n";

try {
    // Connect to SQLite database
    $db = new SQLite3($dbFile);
    
    echo "Checking buttons table structure...\n";
    
    // Check if table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='buttons'");
    if (!$result->fetchArray()) {
        echo "ERROR: buttons table does not exist!\n";
        exit(1);
    }
    
    // Get table info
    $result = $db->query("PRAGMA table_info(buttons)");
    $columns = [];
    echo "Existing columns:\n";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
        echo "  - {$row['name']} ({$row['type']})\n";
    }
    
    // Check for missing columns
    $requiredColumns = ['temperature', 'active'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $missingColumns[] = $column;
        }
    }
    
    if (empty($missingColumns)) {
        echo "All required columns exist!\n";
    } else {
        echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
        
        // Ask if user wants to add missing columns
        echo "Do you want to add missing columns? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($input) === 'y') {
            foreach ($missingColumns as $column) {
                try {
                    switch ($column) {
                        case 'temperature':
                            $sql = "ALTER TABLE buttons ADD COLUMN temperature DECIMAL(3,2) DEFAULT 0.70 NOT NULL";
                            break;
                        case 'active':
                            $sql = "ALTER TABLE buttons ADD COLUMN active TINYINT(1) DEFAULT 1 NOT NULL";
                            break;
                    }
                    
                    echo "Adding column $column...\n";
                    $db->exec($sql);
                    echo "Successfully added column $column\n";
                } catch (Exception $e) {
                    echo "Failed to add column $column: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // Show final structure
    echo "\nFinal table structure:\n";
    $result = $db->query("PRAGMA table_info(buttons)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "  - {$row['name']} ({$row['type']})\n";
    }
    
    $db->close();
    echo "\nDone!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>