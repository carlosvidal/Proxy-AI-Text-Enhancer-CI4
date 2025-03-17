<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiKeysToTenants extends Migration
{
    public function up()
    {
        $fields = [
            'max_api_keys' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'after' => 'max_domains'
            ]
        ];

        $this->forge->addColumn('tenants', $fields);

        // Update existing tenants based on their plan
        $this->db->query("UPDATE tenants SET max_api_keys = 1 WHERE plan_code = 'free'");
        $this->db->query("UPDATE tenants SET max_api_keys = 5 WHERE plan_code = 'pro'");
        $this->db->query("UPDATE tenants SET max_api_keys = 999 WHERE plan_code = 'enterprise'");
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', 'max_api_keys');
    }
}
