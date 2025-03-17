<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiKeysToTenants extends Migration
{
    public function up()
    {
        // Verificar si la columna ya existe
        $hasColumn = $this->db->fieldExists('max_api_keys', 'tenants');

        if (!$hasColumn) {
            $fields = [
                'max_api_keys' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 1,
                    'after' => 'max_domains'
                ]
            ];

            $this->forge->addColumn('tenants', $fields);

            // Actualizar tenants existentes basados en su plan
            $this->db->query("UPDATE tenants SET max_api_keys = 1 WHERE plan_code = 'free'");
            $this->db->query("UPDATE tenants SET max_api_keys = 5 WHERE plan_code = 'pro'");
            $this->db->query("UPDATE tenants SET max_api_keys = 999 WHERE plan_code = 'enterprise'");
        }
    }

    public function down()
    {
        // Verificar si la columna existe antes de intentar eliminarla
        if ($this->db->fieldExists('max_api_keys', 'tenants')) {
            $this->forge->dropColumn('tenants', 'max_api_keys');
        }
    }
}
