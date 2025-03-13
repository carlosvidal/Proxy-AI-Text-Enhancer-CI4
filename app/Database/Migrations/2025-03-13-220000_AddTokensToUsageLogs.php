<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokensToUsageLogs extends Migration
{
    public function up()
    {
        // Add tokens column to usage_logs table
        $fields = [
            'tokens' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
                'after' => 'status'
            ],
            'prompt_tokens' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
                'after' => 'tokens'
            ],
            'completion_tokens' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
                'after' => 'prompt_tokens'
            ]
        ];

        $this->forge->addColumn('usage_logs', $fields);
    }

    public function down()
    {
        // Remove columns from usage_logs table
        $this->forge->dropColumn('usage_logs', ['tokens', 'prompt_tokens', 'completion_tokens']);
    }
}
