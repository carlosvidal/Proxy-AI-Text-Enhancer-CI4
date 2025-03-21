<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigratePromptToSystemPrompt extends Migration
{
    public function up()
    {
        // Crear tabla usage_logs
        $this->forge->addField([
            'usage_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
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
            'tokens_in' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0
            ],
            'tokens_out' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0
            ],
            'has_image' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        $this->forge->addKey('usage_id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('external_id');
        $this->forge->createTable('usage_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('usage_logs', true);
    }
}
