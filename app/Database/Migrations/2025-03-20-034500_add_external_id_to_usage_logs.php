<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalIdToUsageLogs extends Migration
{
    public function up()
    {
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
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'request_timestamp' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'request_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true
            ],
            'request_data' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_data' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_time' => [
                'type' => 'FLOAT',
                'null' => true
            ],
            'tokens_used' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true
            ],
            'status' => [
                'type' => 'VARCHAR',  // SQLite doesn't support ENUM
                'constraint' => 10,
                'default' => 'success'
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id', 'button_id']);
        $this->forge->addKey(['tenant_id', 'external_id']);
        $this->forge->addKey('request_timestamp');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data from old table to new table, using user_id as external_id initially
        $this->db->query('INSERT INTO usage_logs_new (id, tenant_id, user_id, external_id, button_id, request_timestamp, request_ip, request_data, response_data, response_time, tokens_used, status, error_message, created_at)
                         SELECT id, tenant_id, user_id, user_id, button_id, request_timestamp, request_ip, request_data, response_data, response_time, tokens_used, status, error_message, created_at
                         FROM usage_logs');

        // Drop old table
        $this->forge->dropTable('usage_logs');

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');
    }

    public function down()
    {
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
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'request_timestamp' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'request_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true
            ],
            'request_data' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_data' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_time' => [
                'type' => 'FLOAT',
                'null' => true
            ],
            'tokens_used' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'success'
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'user_id', 'button_id']);
        $this->forge->addKey('request_timestamp');
        
        // Create temporary table
        $this->forge->createTable('usage_logs_new');

        // Copy data from current table to new table
        $this->db->query('INSERT INTO usage_logs_new (id, tenant_id, user_id, button_id, request_timestamp, request_ip, request_data, response_data, response_time, tokens_used, status, error_message, created_at)
                         SELECT id, tenant_id, user_id, button_id, request_timestamp, request_ip, request_data, response_data, response_time, tokens_used, status, error_message, created_at
                         FROM usage_logs');

        // Drop old table
        $this->forge->dropTable('usage_logs');

        // Rename new table to old name
        $this->db->query('ALTER TABLE usage_logs_new RENAME TO usage_logs');
    }
}
