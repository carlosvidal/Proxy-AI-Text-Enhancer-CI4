<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToApiUsers extends Migration
{
    public function up()
    {
        $fields = [
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
                'after' => 'id'
            ]
        ];
        
        $this->forge->addColumn('api_users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('api_users', 'user_id');
    }
}
