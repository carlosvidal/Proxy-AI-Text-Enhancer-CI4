<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Fix tenant_users table

class FixTenantUsersTable extends Migration
{
    public function up()
    {
        // Check if table exists first
        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();
        $tables = $db->listTables();
        if (!in_array($prefix . 'tenant_users', $tables)) {
            return;
        }

        // Add role field to tenant_users table
        $fields = [
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
                'default' => 'user',
                'after' => 'name'
            ]
        ];

        $this->forge->addColumn('tenant_users', $fields);

        // Update existing records to have a default role
        $db->query("UPDATE tenant_users SET role = 'user' WHERE role IS NULL");
    }

    public function down()
    {
        $this->forge->dropColumn('tenant_users', 'role');
    }
}
