<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateApiKeysToUseHashIds extends Migration
{
    public function up()
    {
        // First, check if the table exists by trying to get its fields
        try {
            $existingData = $this->db->table('api_keys')->get()->getResultArray();
            
            // If we get here, the table exists, so drop it
            $this->forge->dropTable('api_keys', true);
        } catch (\Exception $e) {
            // Table doesn't exist, that's fine
            $existingData = [];
        }

        // Create the new table structure
        $this->forge->addField([
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'api_key' => [
                'type' => 'TEXT',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ]);
        
        // Add primary and foreign keys
        $this->forge->addKey('api_key_id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'tenant_id', 'CASCADE', 'CASCADE');
        
        // Enable foreign key constraints in SQLite
        $this->db->query('PRAGMA foreign_keys = ON');
        
        // Create the table
        $this->forge->createTable('api_keys');

        // If we had existing data, migrate it to the new structure
        if (!empty($existingData)) {
            helper('hash');
            
            // Get valid tenant IDs
            $validTenantIds = $this->db->table('tenants')
                                     ->select('tenant_id')
                                     ->get()
                                     ->getResultArray();
            $validTenantIds = array_column($validTenantIds, 'tenant_id');

            foreach ($existingData as $row) {
                // Skip if tenant_id doesn't exist
                if (!in_array($row['tenant_id'], $validTenantIds)) {
                    continue;
                }

                // Generate a new hash ID for each key
                $data = [
                    'api_key_id' => generate_hash_id('key'),
                    'tenant_id' => $row['tenant_id'],
                    'name' => $row['name'],
                    'provider' => $row['provider'],
                    'api_key' => $row['api_key'],
                    'is_default' => $row['is_default'] ?? 0,
                    'active' => $row['active'] ?? 1,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
                
                $this->db->table('api_keys')->insert($data);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('api_keys', true);
    }
}
