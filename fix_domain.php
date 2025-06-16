<?php

// Quick script to fix the domain issue
$db = new SQLite3('writable/database.sqlite');

// Get current domain
$result = $db->query("SELECT domain FROM buttons WHERE button_id = 'btn-684cdeaa-d5a2afea'");
$row = $result->fetchArray();
$currentDomain = $row['domain'];

echo "Current domain: " . $currentDomain . "\n";

// Update to include both ports and llmproxy.test
$newDomain = 'http://127.0.0.1:5500,http://127.0.0.1:5501,http://llmproxy.test:8080';
$stmt = $db->prepare("UPDATE buttons SET domain = ? WHERE button_id = 'btn-684cdeaa-d5a2afea'");
$stmt->bindValue(1, $newDomain);
$result = $stmt->execute();

if ($result) {
    echo "Domain updated successfully to: " . $newDomain . "\n";
} else {
    echo "Error updating domain\n";
}

// Verify the update
$result = $db->query("SELECT domain FROM buttons WHERE button_id = 'btn-684cdeaa-d5a2afea'");
$row = $result->fetchArray();
echo "New domain: " . $row['domain'] . "\n";

$db->close();
?>