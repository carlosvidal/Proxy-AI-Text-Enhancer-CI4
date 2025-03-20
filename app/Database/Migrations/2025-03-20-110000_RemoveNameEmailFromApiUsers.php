<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveNameEmailFromApiUsers extends Migration
{
    public function up()
    {
        // Eliminar las columnas name y email
        $fields = [
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ];

        $this->forge->dropColumn('api_users', array_keys($fields));
    }

    public function down()
    {
        // Restaurar las columnas name y email
        $fields = [
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'after' => 'external_id'
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'name'
            ],
        ];

        $this->forge->addColumn('api_users', $fields);
    }
}
