<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateButtonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'api_key' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => 1
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tenant_id'); // Index for faster lookups
        $this->forge->addUniqueKey('button_id'); // Ensure button_id is unique
        $this->forge->createTable('buttons');
    }

    public function down()
    {
        $this->forge->dropTable('buttons');
    }
}
