<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsageIdToUsageLogs extends Migration
{
    public function up()
    {
        $db = db_connect();
        
        // 1. Agregar columna con un valor por defecto
        $sql = "ALTER TABLE usage_logs ADD COLUMN usage_id VARCHAR(255) NOT NULL DEFAULT 'pending'";
        $db->query($sql);

        // 2. Actualizar los registros existentes con IDs únicos
        $rows = $db->table('usage_logs')->get()->getResultArray();
        foreach ($rows as $row) {
            $usage_id = 'usage-' . bin2hex(random_bytes(8));
            $db->table('usage_logs')
                ->where('id', $row['id'])
                ->update(['usage_id' => $usage_id]);
        }

        // 3. Agregar índice único
        $sql = "CREATE UNIQUE INDEX idx_usage_logs_usage_id ON usage_logs(usage_id)";
        $db->query($sql);
    }

    public function down()
    {
        $db = db_connect();
        
        // 1. Eliminar el índice
        $sql = "DROP INDEX IF EXISTS idx_usage_logs_usage_id";
        $db->query($sql);

        // 2. Eliminar la columna
        $sql = "ALTER TABLE usage_logs DROP COLUMN usage_id";
        $db->query($sql);
    }
}
