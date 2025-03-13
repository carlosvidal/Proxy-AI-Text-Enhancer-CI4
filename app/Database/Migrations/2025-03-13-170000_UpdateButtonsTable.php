<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateButtonsTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if button_id exists but is empty
        $buttons = $db->table('buttons')->get()->getResultArray();
        foreach ($buttons as $button) {
            if (empty($button['button_id'])) {
                // Generate a new button_id in the format btn-{timestamp}-{random}
                helper('hash');
                $buttonId = generate_hash_id('btn');
                
                // Update the button with the new button_id
                $db->table('buttons')
                    ->where('id', $button['id'])
                    ->update(['button_id' => $buttonId]);
            }
        }

        // Make button_id NOT NULL and unique if it isn't already
        $fields = [
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ]
        ];
        $this->forge->modifyColumn('buttons', $fields);

        // Add unique index if it doesn't exist
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS buttons_button_id_unique ON buttons(button_id)');

        // Update usage_logs table to use button_id if it doesn't already exist
        $fields = $db->getFieldNames('usage_logs');
        if (!in_array('button_id', $fields)) {
            $this->forge->addColumn('usage_logs', [
                'button_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'tenant_id'
                ]
            ]);

            // Copy button IDs to usage_logs using SQLite compatible syntax
            $sql = "UPDATE usage_logs 
                    SET button_id = (
                        SELECT button_id 
                        FROM buttons 
                        WHERE buttons.id = usage_logs.button_id
                    )
                    WHERE EXISTS (
                        SELECT 1 
                        FROM buttons 
                        WHERE buttons.id = usage_logs.button_id
                    )";
            $db->query($sql);
        }
    }

    public function down()
    {
        // Since this is a data migration that updates IDs,
        // we don't provide a down() method to prevent data loss
        return;
    }
}
