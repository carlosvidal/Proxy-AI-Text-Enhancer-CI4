<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'null' => false
            ],
            'api_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'active' => [
                'type' => 'INTEGER',
                'null' => false,
                'default' => 1
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('api_key');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('tenant_api_keys');

        // Add a default API key for each existing tenant
        $tenants = $this->db->table('tenants')->get()->getResultArray();
        foreach ($tenants as $tenant) {
            $this->db->table('tenant_api_keys')->insert([
                'tenant_id' => $tenant['tenant_id'],
                'api_key' => 'key-' . bin2hex(random_bytes(16)),
                'name' => 'Default API Key',
                'description' => 'Default API key created during migration',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('tenant_api_keys');
    }
}
