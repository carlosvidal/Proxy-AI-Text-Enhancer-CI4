<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastLoginColumn extends Migration
{
    public function up()
    {
        // Add the last_login column to admin_users table
        $this->forge->addColumn('admin_users', [
            'last_login' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        // Remove the last_login column if needed
        $this->forge->dropColumn('admin_users', 'last_login');
    }
}
