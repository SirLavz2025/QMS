<?php
require 'db.php';

// Generate a clean, uncorrupted hash for 'password123'
$new_password = password_hash('password123', PASSWORD_BCRYPT);

try {
    // Force update the admin account to use this fresh hash
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@faculty.com'");
    $stmt->execute([$new_password]);
    
    echo "<h3>Success! The password for admin@faculty.com has been cleanly reset to: password123</h3>";
    echo "<p>Please delete this file (reset.php) from your server now for security purposes, then try logging in again.</p>";
} catch (\PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>