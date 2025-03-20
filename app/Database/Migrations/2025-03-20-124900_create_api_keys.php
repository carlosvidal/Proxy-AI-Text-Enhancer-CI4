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
        $this->forge->createTable('api_keys', true);

        // Create index for tenant_id
        $this->db->query('CREATE INDEX idx_api_keys_tenant_id ON api_keys(tenant_id)');

        // Migrate data from tenant_api_keys if exists
        if ($has_old_table) {
            helper('hash');
            
            // Get all records from tenant_api_keys
            $old_keys = $this->db->table('tenant_api_keys')->get()->getResultArray();
            
            // Insert each record with a new hash ID
            foreach ($old_keys as $key) {
                $this->db->table('api_keys')->insert([
                    'api_key_id' => generate_hash_id('key'),
                    'tenant_id' => $key['tenant_id'],
                    'name' => 'OpenAI Key',
                    'provider' => $key['provider'],
                    'api_key' => $key['api_key'],
                    'is_default' => 1,
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
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
