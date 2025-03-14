<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    // File: app/Database/Seeds/AdminSeeder.php
    public function run()
    {
        $data = [
            'username' => 'admin',
            'email'    => 'admin@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'name'     => 'Administrator',
            'role'     => 'superadmin',
            'active'   => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Using Query Builder
        $this->db->table('users')->insert($data);
    }
}
