<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTenantUsersTable extends Migration
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
            'tenant_id' => [ // References tenants.tenant_id (format: ten-{timestamp}-{random})
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
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
        $this->forge->addKey('tenant_id');
        $this->forge->addUniqueKey(['tenant_id', 'email']);
        $this->forge->createTable('tenant_users');
    }

    public function down()
    {
        $this->forge->dropTable('tenant_users');
    }
}
