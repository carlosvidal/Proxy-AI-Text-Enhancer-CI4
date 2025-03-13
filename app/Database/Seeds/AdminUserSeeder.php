<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UsersModel;
use App\Models\TenantsModel;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        helper('hash');
        
        $usersModel = new UsersModel();
        $tenantsModel = new TenantsModel();
        
        // Create admin tenant first
        $adminTenantId = 'ten-' . dechex(time()) . '-' . bin2hex(random_bytes(4));
        $adminTenant = [
            'tenant_id' => $adminTenantId,
            'name' => 'Administrator Tenant',
            'email' => 'admin@example.com',
            'quota' => 1000,
            'active' => 1,
            'subscription_status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $tenantsModel->insert($adminTenant);
        
        // Create demo tenant
        $demoTenantId = 'ten-' . dechex(time()) . '-' . bin2hex(random_bytes(4));
        $demoTenant = [
            'tenant_id' => $demoTenantId,
            'name' => 'Demo Tenant',
            'email' => 'demo@example.com',
            'quota' => 1000,
            'active' => 1,
            'subscription_status' => 'trial',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $tenantsModel->insert($demoTenant);

        // Create users
        $data = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'misacavi',  // Will be hashed by UsersModel
                'name' => 'Administrator',
                'role' => 'superadmin',
                'tenant_id' => $adminTenantId,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'demo_user',
                'email' => 'demo@example.com',
                'password' => 'misacavi',  // Will be hashed by UsersModel
                'name' => 'Demo User',
                'role' => 'tenant',
                'tenant_id' => $demoTenantId,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        try {
            $this->db->transStart();
            
            foreach ($data as $user) {
                $usersModel->insert($user);
                echo "Created user: {$user['username']}\n";
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }
            
            echo "\nSeeding completed successfully!\n";
            echo "You can login with:\n";
            echo "Admin - Username: admin, Password: misacavi\n";
            echo "Demo - Username: demo_user, Password: misacavi\n";
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            die("Error: " . $e->getMessage() . "\n");
        }
    }
}
