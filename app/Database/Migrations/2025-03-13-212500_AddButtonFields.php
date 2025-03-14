<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddButtonFields extends Migration
{
    public function up()
    {
        // Primero agregamos las columnas que pueden ser NULL
        $fields = [
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true, // Temporalmente permitimos NULL
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
                'null' => true, // Temporalmente permitimos NULL
                'after' => 'max_tokens'
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true, // Temporalmente permitimos NULL
                'after' => 'provider'
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => true, // Temporalmente permitimos NULL
                'after' => 'model'
            ]
        ];

        $this->forge->addColumn('buttons', $fields);

        // Establecer valores por defecto para los registros existentes
        $this->db->query("UPDATE buttons SET domain = 'https://example.com' WHERE domain IS NULL");
        $this->db->query("UPDATE buttons SET provider = 'openai' WHERE provider IS NULL");
        $this->db->query("UPDATE buttons SET model = 'gpt-3.5-turbo' WHERE model IS NULL");
        $this->db->query("UPDATE buttons SET api_key = 'default-key' WHERE api_key IS NULL");

        // Ahora hacemos las columnas NOT NULL
        $this->forge->modifyColumn('buttons', [
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => false
            ]
        ]);
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
