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

        // Create new table with is_default field
        $this->forge->addField([
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'api_key' => [
                'type' => 'TEXT',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ]);

        $this->forge->addKey('api_key_id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_keys_new');

        // Copy data from old table with generated hash IDs
        helper('hash');
        $old_keys = $this->db->table('api_keys')->get()->getResultArray();
        
        // Create a mapping of old IDs to new hash IDs
        $id_mapping = [];
        foreach ($old_keys as $key) {
            $hash_id = generate_hash_id('key');
            $key['api_key_id'] = $hash_id;
            
            // Store mapping for buttons update
            $id_mapping[$key['api_key_id']] = $hash_id;
            
            // Remove old id
            unset($key['api_key_id']);
            
            // Insert into new table
            $this->db->table('api_keys_new')->insert($key);
        }

        // Update references in buttons table
        $buttons = $this->db->table('buttons')->get()->getResultArray();
        foreach ($buttons as $button) {
            if (isset($button['api_key_id']) && isset($id_mapping[$button['api_key_id']])) {
                $this->db->table('buttons')
                         ->where('id', $button['id'])
                         ->update(['api_key_id' => $id_mapping[$button['api_key_id']]]);
            }
        }

        // For each tenant, set their first API key as default
        $tenants = $this->db->query("SELECT DISTINCT tenant_id FROM api_keys_new")->getResultArray();
        foreach ($tenants as $tenant) {
            $first_key = $this->db->query("SELECT api_key_id FROM api_keys_new WHERE tenant_id = ? ORDER BY created_at ASC LIMIT 1", [$tenant['tenant_id']])->getRowArray();
            if ($first_key) {
                $this->db->query("UPDATE api_keys_new SET is_default = 1 WHERE api_key_id = ?", [$first_key['api_key_id']]);
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
        // Remove is_default column
        $this->forge->dropColumn('api_keys', 'is_default');
    }
}
