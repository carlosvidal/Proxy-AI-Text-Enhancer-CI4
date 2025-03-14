<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Create api_users table

class CreateApiUsersTable extends Migration
{
    public function up()
    {
        // Create API users table
        $this->forge->addField([
            'user_id' => [ // Format: usr-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'comment' => 'Internal ID with format: usr-{timestamp}-{random}'
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'External ID required for API consumption'
            ],
            'tenant_id' => [ // Format: ten-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'comment' => 'Tenant ID with format: ten-{timestamp}-{random}'
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'quota' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 100000,
                'comment' => 'Monthly token quota'
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        // Add primary key on user_id and indexes
        $this->forge->addKey('user_id', true);
        $this->forge->addKey(['external_id', 'tenant_id']); // Índice compuesto para unicidad por tenant
        $this->forge->addKey('tenant_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_users');

        // Create API user buttons table for button access permissions
        $this->forge->addField([
            'user_id' => [ // References api_users.user_id
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'comment' => 'API User ID'
            ],
            'button_id' => [ // References buttons.button_id
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'comment' => 'Button ID'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        // Add composite primary key on user_id and button_id
        $this->forge->addKey(['user_id', 'button_id'], true);
        $this->forge->addForeignKey('user_id', 'api_users', 'user_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('button_id', 'buttons', 'button_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_user_buttons');
    }

    public function down()
    {
        // Drop tables in reverse order to avoid foreign key constraints
        $this->forge->dropTable('api_user_buttons');
        $this->forge->dropTable('api_users');
    }
}
