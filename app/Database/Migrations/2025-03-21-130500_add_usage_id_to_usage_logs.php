<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsageIdToUsageLogs extends Migration
{
    public function up()
    {
        // Primero agregamos la columna como nullable
        $this->forge->addColumn('usage_logs', [
            'usage_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'id'
            ]
        ]);

        // Generamos IDs para registros existentes
        $db = db_connect();
        $rows = $db->table('usage_logs')->get()->getResultArray();
        foreach ($rows as $row) {
            $usage_id = 'usage-' . bin2hex(random_bytes(8));
            $db->table('usage_logs')
                ->where('id', $row['id'])
                ->update(['usage_id' => $usage_id]);
        }

        // Ahora hacemos la columna NOT NULL
        $this->forge->modifyColumn('usage_logs', [
            'usage_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ]
        ]);

        // Agregamos el índice único
        $this->forge->addKey('usage_id', true, true, 'idx_usage_logs_usage_id');
    }

    public function down()
    {
        $this->forge->dropColumn('usage_logs', 'usage_id');
    }
}
