<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Create usage logs table

class CreateUsageLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'tenant_id' => [ // References tenants.tenant_id (format: ten-{timestamp}-{random})
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'user_id' => [ // References tenant_users.user_id (format: usr-{timestamp}-{random})
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'button_id' => [ // References buttons.button_id (format: btn-{timestamp}-{random})
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
                'type' => 'ENUM',
                'constraint' => ['success', 'error'],
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
        $this->forge->createTable('usage_logs');
    }

    public function down()
    {
        $this->forge->dropTable('usage_logs');
    }
}
