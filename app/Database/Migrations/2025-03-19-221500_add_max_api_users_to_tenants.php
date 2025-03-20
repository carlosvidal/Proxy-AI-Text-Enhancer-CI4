<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiUsersToTenants extends Migration
{
    public function up()
    {
        try {
            // Add column if it doesn't exist
            $this->forge->addColumn('tenants', [
                'max_api_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 1,
                    'after' => 'name'
                ]
            ]);
        } catch (\Exception $e) {
            // Column might already exist, that's okay
            log_message('info', 'Column max_api_users might already exist: ' . $e->getMessage());
        }

        // Update existing tenants to have a default value
        $this->db->query("UPDATE tenants SET max_api_users = 1 WHERE max_api_users IS NULL");
    }

    public function down()
    {
        try {
            $this->forge->dropColumn('tenants', 'max_api_users');
        } catch (\Exception $e) {
            // Column might not exist, that's okay
            log_message('info', 'Column max_api_users might not exist: ' . $e->getMessage());
        }
    }
}
