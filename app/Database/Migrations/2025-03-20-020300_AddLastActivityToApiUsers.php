<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastActivityToApiUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('api_users', [
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('api_users', 'last_activity');
    }
}
