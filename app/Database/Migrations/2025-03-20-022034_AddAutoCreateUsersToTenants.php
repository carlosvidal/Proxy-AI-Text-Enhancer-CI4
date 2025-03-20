<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAutoCreateUsersToTenants extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tenants', [
            'auto_create_users' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'after' => 'max_api_keys',
                'comment' => 'Whether to auto-create API users if they dont exist'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', 'auto_create_users');
    }
}
