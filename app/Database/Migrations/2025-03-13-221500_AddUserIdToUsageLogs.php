<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToUsageLogs extends Migration
{
    public function up()
    {
        // Primero, establecemos un valor por defecto para los registros existentes
        $this->db->query("UPDATE usage_logs SET user_id = 0 WHERE user_id IS NULL");

        // Modificar la columna user_id para que coincida con el formato de IDs hash
        $fields = [
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true // Temporalmente permitimos NULL
            ]
        ];

        $this->forge->modifyColumn('usage_logs', $fields);

        // Actualizar los IDs existentes al nuevo formato
        $this->db->query("UPDATE usage_logs SET user_id = 'usr-' || substr(hex(randomblob(4)), 1, 8) || '-' || substr(hex(randomblob(4)), 1, 8) WHERE user_id = '0'");

        // Hacer la columna NOT NULL
        $fields = [
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ]
        ];

        $this->forge->modifyColumn('usage_logs', $fields);
        
        // Asegurarnos de que el índice existe
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_usage_logs_tenant_user ON usage_logs(tenant_id, user_id)');
    }

    public function down()
    {
        // Revertir la columna user_id a INTEGER NULL
        $fields = [
            'user_id' => [
                'type' => 'INTEGER',
                'null' => true
            ]
        ];

        $this->forge->modifyColumn('usage_logs', $fields);
    }
}
