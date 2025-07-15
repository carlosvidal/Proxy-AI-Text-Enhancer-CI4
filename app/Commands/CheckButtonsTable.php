<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckButtonsTable extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:check-buttons';
    protected $description = 'Check and fix buttons table structure';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        CLI::write('Checking buttons table structure...', 'yellow');
        
        // Check if table exists
        if (!$db->tableExists('buttons')) {
            CLI::error('ERROR: buttons table does not exist!');
            return;
        }
        
        // Get existing columns
        $columns = $db->getFieldNames('buttons');
        CLI::write('Existing columns: ' . implode(', ', $columns), 'green');
        
        // Check for missing columns
        $requiredColumns = ['temperature', 'active'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                $missingColumns[] = $column;
            }
        }
        
        if (empty($missingColumns)) {
            CLI::write('All required columns exist!', 'green');
        } else {
            CLI::write('Missing columns: ' . implode(', ', $missingColumns), 'red');
            
            // Ask if user wants to add missing columns
            if (CLI::prompt('Do you want to add missing columns?', ['y', 'n']) === 'y') {
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
                        
                        CLI::write("Adding column $column...", 'yellow');
                        $db->query($sql);
                        CLI::write("Successfully added column $column", 'green');
                    } catch (\Exception $e) {
                        CLI::error("Failed to add column $column: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Show final structure
        CLI::write("\nFinal table structure:", 'yellow');
        $fields = $db->getFieldData('buttons');
        foreach ($fields as $field) {
            CLI::write("- {$field->name} ({$field->type})", 'cyan');
        }
        
        CLI::write("\nDone!", 'green');
    }
}