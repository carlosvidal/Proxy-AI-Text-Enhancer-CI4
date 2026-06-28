<?php

// Script to fix the API key assignment for button BTN00001
$tenant_id = 'ten-67d88d1d-111ae225';
$button_id = 'BTN00001';
$api_key_id = 'key-685215f4-95daf480';

echo "=== FIX BUTTON API KEY ASSIGNMENT ===\n\n";

try {
    $pdo = new PDO("sqlite:writable/database.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n\n";
    
    // Verify button exists
    $stmt = $pdo->prepare("SELECT button_id, api_key_id FROM buttons WHERE button_id = ?");
    $stmt->execute([$button_id]);
    $button = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$button) {
        echo "❌ Button $button_id not found!\n";
        exit(1);
    }
    
    echo "Current button API key: '" . $button['api_key_id'] . "'\n";
    
    // Verify API key exists
    $stmt = $pdo->prepare("SELECT api_key_id, name, provider FROM api_keys WHERE api_key_id = ?");
    $stmt->execute([$api_key_id]);
    $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$apiKey) {
        echo "❌ API key $api_key_id not found!\n";
        exit(1);
    }
    
    echo "API key to assign: {$apiKey['name']} ({$apiKey['provider']})\n\n";
    
    // Update the button
    $stmt = $pdo->prepare("UPDATE buttons SET api_key_id = ? WHERE button_id = ?");
    $result = $stmt->execute([$api_key_id, $button_id]);
    
    if ($result) {
        echo "✅ Successfully assigned API key '$api_key_id' to button '$button_id'\n\n";
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT api_key_id FROM buttons WHERE button_id = ?");
        $stmt->execute([$button_id]);
        $updatedButton = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verification - Button now has API key: '{$updatedButton['api_key_id']}'\n";
        
        if ($updatedButton['api_key_id'] === $api_key_id) {
            echo "✅ UPDATE SUCCESSFUL! You can now test the web component.\n";
        } else {
            echo "❌ UPDATE FAILED! API key was not assigned correctly.\n";
        }
    } else {
        echo "❌ Failed to update button API key!\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== END FIX ===\n";