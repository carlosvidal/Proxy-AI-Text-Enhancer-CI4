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

        // For SQLite, we need to:
        // 1. Create a new table with the desired structure
        // 2. Copy data from old table to new table
        // 3. Drop old table
        // 4. Rename new table to old name

        // First, create the new table
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
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id']);
        $this->forge->addKey(['tenant_id', 'external_id']);
        $this->forge->addKey('usage_date');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data from old table to new table, using user_id as external_id initially
        $this->db->query('INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, provider, model, has_image, tokens, usage_date)
                         SELECT id, tenant_id, user_id, user_id, provider, model, has_image, tokens, usage_date
                         FROM usage_logs');

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

        // For down migration, we'll do the reverse process
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
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id']);
        $this->forge->addKey('usage_date');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data from current table to new table
        $this->db->query('INSERT INTO usage_logs_new (id, tenant_id, user_id, provider, model, has_image, tokens, usage_date)
                         SELECT id, tenant_id, user_id, provider, model, has_image, tokens, usage_date
                         FROM usage_logs');

        // Drop old table
        $this->forge->dropTable('usage_logs', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');
    }
}
