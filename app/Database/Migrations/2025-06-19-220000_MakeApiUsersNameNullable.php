<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeApiUsersNameNullable extends Migration
{
    public function up()
    {
        // SQLite doesn't support ALTER COLUMN directly, so we need to recreate the table
        
        // Create new table with nullable name
        $this->forge->addField([
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true  // Make name nullable
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => true
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'default' => 10000,
                'null' => false
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        
        $this->forge->addPrimaryKey('user_id');
        $this->forge->createTable('api_users_new', true);
        
        // Copy data from old table to new table
        $this->db->query('INSERT INTO api_users_new (user_id, tenant_id, external_id, name, email, quota, daily_quota, active, created_at, updated_at, last_activity) 
                         SELECT user_id, tenant_id, external_id, name, email, quota, daily_quota, active, created_at, updated_at, last_activity 
                         FROM api_users');
        
        // Drop old table and rename new table
        $this->forge->dropTable('api_users');
        $this->db->query('ALTER TABLE api_users_new RENAME TO api_users');
    }

    public function down()
    {
        // Reverse the migration by making name NOT NULL again
        // This is a simplified reversal - in practice you might want more sophisticated handling
        
        // Create table with NOT NULL name
        $this->forge->addField([
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false  // Make name NOT NULL again
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => true
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'default' => 10000,
                'null' => false
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        
        $this->forge->addPrimaryKey('user_id');
        $this->forge->createTable('api_users_new', true);
        
        // Copy data (only rows with non-null names)
        $this->db->query('INSERT INTO api_users_new (user_id, tenant_id, external_id, name, email, quota, daily_quota, active, created_at, updated_at, last_activity) 
                         SELECT user_id, tenant_id, external_id, name, email, quota, daily_quota, active, created_at, updated_at, last_activity 
                         FROM api_users WHERE name IS NOT NULL');
        
        $this->forge->dropTable('api_users');
        $this->db->query('ALTER TABLE api_users_new RENAME TO api_users');
    }
}