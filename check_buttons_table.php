<?php
// Script to check and fix buttons table structure
// Run this with: php check_buttons_table.php

require_once 'app/Config/Database.php';
require_once 'system/Database/Database.php';

// Load CodeIgniter
require_once 'system/CodeIgniter.php';

// Get database instance
$db = \Config\Database::connect();

echo "Checking buttons table structure...\n";

// Check if table exists
if (!$db->tableExists('buttons')) {
    echo "ERROR: buttons table does not exist!\n";
    exit(1);
}

// Get existing columns
$columns = $db->getFieldNames('buttons');
echo "Existing columns: " . implode(', ', $columns) . "\n";

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
    
    // Try to add missing columns
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
            $db->query($sql);
            echo "Successfully added column $column\n";
        } catch (Exception $e) {
            echo "Failed to add column $column: " . $e->getMessage() . "\n";
        }
    }
}

// Show final structure
echo "\nFinal table structure:\n";
$fields = $db->getFieldData('buttons');
foreach ($fields as $field) {
    echo "- {$field->name} ({$field->type})\n";
}

echo "\nDone!\n";
?>