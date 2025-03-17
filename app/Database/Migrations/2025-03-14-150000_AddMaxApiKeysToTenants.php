<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiKeysToTenants extends Migration
{
    public function up()
    {
        // First check if the column already exists to avoid duplicate column errors
        $fields = $this->db->getFieldData('tenants');
        $columnExists = false;
        foreach ($fields as $field) {
            if ($field->name === 'max_api_keys') {
                $columnExists = true;
                break;
            }
        }

        // Only add the column if it doesn't exist
        if (!$columnExists) {
            $this->forge->addColumn('tenants', [
                'max_api_keys' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'after' => 'max_domains'
                ]
            ]);
        }

        // Set a default value for all tenants without checking plan_code
        $this->db->query("UPDATE tenants SET max_api_keys = 1 WHERE max_api_keys IS NULL");
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', 'max_api_keys');
    }
}
