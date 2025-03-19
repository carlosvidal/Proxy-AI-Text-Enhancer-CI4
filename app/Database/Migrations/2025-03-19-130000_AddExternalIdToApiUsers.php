<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalIdToApiUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('api_users', [
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'user_id'
            ],
        ]);

        // Add an index on tenant_id + external_id for faster lookups
        $this->db->query('CREATE INDEX idx_api_users_tenant_external ON api_users(tenant_id, external_id)');
    }

    public function down()
    {
        // Remove the index first
        $this->db->query('DROP INDEX IF EXISTS idx_api_users_tenant_external');
        
        // Then drop the column
        $this->forge->dropColumn('api_users', 'external_id');
    }
}