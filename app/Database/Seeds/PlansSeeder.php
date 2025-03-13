<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Small',
                'code' => 'small',
                'price' => 29.00,
                'requests_limit' => 1000,
                'users_limit' => 2,
                'features' => json_encode(['Basic support', 'API access']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Medium',
                'code' => 'medium',
                'price' => 79.00,
                'requests_limit' => 5000,
                'users_limit' => 5,
                'features' => json_encode(['Priority support', 'API access', 'Advanced analytics']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Large',
                'code' => 'large',
                'price' => 199.00,
                'requests_limit' => 15000,
                'users_limit' => 0,
                'features' => json_encode(['24/7 support', 'API access', 'Advanced analytics', 'Custom integrations']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('plans')->insertBatch($plans);
    }
}
