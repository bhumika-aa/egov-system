<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Check authentication
Auth::checkSessionTimeout();
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to access your profile.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        redirect('profile.php');
    }
    
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Check if email already exists (for other users)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Email already exists. Please use a different email.';
    } else {
        $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            logActivity('PROFILE_UPDATED', 'users', $user_id);
            $_SESSION['success'] = 'Profile updated successfully!';
        } else {
            $_SESSION['error'] = 'Error updating profile: ' . $update_stmt->error;
        }
    }
    
    redirect('profile.php');
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        redirect('profile.php');
    }
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            logActivity('PASSWORD_CHANGED', 'users', $user_id);
            $_SESSION['success'] = 'Password changed successfully!';
        } else {
            $_SESSION['error'] = 'Error changing password: ' . $update_stmt->error;
        }
    }
    
    redirect('profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Citizen Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user"></i> My Profile</h1>
                <p>Manage your account information and settings</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-circle"></i> Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="form-text text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" required 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                           placeholder="Enter your full name">
                                </div>
                                
                                <div class="form-group">
                                    <label>Email Address *</label>
                                    <input type="email" name="email" required 
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           placeholder="Enter your email">
                                </div>
                                
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           placeholder="Enter your phone number">
                                </div>
                                
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" rows="3" 
                                              placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Account Type</label>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label>Account Status</label>
                                    <input type="text" value="<?php echo ucfirst($user['status']); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label>Member Since</label>
                                    <input type="text" value="<?php echo formatDate($user['created_at'], 'F j, Y'); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label>Last Updated</label>
                                    <input type="text" value="<?php echo formatDate($user['updated_at'], 'F j, Y'); ?>" disabled>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="passwordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-group">
                                    <label>Current Password *</label>
                                    <div class="password-input">
                                        <input type="password" name="current_password" required 
                                               placeholder="Enter current password">
                                        <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password *</label>
                                    <div class="password-input">
                                        <input type="password" name="new_password" required 
                                               placeholder="Enter new password" id="new_password">
                                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="strength-meter" id="passwordStrength"></div>
                                    </div>
                                    <small class="form-text text-muted">Must be at least 8 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password *</label>
                                    <div class="password-input">
                                        <input type="password" name="confirm_password" required 
                                               placeholder="Confirm new password">
                                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account Security -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
                        </div>
                        <div class="card-body">
                            <div class="security-info">
                                <div class="security-item">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <div>
                                        <h4>Last Login</h4>
                                        <p><?php echo isset($_SESSION['last_login']) ? $_SESSION['last_login'] : 'Not available'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <i class="fas fa-history"></i>
                                    <div>
                                        <h4>Activity Log</h4>
                                        <p>Your account activities are being logged for security</p>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <h4>Email Notifications</h4>
                                        <p>You will receive email notifications for important updates</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="../logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout from all devices
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function togglePassword(fieldName) {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        const icon = field.parentNode.querySelector('.toggle-password i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Password strength indicator
    const newPassword = document.getElementById('new_password');
    const strengthMeter = document.getElementById('passwordStrength');
    
    newPassword.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 20;
        
        // Lowercase check
        if (/[a-z]/.test(password)) strength += 20;
        
        // Uppercase check
        if (/[A-Z]/.test(password)) strength += 20;
        
        // Number check
        if (/[0-9]/.test(password)) strength += 20;
        
        // Special character check
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;
        
        strengthMeter.style.width = strength + '%';
        
        if (strength < 40) {
            strengthMeter.className = 'strength-meter strength-weak';
        } else if (strength < 80) {
            strengthMeter.className = 'strength-meter strength-medium';
        } else {
            strengthMeter.className = 'strength-meter strength-strong';
        }
    });
    
    // Password form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const newPass = document.querySelector('input[name="new_password"]').value;
        const confirmPass = document.querySelector('input[name="confirm_password"]').value;
        
        if (newPass.length < 8) {
            e.preventDefault();
            alert('New password must be at least 8 characters long.');
            return false;
        }
        
        if (newPass !== confirmPass) {
            e.preventDefault();
            alert('New passwords do not match.');
            return false;
        }
        
        return true;
    });
    </script>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>