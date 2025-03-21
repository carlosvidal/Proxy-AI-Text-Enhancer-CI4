<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigratePromptToSystemPrompt extends Migration
{
    public function up()
    {
        // En SQLite no podemos eliminar columnas directamente
        // En su lugar, crearemos una nueva tabla y copiaremos los datos
        
        // 1. Crear tabla temporal
        $this->forge->addField([
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addKey('button_id', true);
        $this->forge->createTable('buttons_temp');

        // 2. Copiar datos
        $db = db_connect();
        $db->query("INSERT INTO buttons_temp 
                   SELECT button_id, tenant_id, name, domain, api_key_id, prompt as system_prompt, active, created_at, updated_at 
                   FROM buttons");

        // 3. Eliminar tabla original
        $this->forge->dropTable('buttons');

        // 4. Renombrar tabla temporal
        $db->query("ALTER TABLE buttons_temp RENAME TO buttons");
    }

    public function down()
    {
        // En SQLite no podemos eliminar columnas directamente
        // En su lugar, crearemos una nueva tabla y copiaremos los datos
        
        // 1. Crear tabla temporal
        $this->forge->addField([
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addKey('button_id', true);
        $this->forge->createTable('buttons_temp');

        // 2. Copiar datos
        $db = db_connect();
        $db->query("INSERT INTO buttons_temp 
                   SELECT button_id, tenant_id, name, domain, api_key_id, system_prompt as prompt, active, created_at, updated_at 
                   FROM buttons");

        // 3. Eliminar tabla original
        $this->forge->dropTable('buttons');

        // 4. Renombrar tabla temporal
        $db->query("ALTER TABLE buttons_temp RENAME TO buttons");
    }
}
