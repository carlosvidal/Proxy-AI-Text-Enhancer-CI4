<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiUsersTable extends Migration
{
    public function up()
    {
        // Drop existing tables if they exist
        $this->forge->dropTable('api_user_buttons', true);
        $this->forge->dropTable('api_users', true);

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
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Add primary key on user_id and indexes
        $this->forge->addKey('user_id', true);
        $this->forge->addKey(['external_id', 'tenant_id']); // Composite index for uniqueness per tenant
        $this->forge->addKey('tenant_id');
        
        // Create the table
        $this->forge->createTable('api_users', true);

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

        // Create the table
        $this->forge->createTable('api_user_buttons', true);
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->forge->dropTable('api_user_buttons', true);
        $this->forge->dropTable('api_users', true);
    }
}
