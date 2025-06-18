<?php

// Test script to simulate the exact request that's failing
echo "=== TEST REQUEST SIMULATION ===\n\n";

$url = 'http://llmproxy.mitienda.host/api/llm-proxy';
$payload = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Test system prompt'
        ],
        [
            'role' => 'user', 
            'content' => 'Test user message'
        ]
    ],
    'temperature' => 0.7,
    'stream' => true,
    'tenantId' => 'ten-67d88d1d-111ae225',
    'userId' => '12965',
    'buttonId' => 'BTN00001',
    'hasImage' => false
];

echo "Testing URL: $url\n";
echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Origin: https://panel.mitienda.host'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "Response:\n";
echo $response;

curl_close($ch);

echo "\n=== END TEST ===\n";