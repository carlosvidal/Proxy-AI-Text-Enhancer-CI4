<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsageIdToUsageLogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usage_logs', [
            'usage_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'after' => 'id'
            ]
        ]);

        // Add unique index for usage_id
        $this->forge->addKey('usage_id', true, true, 'idx_usage_logs_usage_id');
    }

    public function down()
    {
        $this->forge->dropColumn('usage_logs', 'usage_id');
    }
}
