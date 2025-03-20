<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalIdToUsageLogs extends Migration
{
    public function up()
    {
        // First, clean up any leftover temporary tables from failed migrations
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('usage_logs_new', $table_names)) {
            $this->forge->dropTable('usage_logs_new', true);
        }

        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('usage_logs')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in usage_logs: ' . implode(', ', $existing_columns));

        // Create the new table with minimal structure
        $this->forge->addField([
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id']);
        $this->forge->addKey(['tenant_id', 'external_id']);
        $this->forge->addKey('created_at');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Build the column list for the INSERT based on existing columns
        $copy_columns = array_intersect(['id', 'tenant_id', 'user_id', 'tokens', 'created_at'], $existing_columns);
        $select_columns = implode(', ', $copy_columns);

        // Copy data from old table to new table, using user_id as external_id initially
        $this->db->query("INSERT INTO usage_logs_new (${select_columns}, external_id)
                         SELECT ${select_columns}, user_id
                         FROM usage_logs");

        // Drop old table
        $this->forge->dropTable('usage_logs', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');
    }

    public function down()
    {
        // Clean up any leftover temporary tables first
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('usage_logs_new', $table_names)) {
            $this->forge->dropTable('usage_logs_new', true);
        }

        // Create temporary table without external_id
        $this->forge->addField([
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
            'tokens' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id']);
        $this->forge->addKey('created_at');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data from current table to new table
        $this->db->query('INSERT INTO usage_logs_new (id, tenant_id, user_id, tokens, created_at)
                         SELECT id, tenant_id, user_id, tokens, created_at
                         FROM usage_logs');

        // Drop old table
        $this->forge->dropTable('usage_logs', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');
    }
}
