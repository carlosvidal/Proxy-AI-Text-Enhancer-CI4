<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToUsageLogs extends Migration
{
    public function up()
    {
        // Add user_id column to usage_logs table
        $fields = [
            'user_id' => [ // References tenant_users.user_id (format: usr-{timestamp}-{random})
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'after' => 'tenant_id'
            ]
        ];

        $this->forge->addColumn('usage_logs', $fields);
        
        // Add index for faster lookups
        $this->forge->addKey(['tenant_id', 'user_id']);
    }

    public function down()
    {
        // Remove user_id column from usage_logs table
        $this->forge->dropColumn('usage_logs', ['user_id']);
    }
}
