<?php

$db = new SQLite3('writable/database.sqlite');

$username = 'demo_user';
$email = 'demo@example.com';
$password = 'demo123';
$name = 'Demo User';
$role = 'tenant';
$tenant_id = 'demo123';
$active = 1;
$now = date('Y-m-d H:i:s');

// Create password hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Prepare the statement
$stmt = $db->prepare('INSERT INTO users (username, email, password, name, role, tenant_id, active, created_at, updated_at) VALUES (:username, :email, :password, :name, :role, :tenant_id, :active, :created_at, :updated_at)');

// Bind parameters
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':password', $hash, SQLITE3_TEXT);
$stmt->bindValue(':name', $name, SQLITE3_TEXT);
$stmt->bindValue(':role', $role, SQLITE3_TEXT);
$stmt->bindValue(':tenant_id', $tenant_id, SQLITE3_TEXT);
$stmt->bindValue(':active', $active, SQLITE3_INTEGER);
$stmt->bindValue(':created_at', $now, SQLITE3_TEXT);
$stmt->bindValue(':updated_at', $now, SQLITE3_TEXT);

// Execute the statement
$result = $stmt->execute();

if ($result) {
    echo "Demo user created successfully\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Generated hash: $hash\n";

    // Verify the hash
    if (password_verify($password, $hash)) {
        echo "Password verification test: PASS\n";
    } else {
        echo "Password verification test: FAIL\n";
    }
} else {
    echo "Error creating demo user\n";
}

// Close the database connection
$db->close();
