<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProviderFieldsToUsageLogs extends Migration
{
    public function up()
    {
        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('usage_logs')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in usage_logs: ' . implode(', ', $existing_columns));

        // Drop any leftover temporary table and its indices
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('usage_logs_new', $table_names)) {
            // Drop all indices on the temporary table first
            $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='usage_logs_new'")->getResultArray();
            foreach ($indices as $index) {
                $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
            }
            $this->forge->dropTable('usage_logs_new', true);
        }

        // Create a new table with all fields
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'unknown'
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'unknown'
            ],
            'has_image' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'tokens' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true
            ],
            'usage_date' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ];

        $this->forge->addField($fields);
        
        // Create temporary table without indices first
        $this->forge->createTable('usage_logs_new', true, ['WITHOUT ROWID' => false]);

        // Copy existing data
        $this->db->query("INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, provider, model, has_image, tokens, usage_date)
                         SELECT id, tenant_id, user_id, external_id, 
                                'unknown' as provider,
                                'unknown' as model,
                                0 as has_image,
                                tokens, usage_date
                         FROM usage_logs");

        // Drop old table and its indices
        $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='usage_logs'")->getResultArray();
        foreach ($indices as $index) {
            $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
        }
        $this->forge->dropTable('usage_logs', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');

        // Add indices with unique names after renaming
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_tenant_user_' . time() . ' ON usage_logs(tenant_id, user_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_tenant_external_' . time() . ' ON usage_logs(tenant_id, external_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_usage_date_' . time() . ' ON usage_logs(usage_date)');
    }

    public function down()
    {
        // Since we're just adding columns with default values, down migration is not critical
        // But we'll provide it for completeness
        
        // Create temporary table without the new fields
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tokens' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true
            ],
            'usage_date' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ];

        $this->forge->addField($fields);

        // Drop any leftover temporary table and its indices
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('usage_logs_new', $table_names)) {
            // Drop all indices on the temporary table first
            $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='usage_logs_new'")->getResultArray();
            foreach ($indices as $index) {
                $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
            }
            $this->forge->dropTable('usage_logs_new', true);
        }
        
        // Create temporary table without indices first
        $this->forge->createTable('usage_logs_new', true, ['WITHOUT ROWID' => false]);

        // Copy data excluding the new fields
        $this->db->query("INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, tokens, usage_date)
                         SELECT id, tenant_id, user_id, external_id, tokens, usage_date
                         FROM usage_logs");

        // Drop old table and its indices
        $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='usage_logs'")->getResultArray();
        foreach ($indices as $index) {
            $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
        }
        $this->forge->dropTable('usage_logs', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');

        // Add indices with unique names after renaming
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_tenant_user_' . time() . ' ON usage_logs(tenant_id, user_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_tenant_external_' . time() . ' ON usage_logs(tenant_id, external_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_usage_date_' . time() . ' ON usage_logs(usage_date)');
    }
}
