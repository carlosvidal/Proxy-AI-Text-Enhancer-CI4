/**
 * AdminUserSeeder - Creates default system authentication users
 * 
 * IMPORTANT: DO NOT HASH PASSWORDS HERE!
 * The UsersModel automatically hashes passwords through its beforeInsert hook.
 * Always provide plain text passwords in this seeder.
 * 
 * Default Users:
 * 1. Admin User
 *    - Username: admin
 *    - Password: misacavi (plain text)
 *    - Role: superadmin
 * 
 * 2. Demo User
 *    - Username: demo_user
 *    - Password: misacavi (plain text)
 *    - Role: tenant
 */

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UsersModel;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $usersModel = new UsersModel();
        
        // CRITICAL: Do not hash passwords here - UsersModel will do it automatically
        $data = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'misacavi',  // Will be hashed by UsersModel
                'name' => 'Administrator',
                'role' => 'superadmin',
                'tenant_id' => 'admin',
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
                'tenant_id' => 'demo',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($data as $user) {
            $usersModel->insert($user);
        }
    }
}
