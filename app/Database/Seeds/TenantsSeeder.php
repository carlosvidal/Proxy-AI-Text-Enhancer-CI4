<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TenantsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'tenant_id' => 'admin',
                'name' => 'Administrator Tenant',
                'email' => 'admin@example.com',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 'demo',
                'name' => 'Demo Tenant',
                'email' => 'demo@example.com',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('tenants')->insertBatch($data);
    }
}
