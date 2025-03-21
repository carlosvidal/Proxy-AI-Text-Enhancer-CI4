<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSystemPromptToButtons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('buttons', [
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
                'after' => 'prompt'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('buttons', 'system_prompt');
    }
}
