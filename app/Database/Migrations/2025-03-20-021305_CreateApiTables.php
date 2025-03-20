<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiTables extends Migration
{
    public function up()
    {
        // Create API users table
        $this->forge->addField([
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'quota' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 100000,
                'comment' => 'Monthly token quota'
            ],
            'daily_quota' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 10000,
                'comment' => 'Daily token quota'
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
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('user_id', true);
        $this->forge->addKey(['external_id', 'tenant_id']); 
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('api_users', true);

        // Create API user buttons table
        $this->forge->addField([
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey(['user_id', 'button_id'], true);
        $this->forge->createTable('api_user_buttons', true);
    }

    public function down()
    {
        $this->forge->dropTable('api_user_buttons', true);
        $this->forge->dropTable('api_users', true);
    }
}
