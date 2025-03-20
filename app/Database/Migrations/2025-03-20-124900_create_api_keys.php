<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'active' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('api_keys');

        // Eliminamos la tabla tenant_api_keys si existe
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tenant_api_keys"')->getResultArray();
        if (!empty($tables)) {
            $this->forge->dropTable('tenant_api_keys');
        }
    }

    public function down()
    {
        $this->forge->dropTable('api_keys');
    }
}
