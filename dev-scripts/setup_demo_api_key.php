<?php

// Script para configurar API key de desarrollo para el demo local
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsPath = __DIR__ . '/app/Config/Paths.php';
require realpath($pathsPath) ?: $pathsPath;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

// Configuración para el demo
$demo_tenant_id = 'ten-684cc05b-5d6457e5';
$demo_user_id = 'DEMO';
$demo_api_key = readline("Ingresa tu API key de OpenAI (sk-...): ");

if (empty($demo_api_key) || substr($demo_api_key, 0, 3) !== 'sk-') {
    echo "❌ API key inválida. Debe empezar con 'sk-'\n";
    exit(1);
}

echo "\n🔧 Configurando API key para desarrollo local...\n";
echo "=====================================\n";
echo "Tenant ID: {$demo_tenant_id}\n";
echo "User ID: {$demo_user_id}\n";
echo "API Key: " . substr($demo_api_key, 0, 10) . "...\n\n";

try {
    // Verificar que la encryption key esté configurada
    $encrypter = \Config\Services::encrypter();
    
    // Test de encriptación
    $test_encrypted = base64_encode($encrypter->encrypt('test'));
    $test_decrypted = $encrypter->decrypt(base64_decode($test_encrypted));
    
    if ($test_decrypted !== 'test') {
        echo "❌ Error: Encryption no está funcionando correctamente\n";
        echo "Por favor verifica que la encryption.key esté configurada en el archivo env\n";
        exit(1);
    }
    
    echo "✅ Encryption funcionando correctamente\n";
    
    // Modelo de API keys
    $apiKeysModel = new \App\Models\ApiKeysModel();
    
    // Verificar si ya existe una API key para este tenant
    $existing = $apiKeysModel->where('tenant_id', $demo_tenant_id)
                            ->where('provider', 'openai')
                            ->first();
    
    if ($existing) {
        echo "🔄 Actualizando API key existente...\n";
        $result = $apiKeysModel->update($existing['id'], [
            'api_key' => $demo_api_key,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "➕ Creando nueva API key...\n";
        helper('hash');
        $result = $apiKeysModel->insert([
            'api_key_id' => generate_hash_id('key'),
            'tenant_id' => $demo_tenant_id,
            'name' => 'Demo Development Key',
            'provider' => 'openai',
            'api_key' => $demo_api_key,
            'is_default' => 1,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    if ($result) {
        echo "✅ API key configurada exitosamente!\n";
        
        // Verificar que se puede leer correctamente
        $retrieved = $apiKeysModel->getDefaultKey($demo_tenant_id, 'openai');
        
        if ($retrieved && $retrieved['api_key'] === $demo_api_key) {
            echo "✅ Verificación exitosa: API key se puede leer correctamente\n";
            echo "\n🎉 ¡Configuración completada!\n";
            echo "\nAhora tu demo local debería funcionar correctamente.\n";
            echo "Puedes probar el componente en: http://127.0.0.1:5500/demo/mitienda-2.html\n";
        } else {
            echo "❌ Error: No se pudo verificar la API key guardada\n";
            if ($retrieved) {
                echo "Expected: " . substr($demo_api_key, 0, 10) . "...\n";
                echo "Got: " . substr($retrieved['api_key'] ?? 'null', 0, 10) . "...\n";
            }
        }
    } else {
        echo "❌ Error al guardar la API key\n";
        echo "Errores: " . json_encode($apiKeysModel->errors()) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Script completado!\n";