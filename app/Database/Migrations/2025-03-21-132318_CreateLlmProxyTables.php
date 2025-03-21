<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLlmProxyTables extends Migration
{
    public function up()
    {
        // Tenants table
        $this->forge->addField([
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('tenant_id', true);
        $this->forge->createTable('tenants');

        // Buttons table
        $this->forge->addField([
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('button_id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('buttons');

        // Usage logs table
        $this->forge->addField([
            'log_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'tokens_in' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'tokens_out' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('log_id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('button_id', 'buttons', 'button_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('usage_logs');

        // Prompt logs table
        $this->forge->addField([
            'prompt_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'log_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'prompt' => [
                'type' => 'TEXT',
            ],
            'response' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('prompt_id', true);
        $this->forge->addForeignKey('log_id', 'usage_logs', 'log_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('prompt_logs');
    }

    public function down()
    {
        $this->forge->dropTable('prompt_logs');
        $this->forge->dropTable('usage_logs');
        $this->forge->dropTable('buttons');
        $this->forge->dropTable('tenants');
    }
}
