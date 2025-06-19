<?php

// Script simple para configurar API key usando SQLite directamente
echo "🔧 Configurando API key para desarrollo local...\n";
echo "=====================================\n";

$demo_tenant_id = 'ten-684cc05b-5d6457e5';
$demo_api_key = 'YOUR_API_KEY_HERE'; // Reemplaza con tu API key real

echo "Tenant ID: {$demo_tenant_id}\n";
echo "API Key: " . substr($demo_api_key, 0, 10) . "...\n\n";

try {
    // Conectar a la base de datos SQLite directamente
    $db = new SQLite3('writable/database.sqlite');
    
    // Verificar si la tabla existe
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='api_keys'");
    if (!$result->fetchArray()) {
        echo "❌ Error: La tabla 'api_keys' no existe\n";
        echo "Ejecuta primero las migraciones de la base de datos\n";
        exit(1);
    }
    
    // Verificar si ya existe una API key para este tenant
    $stmt = $db->prepare('SELECT * FROM api_keys WHERE tenant_id = ? AND provider = ?');
    $stmt->bindValue(1, $demo_tenant_id, SQLITE3_TEXT);
    $stmt->bindValue(2, 'openai', SQLITE3_TEXT);
    $result = $stmt->execute();
    $existing = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($existing) {
        echo "🔄 Actualizando API key existente...\n";
        $update_stmt = $db->prepare('UPDATE api_keys SET api_key = ?, updated_at = ? WHERE api_key_id = ?');
        $update_stmt->bindValue(1, $demo_api_key, SQLITE3_TEXT);
        $update_stmt->bindValue(2, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $update_stmt->bindValue(3, $existing['api_key_id'], SQLITE3_TEXT);
        $success = $update_stmt->execute();
    } else {
        echo "➕ Creando nueva API key...\n";
        
        // Generar un hash ID simple
        $api_key_id = 'key-' . bin2hex(random_bytes(16));
        
        $insert_stmt = $db->prepare('INSERT INTO api_keys (api_key_id, tenant_id, name, provider, api_key, is_default, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert_stmt->bindValue(1, $api_key_id, SQLITE3_TEXT);
        $insert_stmt->bindValue(2, $demo_tenant_id, SQLITE3_TEXT);
        $insert_stmt->bindValue(3, 'Demo Development Key', SQLITE3_TEXT);
        $insert_stmt->bindValue(4, 'openai', SQLITE3_TEXT);
        $insert_stmt->bindValue(5, $demo_api_key, SQLITE3_TEXT); // Sin encriptar para desarrollo
        $insert_stmt->bindValue(6, 1, SQLITE3_INTEGER);
        $insert_stmt->bindValue(7, 1, SQLITE3_INTEGER);
        $insert_stmt->bindValue(8, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $insert_stmt->bindValue(9, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $success = $insert_stmt->execute();
    }
    
    if ($success) {
        echo "✅ API key configurada exitosamente!\n";
        
        // Verificar la configuración
        $verify_stmt = $db->prepare('SELECT * FROM api_keys WHERE tenant_id = ? AND provider = ?');
        $verify_stmt->bindValue(1, $demo_tenant_id, SQLITE3_TEXT);
        $verify_stmt->bindValue(2, 'openai', SQLITE3_TEXT);
        $verify_result = $verify_stmt->execute();
        $verified = $verify_result->fetchArray(SQLITE3_ASSOC);
        
        if ($verified) {
            echo "✅ Verificación exitosa: API key guardada correctamente\n";
            echo "   - ID: " . $verified['api_key_id'] . "\n";
            echo "   - Tenant: " . $verified['tenant_id'] . "\n";
            echo "   - Provider: " . $verified['provider'] . "\n";
            echo "   - Active: " . ($verified['active'] ? 'Sí' : 'No') . "\n";
            echo "   - Default: " . ($verified['is_default'] ? 'Sí' : 'No') . "\n";
            echo "\n🎉 ¡Configuración completada!\n";
            echo "\nNOTA: La API key se guardó sin encriptar para desarrollo local.\n";
            echo "En producción se debe usar encriptación.\n";
            echo "\nAhora tu demo local debería funcionar correctamente.\n";
            echo "Puedes probar el componente en: http://127.0.0.1:5500/demo/mitienda-2.html\n";
        } else {
            echo "❌ Error: No se pudo verificar la API key guardada\n";
        }
    } else {
        echo "❌ Error al guardar la API key: " . $db->lastErrorMsg() . "\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Script completado!\n";