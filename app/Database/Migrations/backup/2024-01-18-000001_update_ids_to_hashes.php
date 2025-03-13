<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateIdsToHashes extends Migration
{
    public function up()
    {
        helper('hash');
        $db = \Config\Database::connect();

        // Update tenants with hash IDs
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
                
                // Update related records in other tables
                $db->table('users')->where('tenant_id', $oldId)->update(['tenant_id' => $hashId]);
                $db->table('tenant_users')->where('tenant_id', $oldId)->update(['tenant_id' => $hashId]);
                $db->table('buttons')->where('tenant_id', $oldId)->update(['tenant_id' => $hashId]);
                $db->table('usage_logs')->where('tenant_id', $oldId)->update(['tenant_id' => $hashId]);
            }
        }

        // Update buttons with hash IDs
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
                
                // Update related records
                $db->table('usage_logs')->where('button_id', $oldId)->update(['button_id' => $hashId]);
            }
        }
    }

    public function down()
    {
        // Since this is a data migration that changes IDs,
        // we don't provide a down() method to prevent data loss
        return;
    }
}
