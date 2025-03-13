<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTenantUsersTable extends Migration
{
    public function up()
    {
        // Drop the old table
        $this->forge->dropTable('tenant_users', true);

        // Create the new table with updated schema
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1000
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

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'user_id']);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('tenant_users');
    }

    public function down()
    {
        $this->forge->dropTable('tenant_users', true);
    }
}
