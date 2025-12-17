<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Check if auth.php exists and is readable
if (!file_exists('includes/auth.php')) {
    die("Error: auth.php file not found!");
}

$auth = new Auth();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Check if POST data is received
    error_log("POST data received: " . print_r($_POST, true));
    
    // Validate CSRF token
    if (!function_exists('validateCSRFToken')) {
        $error = 'CSRF functions not loaded. Check config.php';
    } elseif (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Debug
        error_log("Processing registration for: $username, $email");
        
        // Validate password
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            try {
                $result = $auth->register($username, $email, $password, $full_name, $phone, $address);
                
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
                error_log("Auth error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-user-plus"></i> Create New Account</h2>
                <p>Register to access birth and death registration services</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?> 
                    <a href="login.php">Login here</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug info (remove in production) -->
            <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <strong>Debug Info:</strong><br>
                    Config exists: <?php echo file_exists('config.php') ? 'Yes' : 'No'; ?><br>
                    Auth exists: <?php echo file_exists('includes/auth.php') ? 'Yes' : 'No'; ?><br>
                    DB Connection: <?php 
                        try {
                            $test = getConnection();
                            echo "Connected";
                        } catch (Exception $e) {
                            echo "Failed: " . $e->getMessage();
                        }
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="username"><i class="fas fa-user"></i> Username *</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="Choose a username">
                    </div>
                    
                    <div class="form-group half">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="password"><i class="fas fa-lock"></i> Password *</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required 
                                   placeholder="At least 8 characters">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="password-hint">Must be at least 8 characters</small>
                    </div>
                    
                    <div class="form-group half">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="full_name"><i class="fas fa-id-card"></i> Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required 
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label for="address"><i class="fas fa-home"></i> Address</label>
                    <textarea id="address" name="address" rows="3" 
                              placeholder="Enter your permanent address"></textarea>
                </div>
                
                <div class="form-group checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="terms.php">Terms of Service</a> and 
                        <a href="privacy.php">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-auth">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleBtn = passwordInput.parentNode.querySelector('.toggle-password i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.classList.remove('fa-eye');
            toggleBtn.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleBtn.classList.remove('fa-eye-slash');
            toggleBtn.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>