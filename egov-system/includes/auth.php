<?php
// includes/auth.php
require_once dirname(__DIR__) . '/config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Register new user
    public function register($username, $email, $password, $full_name, $phone = null, $address = null) {
        // Check if username or email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'citizen')");
        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $address);
        
        if ($stmt->execute()) {
            logActivity('USER_REGISTER', 'users', $this->conn->insert_id);
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $stmt->error];
        }
    }
    
    // Login user - FIXED VERSION
    public function login($username, $password) {
        // Remove status check since we don't have status column
        $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check password (accept both hashed and plain text)
            $password_match = false;
            
            // First check if it's a hashed password
            if (password_verify($password, $user['password'])) {
                $password_match = true;
            } 
            // If not hashed, check plain text
            else if ($password === $user['password']) {
                $password_match = true;
                
                // Update to hashed password for next time
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user['id']);
                $update_stmt->execute();
            }
            
            if ($password_match) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['last_activity'] = time();
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('USER_LOGIN', 'users', $user['id']);
                }
                
                return ['success' => true, 'role' => $user['role']];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Check if user has specific role
    public static function hasRole($required_role) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        // Admin has access to everything
        if ($_SESSION['user_role'] === 'admin') {
            return true;
        }
        
        return $_SESSION['user_role'] === $required_role;
    }
    
    // Require specific role
    public static function requireRole($required_role) {
        if (!self::hasRole($required_role)) {
            $_SESSION['error'] = 'Access denied. Insufficient permissions.';
            header("Location: ../login.php");
            exit();
        }
    }
    
    // Check session timeout
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            self::logout();
            header("Location: ../login.php?timeout=1");
            exit();
        }
        $_SESSION['last_activity'] = time();
    }
    
    // Logout
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            if (function_exists('logActivity')) {
                logActivity('USER_LOGOUT', 'users', $_SESSION['user_id']);
            }
        }
        
        session_unset();
        session_destroy();
        session_start();
    }
}
?>