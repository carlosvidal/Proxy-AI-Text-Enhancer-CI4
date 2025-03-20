<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiKeyIdToButtons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('buttons', [
            'api_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'model'
            ]
        ]);

        // Add foreign key constraint
        $this->forge->addForeignKey('api_key_id', 'api_keys', 'api_key_id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('buttons');
    }

    public function down()
    {
        // Remove foreign key first
        $this->db->query('DROP INDEX IF EXISTS buttons_api_key_id_foreign ON buttons');
        
        // Then remove the column
        $this->forge->dropColumn('buttons', 'api_key_id');
    }
}
