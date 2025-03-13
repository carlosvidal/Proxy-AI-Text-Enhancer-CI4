<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to update buttons table to use button_id as primary identifier
 */
class UpdateButtonsTable2024 extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if button_id exists but is empty
        $buttons = $db->table('buttons')->get()->getResultArray();
        foreach ($buttons as $button) {
            if (empty($button['button_id'])) {
                // Create a button_id from the name (lowercase, replace spaces with hyphens)
                $buttonId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($button['name'])));
                
                // Ensure uniqueness by adding a number if needed
                $baseButtonId = $buttonId;
                $counter = 1;
                while ($db->table('buttons')->where('button_id', $buttonId)->countAllResults() > 0) {
                    $buttonId = $baseButtonId . '-' . $counter;
                    $counter++;
                }
                
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
                'constraint' => 255,
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
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'tenant_id'
                ]
            ]);

            // Copy button IDs to usage_logs
            $sql = "UPDATE usage_logs ul 
                    JOIN buttons b ON ul.button_id = b.id 
                    SET ul.button_id = b.button_id";
            $db->query($sql);
        }

        // We'll keep the id column for now to ensure backward compatibility
        // and remove it in a future migration after all code is updated
    }

    public function down()
    {
        // Since we're keeping the id column for now, down() doesn't need to do anything
        // The changes will be reverted in a future migration
    }
}
