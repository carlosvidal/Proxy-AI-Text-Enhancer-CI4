<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalIdToApiUsers extends Migration
{
    public function up()
    {
        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('api_users')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in api_users: ' . implode(', ', $existing_columns));

        // Drop any leftover temporary table
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('api_users_new', $table_names)) {
            $this->forge->dropTable('api_users_new', true);
        }

        // Create a new table with all fields
        $fields = [
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'email' => [
                'type' => 'VARCHAR',
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1000
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 10000
            ],
            'active' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true
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
        $this->forge->createTable('api_users_new');

        // Copy existing data, using user_id as external_id initially
        $this->db->query("INSERT INTO api_users_new (id, user_id, external_id, tenant_id, name, email, quota, daily_quota, active, last_activity, created_at, updated_at)
                         SELECT id, user_id, user_id as external_id, tenant_id, name, email, quota, daily_quota, active, last_activity, created_at, updated_at
                         FROM api_users");

        // Drop old table
        $this->forge->dropTable('api_users', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE api_users_new RENAME TO api_users');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE UNIQUE INDEX idx_api_users_user_id_' . $timestamp . ' ON api_users(user_id)');
        $this->db->query('CREATE UNIQUE INDEX idx_api_users_external_id_tenant_' . $timestamp . ' ON api_users(external_id, tenant_id)');
        $this->db->query('CREATE INDEX idx_api_users_tenant_' . $timestamp . ' ON api_users(tenant_id)');
    }

    public function down()
    {
        // Since we're just adding a column, down migration is not critical
        // But we'll provide it for completeness
        
        // Create temporary table without the new field
        $fields = [
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'email' => [
                'type' => 'VARCHAR',
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1000
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 10000
            ],
            'active' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true
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

        // Drop any leftover temporary table
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('api_users_new', $table_names)) {
            $this->forge->dropTable('api_users_new', true);
        }
        
        // Create temporary table
        $this->forge->createTable('api_users_new');

        // Copy data excluding the new field
        $this->db->query("INSERT INTO api_users_new (id, user_id, tenant_id, name, email, quota, daily_quota, active, last_activity, created_at, updated_at)
                         SELECT id, user_id, tenant_id, name, email, quota, daily_quota, active, last_activity, created_at, updated_at
                         FROM api_users");

        // Drop old table
        $this->forge->dropTable('api_users', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE api_users_new RENAME TO api_users');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE UNIQUE INDEX idx_api_users_user_id_' . $timestamp . ' ON api_users(user_id)');
        $this->db->query('CREATE INDEX idx_api_users_tenant_' . $timestamp . ' ON api_users(tenant_id)');
    }
}
