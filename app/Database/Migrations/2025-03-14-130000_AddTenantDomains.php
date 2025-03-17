<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantDomains extends Migration
{
    public function up()
    {
        // Crear tabla domains
        $this->forge->addField([
            'domain_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'verified' => [
                'type' => 'TINYINT',
                'default' => 0
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ]
        ]);
        $this->forge->addPrimaryKey('domain_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('domains');

        // Agregar max_domains a tenants
        $this->forge->addColumn('tenants', [
            'max_domains' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
                'after' => 'active'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('domains');
        $this->forge->dropColumn('tenants', 'max_domains');
    }
}
