<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddButtonIdColumn extends Migration
{
    public function up()
    {
        // Check if button_id column already exists in buttons table
        $fields = $this->db->getFieldData('buttons');
        $button_id_exists = false;

        foreach ($fields as $field) {
            if ($field->name === 'button_id') {
                $button_id_exists = true;
                break;
            }
        }

        // If button_id column doesn't exist, add it
        if (!$button_id_exists) {
            $this->forge->addColumn('buttons', [
                'button_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                    'after' => 'id'
                ]
            ]);

            // Create a unique index
            $this->db->query('CREATE UNIQUE INDEX button_id_unique ON buttons (button_id)');

            // Now generate button_id for all existing buttons
            $buttons = $this->db->table('buttons')->get()->getResultArray();

            foreach ($buttons as $button) {
                if (empty($button['button_id'])) {
                    // Generate a unique ID
                    $button_id = bin2hex(random_bytes(8)); // 16 characters hex

                    // Verify that it's unique
                    while ($this->db->table('buttons')->where('button_id', $button_id)->countAllResults() > 0) {
                        $button_id = bin2hex(random_bytes(8));
                    }

                    // Update the record
                    $this->db->table('buttons')
                        ->where('id', $button['id'])
                        ->update(['button_id' => $button_id]);
                }
            }

            // Now make it required
            $this->forge->modifyColumn('buttons', [
                'button_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => false,
                ]
            ]);
        }
    }

    public function down()
    {
        // We don't remove the column for data safety
    }
}
