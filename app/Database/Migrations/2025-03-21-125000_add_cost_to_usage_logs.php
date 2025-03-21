<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCostToUsageLogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usage_logs', [
            'cost' => [
                'type' => 'DECIMAL(10,4)',
                'null' => true,
                'default' => null,
                'after' => 'tokens'
            ]
        ]);

        // Calculate cost for existing records based on model and tokens
        $db = db_connect();
        
        // OpenAI models
        $db->query("
            UPDATE usage_logs 
            SET cost = CASE 
                WHEN model = 'gpt-4-turbo' THEN tokens * 0.00003
                WHEN model = 'gpt-4-vision' THEN tokens * 0.00003
                WHEN model = 'gpt-3.5-turbo' THEN tokens * 0.000002
                ELSE tokens * 0.00001
            END 
            WHERE provider = 'openai'
        ");

        // Anthropic models
        $db->query("
            UPDATE usage_logs 
            SET cost = CASE 
                WHEN model = 'claude-3-opus-20240229' THEN tokens * 0.00015
                WHEN model = 'claude-3-sonnet-20240229' THEN tokens * 0.00003
                WHEN model = 'claude-3-haiku-20240307' THEN tokens * 0.00001
                ELSE tokens * 0.00003
            END 
            WHERE provider = 'anthropic'
        ");

        // Mistral models
        $db->query("
            UPDATE usage_logs 
            SET cost = CASE 
                WHEN model = 'mistral-large' THEN tokens * 0.00002
                WHEN model = 'mistral-medium' THEN tokens * 0.000014
                WHEN model = 'mistral-small' THEN tokens * 0.000006
                WHEN model = 'mistral-tiny' THEN tokens * 0.000002
                ELSE tokens * 0.00001
            END 
            WHERE provider = 'mistral'
        ");

        // Google models
        $db->query("
            UPDATE usage_logs 
            SET cost = CASE 
                WHEN model = 'gemini-pro' THEN tokens * 0.000001
                WHEN model = 'gemini-pro-vision' THEN tokens * 0.000001
                ELSE tokens * 0.000001
            END 
            WHERE provider = 'google'
        ");

        // Default for unknown providers/models
        $db->query("
            UPDATE usage_logs 
            SET cost = tokens * 0.00001
            WHERE cost IS NULL AND tokens IS NOT NULL
        ");
    }

    public function down()
    {
        $this->forge->dropColumn('usage_logs', 'cost');
    }
}
