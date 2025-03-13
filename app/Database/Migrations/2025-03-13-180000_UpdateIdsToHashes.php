<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Update IDs to hash format

class UpdateIdsToHashes extends Migration
{
    public function up()
    {
        helper('hash');
        $db = \Config\Database::connect();

        // Update tenants with hash IDs (format: ten-{timestamp}-{random})
        $tenants = $db->table('tenants')->get()->getResultArray();
        foreach ($tenants as $tenant) {
            // Only update if the current tenant_id is not already a hash
            if (!preg_match('/^ten-[0-9a-f]+-[0-9a-f]+$/', $tenant['tenant_id'])) {
                $hashId = generate_hash_id('ten');
                $oldId = $tenant['tenant_id'];

                // Update tenant
                $db->table('tenants')
                    ->where('tenant_id', $oldId)
                    ->update(['tenant_id' => $hashId]);

                // Update related records in other tables using SQLite compatible syntax
                $tables = ['users', 'tenant_users', 'buttons', 'usage_logs'];
                foreach ($tables as $table) {
                    $sql = "UPDATE {$table} SET tenant_id = ? WHERE tenant_id = ?";
                    $db->query($sql, [$hashId, $oldId]);
                }
            }
        }

        // Update buttons with hash IDs (format: btn-{timestamp}-{random})
        $buttons = $db->table('buttons')->get()->getResultArray();
        foreach ($buttons as $button) {
            // Only update if the current button_id is not already a hash
            if (!preg_match('/^btn-[0-9a-f]+-[0-9a-f]+$/', $button['button_id'])) {
                $hashId = generate_hash_id('btn');
                $oldId = $button['button_id'];

                // Update button
                $db->table('buttons')
                    ->where('button_id', $oldId)
                    ->update(['button_id' => $hashId]);

                // Update related records using SQLite compatible syntax
                $sql = "UPDATE usage_logs SET button_id = ? WHERE button_id = ?";
                $db->query($sql, [$hashId, $oldId]);
            }
        }
    }

    public function down()
    {
        // Since this is a data migration that changes IDs to hash format,
        // we don't provide a down() method to prevent data loss
        return;
    }
}
