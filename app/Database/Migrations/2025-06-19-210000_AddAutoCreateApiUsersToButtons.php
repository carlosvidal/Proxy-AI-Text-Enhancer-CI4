<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAutoCreateApiUsersToButtons extends Migration
{
    public function up()
    {
        // Add auto_create_api_users column to buttons table
        $this->forge->addColumn('buttons', [
            'auto_create_api_users' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'after' => 'system_prompt'
            ]
        ]);
    }

    public function down()
    {
        // Remove auto_create_api_users column from buttons table
        $this->forge->dropColumn('buttons', 'auto_create_api_users');
    }
}