<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDomainToButtons extends Migration
{
    public function up()
    {
        // Get the current table structure
        $table_info = $this->db->query("PRAGMA table_info('buttons')")->getResultArray();
        $existing_columns = array_column($table_info, 'name');

        // Log the existing columns for debugging
        log_message('debug', 'Existing columns in buttons: ' . implode(', ', $existing_columns));

        // Drop any leftover temporary table
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('buttons_new', $table_names)) {
            $this->forge->dropTable('buttons_new', true);
        }

        // Create a new table with all fields
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'unique' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => '*'  // * significa todos los dominios permitidos
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'openai'
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'gpt-3.5-turbo'
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        
        // Create temporary table
        $this->forge->createTable('buttons_new');

        // Copy existing data
        $this->db->query("INSERT INTO buttons_new (id, button_id, tenant_id, name, description, domain, prompt, provider, model, status, created_at, updated_at)
                         SELECT id, button_id, tenant_id, name, description, 
                                '*' as domain,
                                prompt,
                                'openai' as provider,
                                'gpt-3.5-turbo' as model,
                                'active' as status,
                                created_at,
                                updated_at
                         FROM buttons");

        // Drop old table
        $this->forge->dropTable('buttons', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE buttons_new RENAME TO buttons');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE UNIQUE INDEX idx_buttons_button_id_' . $timestamp . ' ON buttons(button_id)');
        $this->db->query('CREATE INDEX idx_buttons_tenant_' . $timestamp . ' ON buttons(tenant_id)');
        $this->db->query('CREATE INDEX idx_buttons_domain_' . $timestamp . ' ON buttons(domain)');
        $this->db->query('CREATE INDEX idx_buttons_status_' . $timestamp . ' ON buttons(status)');
    }

    public function down()
    {
        // Since we're just adding columns with default values, down migration is not critical
        // But we'll provide it for completeness
        
        // Create temporary table without the new fields
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'unique' => true
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');

        // Drop any leftover temporary table
        $tables = $this->db->query('SELECT name FROM sqlite_master WHERE type="table"')->getResultArray();
        $table_names = array_column($tables, 'name');
        if (in_array('buttons_new', $table_names)) {
            $this->forge->dropTable('buttons_new', true);
        }
        
        // Create temporary table
        $this->forge->createTable('buttons_new');

        // Copy data excluding the new fields
        $this->db->query("INSERT INTO buttons_new (id, button_id, tenant_id, name, description, prompt, status, created_at, updated_at)
                         SELECT id, button_id, tenant_id, name, description, prompt, status, created_at, updated_at
                         FROM buttons");

        // Drop old table
        $this->forge->dropTable('buttons', true);

        // Rename new table to old name
        $this->db->query('ALTER TABLE buttons_new RENAME TO buttons');

        // Add indices with unique names after renaming
        $timestamp = date('YmdHis');
        $this->db->query('CREATE UNIQUE INDEX idx_buttons_button_id_' . $timestamp . ' ON buttons(button_id)');
        $this->db->query('CREATE INDEX idx_buttons_tenant_' . $timestamp . ' ON buttons(tenant_id)');
        $this->db->query('CREATE INDEX idx_buttons_status_' . $timestamp . ' ON buttons(status)');
    }
}
