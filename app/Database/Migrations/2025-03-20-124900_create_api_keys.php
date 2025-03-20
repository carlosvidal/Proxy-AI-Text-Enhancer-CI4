<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
{
    public function up()
    {
        // First check if tenant_api_keys exists
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        $has_old_table = in_array('tenant_api_keys', $table_names);

        // Create new api_keys table
        $this->forge->addField([
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('api_keys', true);

        // Create index for tenant_id
        $this->db->query('CREATE INDEX idx_api_keys_tenant_id ON api_keys(tenant_id)');

        // If old table exists, migrate data
        if ($has_old_table) {
            // Get schema of old table
            $old_schema = $this->db->query("PRAGMA table_info('tenant_api_keys')")->getResultArray();
            $old_columns = array_column($old_schema, 'name');

            // Check if old table has provider column
            $has_provider = in_array('provider', $old_columns);

            // Migrate data
            if ($has_provider) {
                $this->db->query("INSERT INTO api_keys (tenant_id, name, provider, api_key, is_default, active, created_at, updated_at)
                                SELECT tenant_id, 
                                       'OpenAI Key' as name,
                                       provider,
                                       api_key,
                                       1 as is_default,
                                       1 as active,
                                       datetime('now') as created_at,
                                       datetime('now') as updated_at
                                FROM tenant_api_keys");
            } else {
                $this->db->query("INSERT INTO api_keys (tenant_id, name, provider, api_key, is_default, active, created_at, updated_at)
                                SELECT tenant_id, 
                                       'OpenAI Key' as name,
                                       'openai' as provider,
                                       api_key,
                                       1 as is_default,
                                       1 as active,
                                       datetime('now') as created_at,
                                       datetime('now') as updated_at
                                FROM tenant_api_keys");
            }

            // Drop old table
            $this->forge->dropTable('tenant_api_keys', true);
        }
    }

    public function down()
    {
        // On rollback, we recreate the tenant_api_keys table and migrate data back
        $this->forge->addField([
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'openai'
            ]
        ]);

        $this->forge->createTable('tenant_api_keys', true);

        // Migrate data back
        $this->db->query("INSERT INTO tenant_api_keys (tenant_id, api_key, provider)
                        SELECT tenant_id, api_key, provider
                        FROM api_keys
                        WHERE is_default = 1");

        // Drop new table
        $this->forge->dropTable('api_keys', true);
    }
}
