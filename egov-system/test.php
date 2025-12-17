<?php
session_start();
require_once 'config.php';

$conn = getConnection();

// Simple test
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
$admin = $result->fetch_assoc();

echo "<h2>Debug Info</h2>";
echo "Admin user exists: " . ($admin ? "YES" : "NO") . "<br>";
echo "Admin username: " . ($admin['username'] ?? 'NOT FOUND') . "<br>";
echo "Admin password in DB: " . ($admin['password'] ?? 'NOT FOUND') . "<br>";
echo "Password length: " . strlen($admin['password'] ?? '') . "<br>";

// Test password
$test_password = 'admin123';
echo "<br>Testing password 'admin123':<br>";
echo "Direct compare: " . ($test_password === $admin['password'] ? "MATCH" : "NO MATCH") . "<br>";
echo "password_verify: " . (password_verify($test_password, $admin['password']) ? "MATCH" : "NO MATCH") . "<br>";

// Test auth class
echo "<h2>Testing Auth Class</h2>";
require_once 'includes/auth.php';
$auth = new Auth();
$result = $auth->login('admin', 'admin123');
echo "Login result: " . print_r($result, true);
?>