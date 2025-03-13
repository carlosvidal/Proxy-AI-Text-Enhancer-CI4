<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'tenant_id' => [  // Primary key using format: ten-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'api_key' => [  // Will be encrypted using encryption.key
                'type' => 'TEXT',
                'null' => true
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

        $this->forge->addPrimaryKey('tenant_id');
        $this->forge->createTable('tenants');
    }

    public function down()
    {
        $this->forge->dropTable('tenants');
    }
}
