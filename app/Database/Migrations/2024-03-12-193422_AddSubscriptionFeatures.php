<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubscriptionFeatures extends Migration
{
    public function up()
    {
        // Create plans table if it doesn't exist
        if (!$this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plans'")->getRow()) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INTEGER',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                ],
                'price' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                ],
                'requests_limit' => [
                    'type' => 'INTEGER',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'users_limit' => [
                    'type' => 'INTEGER',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'features' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('plans');
        }

        // Add subscription fields to tenants table if they don't exist
        // SQLite requires separate ALTER TABLE statements for each column
        $existingColumns = $this->db->query("PRAGMA table_info(tenants)")->getResultArray();
        $columnNames = array_column($existingColumns, 'name');
        
        if (!in_array('plan_code', $columnNames)) {
            $this->db->query("ALTER TABLE tenants ADD COLUMN plan_code VARCHAR(50) NULL");
        }

        if (!in_array('subscription_status', $columnNames)) {
            $this->db->query("ALTER TABLE tenants ADD COLUMN subscription_status VARCHAR(20) DEFAULT 'trial'");
        }

        if (!in_array('trial_ends_at', $columnNames)) {
            $this->db->query("ALTER TABLE tenants ADD COLUMN trial_ends_at DATETIME NULL");
        }

        if (!in_array('subscription_ends_at', $columnNames)) {
            $this->db->query("ALTER TABLE tenants ADD COLUMN subscription_ends_at DATETIME NULL");
        }
    }

    public function down()
    {
        // Drop plans table if it exists
        if ($this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plans'")->getRow()) {
            $this->forge->dropTable('plans');
        }

        // Note: SQLite doesn't support dropping columns, so we'll need to recreate the table
        // For now, we'll leave the columns in place as they don't harm functionality
    }
}
