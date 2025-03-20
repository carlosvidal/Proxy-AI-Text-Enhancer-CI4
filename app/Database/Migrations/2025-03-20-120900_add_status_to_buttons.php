<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToButtons extends Migration
{
    public function up()
    {
        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('buttons')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in buttons: ' . implode(', ', $existing_columns));

        // Drop any leftover temporary table and its indices
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('buttons_new', $table_names)) {
            // Drop all indices on the temporary table first
            $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='buttons_new'")->getResultArray();
            foreach ($indices as $index) {
                $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
            }
            $this->forge->dropTable('buttons_new', true);
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        
        // Create temporary table
        $this->forge->createTable('buttons_new');

        // Copy existing data
        $this->db->query("INSERT INTO buttons_new (id, tenant_id, name, description, prompt, status, created_at, updated_at)
                         SELECT id, tenant_id, name, description, prompt, 
                                'active' as status,
                                created_at,
                                updated_at
                         FROM buttons");

        // Drop old table and its indices
        $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='buttons'")->getResultArray();
        foreach ($indices as $index) {
            $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
        }
        $this->forge->dropTable('buttons', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE buttons_new RENAME TO buttons');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE INDEX idx_buttons_tenant_' . $timestamp . ' ON buttons(tenant_id)');
        $this->db->query('CREATE INDEX idx_buttons_status_' . $timestamp . ' ON buttons(status)');
    }

    public function down()
    {
        // Since we're just adding a column with default value, down migration is not critical
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');

        // Drop any leftover temporary table and its indices
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('buttons_new', $table_names)) {
            // Drop all indices on the temporary table first
            $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='buttons_new'")->getResultArray();
            foreach ($indices as $index) {
                $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
            }
            $this->forge->dropTable('buttons_new', true);
        }
        
        // Create temporary table
        $this->forge->createTable('buttons_new');

        // Copy data excluding the new field
        $this->db->query("INSERT INTO buttons_new (id, tenant_id, name, description, prompt, created_at, updated_at)
                         SELECT id, tenant_id, name, description, prompt, created_at, updated_at
                         FROM buttons");

        // Drop old table and its indices
        $indices = $this->db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='buttons'")->getResultArray();
        foreach ($indices as $index) {
            $this->db->query('DROP INDEX IF EXISTS ' . $index['name']);
        }
        $this->forge->dropTable('buttons', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE buttons_new RENAME TO buttons');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE INDEX idx_buttons_tenant_' . $timestamp . ' ON buttons(tenant_id)');
    }
}
