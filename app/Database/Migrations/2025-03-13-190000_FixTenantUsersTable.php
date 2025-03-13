<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixTenantUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'user'],
                'default' => 'user',
                'after' => 'name'
            ]
        ]);

        $this->forge->addColumn('tenant_users', 'role');
    }

    public function down()
    {
        $this->forge->dropColumn('tenant_users', 'role');
    }
}
