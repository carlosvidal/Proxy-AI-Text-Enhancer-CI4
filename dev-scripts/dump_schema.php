<?php
// save as dump_schema.php in la raíz de tu proyecto

require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

$db = \Config\Database::connect();
$tables = $db->listTables();

echo "-- Generated SQLite Schema Dump\n\n";

foreach ($tables as $table) {
    if ($table === 'migrations') continue; // Omitir la tabla de migraciones

    echo "-- Table: {$table}\n";
    $result = $db->query("PRAGMA table_info({$table})");
    $columns = $result->getResultArray();

    echo "CREATE TABLE IF NOT EXISTS {$table} (\n";

    $columnDefs = [];
    foreach ($columns as $column) {
        $nullable = $column['notnull'] == 1 ? 'NOT NULL' : 'NULL';
        $default = $column['dflt_value'] !== null ? "DEFAULT " . $column['dflt_value'] : "";
        $pk = $column['pk'] == 1 ? 'PRIMARY KEY' : '';
        $columnDefs[] = "    {$column['name']} {$column['type']} {$nullable} {$default} {$pk}";
    }

    echo implode(",\n", $columnDefs);
    echo "\n);\n\n";

    // Obtener los índices para esta tabla
    $indices = $db->query("PRAGMA index_list({$table})");
    $indexRows = $indices->getResultArray();

    foreach ($indexRows as $index) {
        if ($index['origin'] === 'pk') continue; // Omitir índice de clave primaria

        $indexColumns = $db->query("PRAGMA index_info({$index['name']})");
        $columns = [];

        foreach ($indexColumns->getResultArray() as $col) {
            $columns[] = $col['name'];
        }

        $columnList = implode(', ', $columns);
        $unique = $index['unique'] ? 'UNIQUE' : '';
        echo "CREATE {$unique} INDEX IF NOT EXISTS {$index['name']} ON {$table} ({$columnList});\n";
    }

    echo "\n";
}
