<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTenantUsersStructure extends Migration
{
    public function up()
    {
        // Primero hacemos email nullable
        $this->forge->modifyColumn('tenant_users', [
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ]
        ]);

        // Agregamos las columnas faltantes
        $this->forge->addColumn('tenant_users', [
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'after' => 'id'
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'after' => 'user_id'
            ]
        ]);

        // Agregamos índice único para user_id
        $this->forge->addKey('user_id', false, true);
    }

    public function down()
    {
        // Revertir los cambios
        $this->forge->dropColumn('tenant_users', ['user_id', 'external_id']);
        
        $this->forge->modifyColumn('tenant_users', [
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ]
        ]);
    }
}
