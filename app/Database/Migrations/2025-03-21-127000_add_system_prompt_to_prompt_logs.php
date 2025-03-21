<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSystemPromptToPromptLogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('prompt_logs', [
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'messages',
                'comment' => 'System prompt used in the request'
            ],
            'system_prompt_source' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'system_prompt',
                'comment' => 'Source of system prompt: button, request, or null'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('prompt_logs', ['system_prompt', 'system_prompt_source']);
    }
}
