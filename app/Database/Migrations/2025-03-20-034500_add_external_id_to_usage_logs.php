<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalIdToUsageLogs extends Migration
{
    public function up()
    {
        // Add external_id column
        $fields = [
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'after' => 'user_id'
            ]
        ];
        $this->forge->addColumn('usage_logs', $fields);

        // Add index for faster lookups
        $this->forge->addKey(['tenant_id', 'external_id']);
    }

    public function down()
    {
        // Remove external_id column
        $this->forge->dropColumn('usage_logs', 'external_id');
    }
}
