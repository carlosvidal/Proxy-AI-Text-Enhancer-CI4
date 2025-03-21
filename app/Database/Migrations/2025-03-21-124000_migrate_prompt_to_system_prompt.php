<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigratePromptToSystemPrompt extends Migration
{
    public function up()
    {
        // First, migrate any existing prompts to system_prompt if they're not null
        $this->db->query("
            UPDATE buttons 
            SET system_prompt = prompt 
            WHERE prompt IS NOT NULL 
            AND (system_prompt IS NULL OR system_prompt = '')
        ");

        // Then drop the prompt column as it's no longer needed
        $this->forge->dropColumn('buttons', 'prompt');
    }

    public function down()
    {
        // Add back the prompt column
        $this->forge->addColumn('buttons', [
            'prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
                'after' => 'domain'
            ]
        ]);

        // Move system_prompt back to prompt
        $this->db->query("
            UPDATE buttons 
            SET prompt = system_prompt 
            WHERE system_prompt IS NOT NULL
        ");
    }
}
