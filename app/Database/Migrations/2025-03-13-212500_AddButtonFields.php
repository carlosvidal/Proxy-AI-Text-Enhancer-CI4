<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddButtonFields extends Migration
{
    public function up()
    {
        $fields = [
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'after' => 'description'
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'domain'
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'type'
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'prompt'
            ],
            'temperature' => [
                'type' => 'DECIMAL',
                'constraint' => '3,2',
                'null' => true,
                'default' => 0.7,
                'after' => 'system_prompt'
            ],
            'max_tokens' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 2048,
                'after' => 'temperature'
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'after' => 'max_tokens'
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'after' => 'provider'
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false,
                'after' => 'model'
            ]
        ];

        $this->forge->addColumn('buttons', $fields);
    }

    public function down()
    {
        $fields = [
            'domain',
            'type',
            'prompt',
            'system_prompt',
            'temperature',
            'max_tokens',
            'provider',
            'model',
            'api_key'
        ];

        $this->forge->dropColumn('buttons', $fields);
    }
}
