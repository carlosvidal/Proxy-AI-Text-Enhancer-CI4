<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Create users table

class CreateUsersTable extends Migration
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
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['superadmin', 'tenant'],
                'default' => 'tenant'
            ],
            'tenant_id' => [  // Links to tenants table using hash ID format: ten-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => 1
            ],
            'last_login' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
