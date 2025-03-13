<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubscriptionFeatures extends Migration
{
    public function up()
    {
        // Add subscription features to tenants table
        $fields = [
            'max_buttons' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'after' => 'api_key'
            ],
            'max_requests' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1000,
                'after' => 'max_buttons'
            ],
            'subscription_type' => [
                'type' => 'ENUM',
                'constraint' => ['free', 'basic', 'premium'],
                'default' => 'free',
                'after' => 'max_requests'
            ]
        ];

        $this->forge->addColumn('tenants', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('tenants', ['max_buttons', 'max_requests', 'subscription_type']);
    }
}
