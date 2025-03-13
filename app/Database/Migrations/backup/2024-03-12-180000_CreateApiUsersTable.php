<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiUsersTable extends Migration
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
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'quota' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addKey(['tenant_id', 'user_id']); // Index for faster lookups
        $this->forge->createTable('api_users');
    }

    public function down()
    {
        $this->forge->dropTable('api_users');
    }
}
