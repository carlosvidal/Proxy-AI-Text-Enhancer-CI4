<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantApiKeysTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'api_key_id' => [  // Unique identifier using format: key-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'tenant_id' => [  // References tenants.tenant_id
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'is_default' => [
                'type' => 'BOOLEAN',
                'default' => 0
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => 1
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('api_key_id');
        $this->forge->addKey('tenant_id');
        $this->forge->addKey(['tenant_id', 'provider']);
        
        $this->forge->createTable('tenant_api_keys');
    }

    public function down()
    {
        $this->forge->dropTable('tenant_api_keys');
    }
}
