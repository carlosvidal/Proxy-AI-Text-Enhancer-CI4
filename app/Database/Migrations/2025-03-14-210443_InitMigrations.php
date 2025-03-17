<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InitMigrations extends Migration
{
    public function up()
    {
        // Create tenant_api_keys table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('api_key_id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('tenant_api_keys');
    }

    public function down()
    {
        // Drop tenant_api_keys table
        $this->forge->dropTable('tenant_api_keys', true);
    }
}
