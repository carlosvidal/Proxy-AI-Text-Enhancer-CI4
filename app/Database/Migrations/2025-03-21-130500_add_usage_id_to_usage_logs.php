<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsageIdToUsageLogs extends Migration
{
    public function up()
    {
        $db = db_connect();
        
        // 1. Crear tabla temporal con la nueva estructura
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'usage_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tokens' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 0
            ],
            'cost' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0
            ],
            'has_image' => [
                'type' => 'INTEGER',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'default' => 'success'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('usage_id', true, true, 'idx_usage_logs_usage_id');
        $this->forge->createTable('usage_logs_temp');

        // 2. Copiar datos existentes a la tabla temporal
        $rows = $db->table('usage_logs')->get()->getResultArray();
        foreach ($rows as $row) {
            $row['usage_id'] = 'usage-' . bin2hex(random_bytes(8));
            $db->table('usage_logs_temp')->insert($row);
        }

        // 3. Eliminar tabla original
        $this->forge->dropTable('usage_logs');

        // 4. Renombrar tabla temporal
        $db->query('ALTER TABLE usage_logs_temp RENAME TO usage_logs');
    }

    public function down()
    {
        // No podemos revertir esto de manera segura
        // ya que perderíamos los usage_ids generados
    }
}
