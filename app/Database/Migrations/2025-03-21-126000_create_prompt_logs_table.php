<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromptLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'usage_log_id' => [
                'type' => 'INTEGER',
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'null' => true
            ],
            'messages' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'JSON array of message objects'
            ],
            'response' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Response from the LLM'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('usage_log_id');
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('button_id');
        $this->forge->addKey('created_at');

        $this->forge->createTable('prompt_logs');
    }

    public function down()
    {
        $this->forge->dropTable('prompt_logs');
    }
}
