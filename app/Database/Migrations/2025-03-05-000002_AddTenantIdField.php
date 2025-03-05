<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantIdField extends Migration
{
    public function up()
    {
        // Check if the tenant_id column exists in tenants table
        $fields = $this->db->getFieldData('tenants');
        $tenant_id_exists = false;

        foreach ($fields as $field) {
            if ($field->name === 'tenant_id') {
                $tenant_id_exists = true;
                break;
            }
        }

        // If not, add it
        if (!$tenant_id_exists) {
            $this->forge->addColumn('tenants', [
                'tenant_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'id'
                ]
            ]);

            // Create a unique index
            $this->db->query('CREATE UNIQUE INDEX tenant_id_unique ON tenants (tenant_id)');

            // Now generate tenant_id for all existing tenants
            $tenants = $this->db->table('tenants')->get()->getResultArray();

            foreach ($tenants as $tenant) {
                if (empty($tenant['tenant_id'])) {
                    // Generate a tenant_id based on name
                    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tenant['name']));
                    $base = substr($base . 'xxxx', 0, 4);
                    $random = bin2hex(random_bytes(2));
                    $tenant_id = $base . $random;

                    // Check for uniqueness
                    while ($this->db->table('tenants')->where('tenant_id', $tenant_id)->countAllResults() > 0) {
                        $random = bin2hex(random_bytes(2));
                        $tenant_id = $base . $random;
                    }

                    // Update the record
                    $this->db->table('tenants')
                        ->where('id', $tenant['id'])
                        ->update(['tenant_id' => $tenant_id]);
                }
            }

            // Now make it required
            $this->forge->modifyColumn('tenants', [
                'tenant_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ]
            ]);
        }
    }

    public function down()
    {
        // We won't remove the column for data safety
    }
}
