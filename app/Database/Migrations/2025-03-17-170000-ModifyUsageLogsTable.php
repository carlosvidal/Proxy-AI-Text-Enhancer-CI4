<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyUsageLogsTable extends Migration
{
    public function up()
    {
        // Primero verificamos si la columna existe
        if (!$this->db->fieldExists('provider', 'usage_logs')) {
            $this->forge->addColumn('usage_logs', [
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'user_id'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('usage_logs', 'provider');
    }
}
