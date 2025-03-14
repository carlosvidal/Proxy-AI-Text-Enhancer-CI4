<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTokensTable extends Migration
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
            'tenant_id' => [  // Links to tenants table following hash-based ID format
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'button_id' => [  // Links to buttons table following hash-based ID format
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'tokens' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'button_id']);
        $this->forge->createTable('tokens');
    }

    public function down()
    {
        $this->forge->dropTable('tokens');
    }
}
