<?php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class MasterSeeder extends Seeder
{
    public function run()
    {
        // Limpiar todas las tablas antes de insertar datos
        $this->db->disableForeignKeyChecks();
        $this->db->table('usage_logs')->truncate();
        $this->db->table('user_quotas')->truncate();
        $this->db->table('api_tokens')->truncate();
        $this->db->table('api_users')->truncate();
        $this->db->table('tenant_users')->truncate();
        $this->db->table('domains')->truncate();
        $this->db->table('buttons')->truncate();
        $this->db->table('api_keys')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('tenants')->truncate();
        $this->db->table('plans')->truncate();
        $this->db->enableForeignKeyChecks();

        $now = Time::now('America/Mexico_City', 'en_US');

        // Tenants
        $tenants = [
            [
                'tenant_id' => 'ten-001',
                'name' => 'Tenant Principal',
                'email' => 'tenant1@example.com',
                'quota' => 100000,
                'active' => 1,
                'api_key' => 'apikey_tenant1',
                'plan_code' => 'basic',
                'subscription_status' => 'active',
                'trial_ends_at' => $now,
                'subscription_ends_at' => $now->addMonths(1),
                'max_domains' => 10,
                'max_api_keys' => 5,
                'auto_create_users' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('tenants')->insertBatch($tenants);

        // Plans
        $plans = [
            [
                'name' => 'Básico',
                'code' => 'basic',
                'price' => 0,
                'requests_limit' => 100000,
                'users_limit' => 10,
                'features' => json_encode(['feature1','feature2']),
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('plans')->insertBatch($plans);

        // Users (admin)
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'name' => 'Administrador',
                'role' => 'superadmin',
                'tenant_id' => null,
                'active' => 1,
                'quota' => 100000,
                'last_login' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('users')->insertBatch($users);

        // Api Keys
        $apiKeys = [
            [
                'api_key_id' => 'key-001',
                'tenant_id' => 'ten-001',
                'name' => 'Clave Principal',
                'provider' => 'openai',
                'api_key' => 'sk-xxx',
                'is_default' => 1,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('api_keys')->insertBatch($apiKeys);

        // Buttons
        $buttons = [
            [
                'button_id' => 'btn-001',
                'tenant_id' => 'ten-001',
                'name' => 'Botón Demo',
                'description' => 'Botón de prueba',
                'domain' => 'demo.com',
                'system_prompt' => 'Hola, soy un prompt de sistema',
                'provider' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'api_key_id' => 'key-001',
                'status' => 'active',
                'auto_create_api_users' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('buttons')->insertBatch($buttons);

        // Domains
        $domains = [
            [
                'domain_id' => 'dom-001',
                'tenant_id' => 'ten-001',
                'domain' => 'demo.com',
                'verified' => 1,
                'created_at' => $now
            ]
        ];
        $this->db->table('domains')->insertBatch($domains);

        // Api Users
        $apiUsers = [
            [
                'user_id' => 'usr-001',
                'tenant_id' => 'ten-001',
                'external_id' => 'ext-001',
                'name' => 'API User Demo',
                'email' => 'apiuser@example.com',
                'quota' => 10000,
                'daily_quota' => 1000,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity' => $now
            ]
        ];
        $this->db->table('api_users')->insertBatch($apiUsers);

        // Tenant Users
        $tenantUsers = [
            [
                'tenant_id' => 'ten-001',
                'user_id' => 'usr-001',
                'name' => 'Tenant API User',
                'email' => 'tenantuser@example.com',
                'quota' => 5000,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('tenant_users')->insertBatch($tenantUsers);

        // Api Tokens
        $apiTokens = [
            [
                'user_id' => 1,
                'tenant_id' => 'ten-001',
                'name' => 'Token Demo',
                'token' => bin2hex(random_bytes(32)),
                'refresh_token' => bin2hex(random_bytes(32)),
                'scopes' => json_encode(['read', 'write']),
                'last_used_at' => $now,
                'expires_at' => $now->addDays(30),
                'revoked' => 0,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('api_tokens')->insertBatch($apiTokens);

        // Usage Logs
        $usageLogs = [
            [
                'usage_id' => 'usg-001',
                'tenant_id' => 'ten-001',
                'user_id' => 1,
                'external_id' => 'ext-001',
                'button_id' => 'btn-001',
                'provider' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'tokens' => 100,
                'cost' => 0.05,
                'has_image' => 0,
                'status' => 'success',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        $this->db->table('usage_logs')->insertBatch($usageLogs);

        // User Quotas
        $userQuotas = [
            [
                'tenant_id' => 'ten-001',
                'external_id' => 'ext-001',
                'total_quota' => 10000,
                'created_at' => $now
            ]
        ];
        $this->db->table('user_quotas')->insertBatch($userQuotas);
    }
}
