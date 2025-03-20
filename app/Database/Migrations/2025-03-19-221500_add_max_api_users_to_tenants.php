<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMaxApiUsersToTenants extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tenants', [
            'max_api_users' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
                'after' => 'name'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', 'max_api_users');
    }
}
