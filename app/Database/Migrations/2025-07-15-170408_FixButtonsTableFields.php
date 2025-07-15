<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixButtonsTableFields extends Migration
{
    public function up()
    {
        // Check if buttons table exists
        if (!$this->db->tableExists('buttons')) {
            log_message('error', 'Buttons table does not exist, skipping migration');
            return;
        }
        
        // Get existing columns
        $existingColumns = $this->db->getFieldNames('buttons');
        log_message('info', 'Existing buttons table columns: ' . implode(', ', $existingColumns));
        
        // Add temperature field if it doesn't exist
        if (!in_array('temperature', $existingColumns)) {
            try {
                $this->forge->addColumn('buttons', [
                    'temperature' => [
                        'type' => 'DECIMAL',
                        'constraint' => '3,2',
                        'default' => '0.70',
                        'null' => false
                    ]
                ]);
                log_message('info', 'Added temperature column to buttons table');
            } catch (\Exception $e) {
                log_message('error', 'Failed to add temperature column: ' . $e->getMessage());
            }
        } else {
            log_message('info', 'Temperature column already exists in buttons table');
        }
        
        // Add active field if it doesn't exist
        if (!in_array('active', $existingColumns)) {
            try {
                $this->forge->addColumn('buttons', [
                    'active' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 1,
                        'null' => false
                    ]
                ]);
                log_message('info', 'Added active column to buttons table');
            } catch (\Exception $e) {
                log_message('error', 'Failed to add active column: ' . $e->getMessage());
            }
        } else {
            log_message('info', 'Active column already exists in buttons table');
        }
    }

    public function down()
    {
        // Check if buttons table exists
        if (!$this->db->tableExists('buttons')) {
            log_message('error', 'Buttons table does not exist, skipping rollback');
            return;
        }
        
        // Get existing columns
        $existingColumns = $this->db->getFieldNames('buttons');
        
        // Remove temperature field if it exists
        if (in_array('temperature', $existingColumns)) {
            try {
                $this->forge->dropColumn('buttons', 'temperature');
                log_message('info', 'Removed temperature column from buttons table');
            } catch (\Exception $e) {
                log_message('error', 'Failed to remove temperature column: ' . $e->getMessage());
            }
        }
        
        // Remove active field if it exists
        if (in_array('active', $existingColumns)) {
            try {
                $this->forge->dropColumn('buttons', 'active');
                log_message('info', 'Removed active column from buttons table');
            } catch (\Exception $e) {
                log_message('error', 'Failed to remove active column: ' . $e->getMessage());
            }
        }
    }
}
