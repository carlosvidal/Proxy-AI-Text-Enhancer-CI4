<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unique' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'default' => 1000
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'default' => 10000
            ],
            'active' => [
                'type' => 'INTEGER',
                'constraint' => 1,
                'default' => 1
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true
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
        $this->forge->createTable('api_users');
    }

    public function down()
    {
        $this->forge->dropTable('api_users');
    }
}
