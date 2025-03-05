<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateButtonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'api_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'domain']);
        $this->forge->createTable('buttons');
    }

    public function down()
    {
        $this->forge->dropTable('buttons');
    }
}
