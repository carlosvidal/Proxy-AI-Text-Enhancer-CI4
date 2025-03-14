<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTenantUsersAddQuota extends Migration
{
    public function up()
    {
        // Add quota and api_key columns to tenant_users table
        $fields = [
            'quota' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1000,
                'after' => 'name'
            ],
            'api_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'quota'
            ]
        ];

        $this->forge->addColumn('tenant_users', $fields);
    }

    public function down()
    {
        // Remove columns from tenant_users table
        $this->forge->dropColumn('tenant_users', ['quota', 'api_key']);
    }
}
