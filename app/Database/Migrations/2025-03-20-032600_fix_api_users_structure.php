<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixApiUsersStructure extends Migration
{
    public function up()
    {
        // Primero respaldamos los datos existentes
        $this->db->query("CREATE TABLE IF NOT EXISTS api_users_backup AS SELECT * FROM api_users");

        // Eliminamos la tabla actual
        $this->forge->dropTable('api_users', true);

        // Creamos la tabla con la estructura correcta
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'null' => false,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1000,
            ],
            'daily_quota' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 10000,
            ],
            'active' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1,
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('api_users');

        // Restauramos los datos, generando user_id para los registros existentes
        $this->db->query("INSERT INTO api_users (user_id, tenant_id, name, email, quota, daily_quota, active, last_activity, created_at, updated_at)
            SELECT 
                'usr-' || substr(hex(randomblob(4)), 1, 8) || '-' || substr(hex(randomblob(4)), 1, 8),
                tenant_id,
                name,
                NULL,
                1000,
                10000,
                active,
                last_activity,
                created_at,
                updated_at
            FROM api_users_backup");
    }

    public function down()
    {
        if ($this->db->tableExists('api_users_backup')) {
            // Restaurar la tabla original
            $this->forge->dropTable('api_users', true);
            $this->db->query("CREATE TABLE api_users AS SELECT * FROM api_users_backup");
            $this->db->query("DROP TABLE api_users_backup");
        }
    }
}
