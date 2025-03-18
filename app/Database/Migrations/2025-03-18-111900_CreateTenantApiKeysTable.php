<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantApiKeysTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tenant_api_keys')) {
            return; // Skip if table already exists
        }

        $this->forge->addField([
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'api_key' => [
                'type' => 'TEXT',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('api_key_id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('tenant_api_keys');
    }

    public function down()
    {
        $this->forge->dropTable('tenant_api_keys');
    }
}
