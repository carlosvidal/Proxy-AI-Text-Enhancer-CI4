<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAutoCreateApiUsersToButtons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('buttons', [
            'auto_create_api_users' => [
                'type' => 'BOOLEAN',
                'default' => 0,
                'after' => 'status'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('buttons', 'auto_create_api_users');
    }
}
