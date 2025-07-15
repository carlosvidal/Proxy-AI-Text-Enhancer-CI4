<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixButtonsTableFields extends Migration
{
    public function up()
    {
        // Add temperature field to buttons table (SQLite compatible - no AFTER clause)
        $this->forge->addColumn('buttons', [
            'temperature' => [
                'type' => 'DECIMAL',
                'constraint' => '3,2',
                'default' => '0.70',
                'null' => false
            ]
        ]);
        
        // Add active field to buttons table (SQLite compatible - no AFTER clause)
        $this->forge->addColumn('buttons', [
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        // Remove temperature field from buttons table
        $this->forge->dropColumn('buttons', 'temperature');
        
        // Remove active field from buttons table
        $this->forge->dropColumn('buttons', 'active');
    }
}
