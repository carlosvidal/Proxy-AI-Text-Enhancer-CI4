<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run()
    {
        // Prompt for admin credentials
        echo "Creating superadmin user...\n";
        $username = readline("Enter admin username: ");
        $email = readline("Enter admin email: ");
        $password = readline("Enter admin password (min 8 characters): ");

        if (strlen($password) < 8) {
            die("Password must be at least 8 characters long\n");
        }

        // Create superadmin user
        $adminData = [
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name'     => 'Administrator',
            'role'     => 'superadmin',
            'active'   => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Using Query Builder with try-catch
        try {
            $this->db->transStart();
            
            // Insert admin user
            $this->db->table('users')->insert($adminData);
            echo "Superadmin user created successfully\n";

            // Create demo tenant for the admin
            $tenant_id = 'ten-' . dechex(time()) . '-' . bin2hex(random_bytes(4));
            $tenantData = [
                'tenant_id' => $tenant_id,
                'name' => 'Demo Tenant',
                'email' => $email,
                'quota' => 1000,
                'active' => 1,
                'subscription_status' => 'trial',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Insert tenant
            $this->db->table('tenants')->insert($tenantData);
            echo "Demo tenant created successfully\n";

            // Create a demo button
            $button_id = 'btn-' . dechex(time()) . '-' . bin2hex(random_bytes(4));
            $buttonData = [
                'button_id' => $button_id,
                'tenant_id' => $tenant_id,
                'name' => 'Demo Button',
                'description' => 'This is a demo button',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Insert button
            $this->db->table('buttons')->insert($buttonData);
            echo "Demo button created successfully\n";

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            echo "\nProduction seeding completed successfully!\n";
            echo "You can now login with:\n";
            echo "Username: {$username}\n";
            echo "Password: (the one you entered)\n";

        } catch (\Exception $e) {
            $this->db->transRollback();
            die("Error: " . $e->getMessage() . "\n");
        }
    }
}
