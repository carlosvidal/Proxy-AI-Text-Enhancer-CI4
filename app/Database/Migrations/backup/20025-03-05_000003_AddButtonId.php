<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddButtonId extends Migration
{
    public function up()
    {
        // Añadimos el campo button_id a la tabla buttons
        $this->forge->addColumn('buttons', [
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
                'after' => 'id'
            ]
        ]);

        // Crear un índice único
        $this->db->query('CREATE UNIQUE INDEX button_id_unique ON buttons (button_id)');

        // Ahora generamos button_id para todos los botones existentes
        $buttons = $this->db->table('buttons')->get()->getResultArray();

        foreach ($buttons as $button) {
            if (empty($button['button_id'])) {
                // Generar un ID único
                $button_id = bin2hex(random_bytes(8)); // 16 caracteres hex

                // Verificar que sea único
                while ($this->db->table('buttons')->where('button_id', $button_id)->countAllResults() > 0) {
                    $button_id = bin2hex(random_bytes(8));
                }

                // Actualizar el registro
                $this->db->table('buttons')
                    ->where('id', $button['id'])
                    ->update(['button_id' => $button_id]);
            }
        }

        // Ahora lo hacemos obligatorio
        $this->forge->modifyColumn('buttons', [
            'button_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ]
        ]);
    }

    public function down()
    {
        // No eliminamos la columna por seguridad
    }
}
