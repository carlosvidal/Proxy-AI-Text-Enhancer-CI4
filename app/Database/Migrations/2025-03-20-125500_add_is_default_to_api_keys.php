<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsDefaultToApiKeys extends Migration
{
    public function up()
    {
        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('api_keys')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in api_keys: ' . implode(', ', $existing_columns));

        // Drop any leftover temporary table
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('api_keys_new', $table_names)) {
            $this->forge->dropTable('api_keys_new', true);
        }

        // Create a new table with all fields
        $fields = [
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'is_default' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 0
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
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        
        // Create temporary table
        $this->forge->createTable('api_keys_new');

        // Copy existing data
        $this->db->query("INSERT INTO api_keys_new (id, tenant_id, name, provider, api_key, is_default, active, created_at, updated_at)
                         SELECT id, tenant_id, name, provider, api_key, 0 as is_default, active, created_at, updated_at
                         FROM api_keys");

        // For each tenant, set their first API key as default
        $tenants = $this->db->query("SELECT DISTINCT tenant_id FROM api_keys_new")->getResultArray();
        foreach ($tenants as $tenant) {
            $first_key = $this->db->query("SELECT id FROM api_keys_new WHERE tenant_id = ? ORDER BY created_at ASC LIMIT 1", [$tenant['tenant_id']])->getRowArray();
            if ($first_key) {
                $this->db->query("UPDATE api_keys_new SET is_default = 1 WHERE id = ?", [$first_key['id']]);
            }
        }

        // Drop old table
        $this->forge->dropTable('api_keys', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE api_keys_new RENAME TO api_keys');

        // Add indices
        $timestamp = date('YmdHis');
        $this->db->query('CREATE INDEX idx_api_keys_tenant_id_' . $timestamp . ' ON api_keys(tenant_id)');
    }

    public function down()
    {
        // Since we're just adding a column, down migration is not critical
        return;
    }
}
