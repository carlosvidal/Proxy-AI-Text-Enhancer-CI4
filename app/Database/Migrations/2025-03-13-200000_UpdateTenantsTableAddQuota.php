<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTenantsTableAddQuota extends Migration
{
    public function up()
    {
        // Add new columns to tenants table
        $fields = [
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'name'
            ],
            'quota' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1000,
                'after' => 'email'
            ],
            'subscription_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'trial',
                'after' => 'quota'
            ]
        ];

        $this->forge->addColumn('tenants', $fields);
    }

    public function down()
    {
        // Remove columns from tenants table
        $this->forge->dropColumn('tenants', ['email', 'quota', 'subscription_status']);
    }
}
