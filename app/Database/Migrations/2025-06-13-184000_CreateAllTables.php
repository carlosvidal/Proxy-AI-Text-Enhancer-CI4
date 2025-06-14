<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAllTables extends Migration
{
    public function up()
    {
        // Tabla: users
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'username' => [ 'type' => 'VARCHAR', 'constraint' => 50, 'unique' => true ],
            'email' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'unique' => true ],
            'password' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'role' => [ 'type' => 'VARCHAR', 'constraint' => 20 ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'active' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'quota' => [ 'type' => 'INTEGER', 'null' => true ],
            'last_login' => [ 'type' => 'DATETIME', 'null' => true ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        // Tabla: tenants
        $this->forge->addField([
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'email' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'quota' => [ 'type' => 'INTEGER' ],
            'active' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'api_key' => [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ],
            'plan_code' => [ 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true ],
            'subscription_status' => [ 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true ],
            'trial_ends_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'subscription_ends_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'max_domains' => [ 'type' => 'INTEGER' ],
            'max_api_keys' => [ 'type' => 'INTEGER' ],
            'auto_create_users' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('tenant_id', true);
        $this->forge->createTable('tenants', true);

        // Tabla: buttons
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'button_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'unique' => true ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'description' => [ 'type' => 'TEXT', 'null' => true ],
            'domain' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'system_prompt' => [ 'type' => 'TEXT', 'null' => true ],
            'provider' => [ 'type' => 'VARCHAR', 'constraint' => 20 ],
            'model' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'api_key_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'status' => [ 'type' => 'VARCHAR', 'constraint' => 20 ],
            'auto_create_api_users' => [ 'type' => 'TINYINT', 'constraint' => 1, 'null' => true ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('button_id');
        $this->forge->createTable('buttons', true);

        // Tabla: api_keys
        $this->forge->addField([
            'api_key_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'provider' => [ 'type' => 'VARCHAR', 'constraint' => 20 ],
            'api_key' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'is_default' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'active' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('api_key_id', true);
        $this->forge->createTable('api_keys', true);

        // Tabla: api_tokens
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'user_id' => [ 'type' => 'INTEGER' ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'token' => [ 'type' => 'VARCHAR', 'constraint' => 64 ],
            'refresh_token' => [ 'type' => 'VARCHAR', 'constraint' => 64 ],
            'scopes' => [ 'type' => 'TEXT', 'null' => true ],
            'last_used_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'expires_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'revoked' => [ 'type' => 'TINYINT', 'constraint' => 1, 'default' => 0 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('api_tokens', true);

        // Tabla: api_users
        $this->forge->addField([
            'user_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'external_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'email' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'quota' => [ 'type' => 'INTEGER', 'null' => true ],
            'daily_quota' => [ 'type' => 'INTEGER', 'default' => 10000 ],
            'active' => [ 'type' => 'TINYINT', 'constraint' => 1, 'default' => 1 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ],
            'last_activity' => [ 'type' => 'DATETIME', 'null' => true ]
        ]);
        $this->forge->addKey('user_id', true);
        $this->forge->createTable('api_users', true);

        // Tabla: tenant_users
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'user_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'email' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'quota' => [ 'type' => 'INTEGER' ],
            'active' => [ 'type' => 'TINYINT', 'constraint' => 1 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('tenant_users', true);

        // Tabla: domains
        $this->forge->addField([
            'domain_id' => [ 'type' => 'VARCHAR', 'constraint' => 32 ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'domain' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'verified' => [ 'type' => 'TINYINT', 'constraint' => 1, 'default' => 0 ],
            'created_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('domain_id', true);
        $this->forge->addUniqueKey(['tenant_id', 'domain']);
        $this->forge->createTable('domains', true);

        // Tabla: plans
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'name' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'code' => [ 'type' => 'VARCHAR', 'constraint' => 50, 'unique' => true ],
            'price' => [ 'type' => 'DECIMAL', 'constraint' => '10,2' ],
            'requests_limit' => [ 'type' => 'INTEGER' ],
            'users_limit' => [ 'type' => 'INTEGER' ],
            'features' => [ 'type' => 'TEXT', 'null' => true ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('plans', true);

        // Tabla: usage_logs
        $this->forge->addField([
            'id' => [ 'type' => 'INTEGER', 'auto_increment' => true ],
            'usage_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'unique' => true ],
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'user_id' => [ 'type' => 'INTEGER', 'null' => true ],
            'external_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'button_id' => [ 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true ],
            'provider' => [ 'type' => 'VARCHAR', 'constraint' => 50 ],
            'model' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'tokens' => [ 'type' => 'INTEGER' ],
            'cost' => [ 'type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true ],
            'has_image' => [ 'type' => 'TINYINT', 'constraint' => 1, 'null' => true ],
            'status' => [ 'type' => 'VARCHAR', 'constraint' => 50 ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('usage_id');
        $this->forge->createTable('usage_logs', true);

        // Tabla: user_quotas
        $this->forge->addField([
            'tenant_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'external_id' => [ 'type' => 'VARCHAR', 'constraint' => 100 ],
            'total_quota' => [ 'type' => 'INTEGER' ],
            'created_at' => [ 'type' => 'DATETIME' ]
        ]);
        $this->forge->addKey(['tenant_id', 'external_id'], true);
        $this->forge->createTable('user_quotas', true);
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('tenants', true);
        $this->forge->dropTable('buttons', true);
        $this->forge->dropTable('api_keys', true);
        $this->forge->dropTable('api_tokens', true);
        $this->forge->dropTable('api_users', true);
        $this->forge->dropTable('tenant_users', true);
        $this->forge->dropTable('domains', true);
        $this->forge->dropTable('plans', true);
        $this->forge->dropTable('usage_logs', true);
        $this->forge->dropTable('user_quotas', true);
    }
}
