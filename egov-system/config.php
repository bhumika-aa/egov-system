
<?php

// Add this at the top of config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// config.php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
// In config.php
define('DB_PASS', ''); // Default XAMPP password is ''
define('DB_NAME', 'egov');

// Site Configuration
define('SITE_NAME', 'Birth & Death Registration System');
define('SITE_URL', 'http://localhost/egov-system/');
define('ADMIN_EMAIL', 'admin@egov.com');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// File Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Get database connection
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Generate CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Validate CSRF Token
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
        return false;
    }
    return true;
}

// Sanitize Input
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Log activity
function logActivity($action, $table = null, $record_id = null, $old_values = null, $new_values = null) {
    $conn = getConnection();
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiisss", $user_id, $action, $table, $record_id, $old_values, $new_values, $ip_address, $user_agent);
    $stmt->execute();
}
?>