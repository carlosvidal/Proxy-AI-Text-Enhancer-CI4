<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddButtonIdToUsageLogs extends Migration
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
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy existing data
        $this->db->query("INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, button_id, provider, model, has_image, tokens, created_at)
                         SELECT id, tenant_id, user_id, external_id, 
                                NULL as button_id,
                                provider,
                                model,
                                has_image,
                                tokens, created_at
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
        $timestamp = date('YmdHis');
        $this->db->query('CREATE INDEX idx_usage_logs_tenant_user_' . $timestamp . ' ON usage_logs(tenant_id, user_id)');
        $this->db->query('CREATE INDEX idx_usage_logs_tenant_external_' . $timestamp . ' ON usage_logs(tenant_id, external_id)');
        $this->db->query('CREATE INDEX idx_usage_logs_button_' . $timestamp . ' ON usage_logs(button_id)');
        $this->db->query('CREATE INDEX idx_usage_logs_created_at_' . $timestamp . ' ON usage_logs(created_at)');
    }

    public function down()
    {
        // Since we're just adding a nullable column, down migration is not critical
        // But we'll provide it for completeness
        
        // Create temporary table without the new field
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');

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
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data excluding the new field
        $this->db->query("INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, provider, model, has_image, tokens, created_at)
                         SELECT id, tenant_id, user_id, external_id, provider, model, has_image, tokens, created_at
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
        $timestamp = date('YmdHis');
        $this->db->query('CREATE INDEX idx_usage_logs_tenant_user_' . $timestamp . ' ON usage_logs(tenant_id, user_id)');
        $this->db->query('CREATE INDEX idx_usage_logs_tenant_external_' . $timestamp . ' ON usage_logs(tenant_id, external_id)');
        $this->db->query('CREATE INDEX idx_usage_logs_created_at_' . $timestamp . ' ON usage_logs(created_at)');
    }
}
