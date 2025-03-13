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
            'button_id' => [  // Unique identifier using format: btn-{timestamp}-{random}
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'tenant_id' => [  // References tenants.tenant_id (format: ten-{timestamp}-{random})
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
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
        $this->forge->addUniqueKey('button_id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('buttons');
    }

    public function down()
    {
        $this->forge->dropTable('buttons');
    }
}
