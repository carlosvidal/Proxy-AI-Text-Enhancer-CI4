<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateApiKeysToUseHashIds extends Migration
{
    public function up()
    {
        // Create the new table structure
        $this->forge->addField([
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
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
            ]
        ]);
        
        $this->forge->addKey('api_key_id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_keys');
    }

    public function down()
    {
        $this->forge->dropTable('api_keys');
    }
}
