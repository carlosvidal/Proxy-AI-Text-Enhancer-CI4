<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncButtonsStatusFromActive extends Migration
{
    public function up()
    {
        // Sync status column from active column for all existing buttons
        // active=1 → status='active', active=0 → status='inactive'
        $this->db->query("UPDATE buttons SET status = 'active' WHERE active = 1");
        $this->db->query("UPDATE buttons SET status = 'inactive' WHERE active = 0 OR active IS NULL");

        log_message('info', 'Synced buttons.status from buttons.active for all rows');
    }

    public function down()
    {
        // No rollback needed
    }
}
