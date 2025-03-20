<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiUsersToTenants extends Migration
{
    public function up()
    {
        // En SQLite no podemos agregar una columna NOT NULL sin valor por defecto
        // así que primero la agregamos como NULL
        $this->forge->addColumn('tenants', [
            'max_api_users' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'name'
            ]
        ]);

        // Luego actualizamos todos los registros con el valor por defecto
        $this->db->query("UPDATE tenants SET max_api_users = 1 WHERE max_api_users IS NULL");

        // Y finalmente cambiamos la columna a NOT NULL
        if ($this->db->DBDriver !== 'SQLite3') {
            // SQLite no soporta ALTER COLUMN, así que solo lo hacemos para otros motores
            $this->forge->modifyColumn('tenants', [
                'max_api_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('max_api_users', 'tenants')) {
            $this->forge->dropColumn('tenants', 'max_api_users');
        }
    }
}
