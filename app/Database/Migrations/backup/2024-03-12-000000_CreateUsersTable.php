/**
 * CreateUsersTable - Migration for System Authentication Users
 * 
 * IMPORTANT: This table is for system authentication users only!
 * Do not confuse with tenant_users table which is for API users.
 * 
 * Key Points:
 * 1. This table stores users who can log into the web interface
 * 2. Each user belongs to one tenant (tenant_id)
 * 3. Passwords are automatically hashed by UsersModel
 * 4. Roles are either 'superadmin' or 'tenant'
 * 
 * Related Files:
 * - UsersModel: Handles password hashing
 * - AdminUserSeeder: Creates default users
 * - Auth Controller: Handles authentication
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true
            ],
            'password' => [  // Automatically hashed by UsersModel
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['superadmin', 'tenant'],
                'default' => 'tenant'
            ],
            'tenant_id' => [  // Links to tenants table
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => 1
            ],
            'last_login' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME'
            ],
            'updated_at' => [
                'type' => 'DATETIME'
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
