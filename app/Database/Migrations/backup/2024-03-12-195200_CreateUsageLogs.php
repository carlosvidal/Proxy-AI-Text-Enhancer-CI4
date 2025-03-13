<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsageLogs extends Migration
{
    public function up()
    {
        // Check if the table already exists using SQLite syntax
        if ($this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usage_logs'")->getRow()) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'button_id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'api_user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'tokens' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'cost' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'default' => 0,
            ],
            'input_length' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'default' => 0,
            ],
            'output_length' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'success',
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        
        $this->forge->createTable('usage_logs');
    }

    public function down()
    {
        $this->forge->dropTable('usage_logs', true);
    }
}
