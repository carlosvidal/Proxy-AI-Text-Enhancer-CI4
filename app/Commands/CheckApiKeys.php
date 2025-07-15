<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckApiKeys extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:check-apikeys';
    protected $description = 'Check API keys for a specific tenant and provider';

    public function run(array $params)
    {
        $tenantId = $params[0] ?? null;
        $provider = $params[1] ?? null;
        
        if (!$tenantId) {
            CLI::error('Please provide tenant ID as first parameter');
            CLI::write('Usage: php spark db:check-apikeys ten-67d88d1d-111ae225 [provider]');
            return;
        }
        
        $apiKeysModel = new \App\Models\ApiKeysModel();
        
        CLI::write("Checking API keys for tenant: $tenantId", 'yellow');
        
        // Get all API keys for this tenant
        $allKeys = $apiKeysModel->where('tenant_id', $tenantId)->findAll();
        
        if (empty($allKeys)) {
            CLI::error("No API keys found for tenant: $tenantId");
            return;
        }
        
        CLI::write("Found " . count($allKeys) . " API keys for this tenant:", 'green');
        
        foreach ($allKeys as $key) {
            $status = [];
            if ($key['active']) $status[] = 'active';
            if ($key['is_default']) $status[] = 'default';
            
            CLI::write("  - {$key['name']} ({$key['provider']}) - " . implode(', ', $status), 'cyan');
        }
        
        // If provider specified, check for specific provider
        if ($provider) {
            CLI::write("\nChecking for provider: $provider", 'yellow');
            
            $defaultKey = $apiKeysModel->getDefaultKey($tenantId, $provider);
            
            if ($defaultKey) {
                CLI::write("✓ Default key found: {$defaultKey['name']}", 'green');
            } else {
                CLI::write("✗ No default key found", 'red');
                
                // Check for any active key
                $anyActiveKey = $apiKeysModel->where('tenant_id', $tenantId)
                                           ->where('provider', $provider)
                                           ->where('active', 1)
                                           ->first();
                
                if ($anyActiveKey) {
                    CLI::write("  But found active key: {$anyActiveKey['name']} (not default)", 'yellow');
                } else {
                    CLI::write("  No active keys found for this provider", 'red');
                }
            }
        }
        
        CLI::write("\nDone!", 'green');
    }
}