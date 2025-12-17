<?php
// login.php - Fixed version
require_once 'config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        if ($result['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('citizen/dashboard.php');
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Fixed CSS to prevent scrolling issues -->
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-y: auto; /* Ensure scrolling works */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Auth Container */
        .auth-container {
            width: 100%;
            max-width: 500px;
            margin: auto;
        }

        /* Auth Card */
        .auth-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Auth Header */
        .auth-header {
            background: linear-gradient(to right, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .auth-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .auth-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #080;
        }

        /* Auth Form */
        .auth-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group label i {
            color: #3498db;
            margin-right: 8px;
            width: 20px;
        }

        /* Input Fields - Fixed to remove small rectangles */
        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
            color: #333;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group input:hover {
            border-color: #bbb;
        }

        /* Password Input Container */
        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #3498db;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .checkbox input {
            width: auto;
            margin: 0;
        }

        .forgot-link {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Login Button */
        .btn-auth {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #3498db, #2c3e50);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            background: linear-gradient(to right, #2980b9, #2c3e50);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        /* Auth Links */
        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .auth-links p {
            margin-bottom: 10px;
            color: #666;
            font-size: 14px;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        /* Auth Info */
        .auth-info {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #eee;
        }

        .auth-info h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }

        .auth-info ul {
            list-style: none;
            padding-left: 0;
        }

        .auth-info li {
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
            color: #555;
            font-size: 14px;
        }

        .auth-info li:before {
            content: "•";
            color: #3498db;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            body {
                padding: 10px;
                align-items: flex-start;
                padding-top: 20px;
            }
            
            .auth-card {
                border-radius: 10px;
            }
            
            .auth-header {
                padding: 20px;
            }
            
            .auth-header h2 {
                font-size: 24px;
            }
            
            .auth-form {
                padding: 20px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        /* Animation for form elements */
        .form-group input {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Remove number input spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Placeholder styling */
        ::placeholder {
            color: #999;
            opacity: 1;
        }

        :-ms-input-placeholder {
            color: #999;
        }

        ::-ms-input-placeholder {
            color: #999;
        }
    </style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-sign-in-alt"></i> Login to Your Account</h2>
                <p>Access your birth and death registration portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Registration successful! Please login.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['timeout']) && $_GET['timeout'] == '1'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-clock"></i> Your session has expired. Please login again.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           placeholder="Enter your username or email"
                           autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-input">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Enter your password"
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember" id="remember">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-auth">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="auth-links">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
            </form>
            
            <div class="auth-info">
                <h4><i class="fas fa-info-circle"></i> Login Instructions:</h4>
                <ul>
                    <li>Use your registered username or email</li>
                    <li>Admin login: username: admin, password: admin123</li>
                    <li>Contact support if you face any issues</li>
                    <li>Ensure cookies are enabled for session management</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
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
        
        // Form validation
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username) {
                alert('Please enter your username or email');
                e.preventDefault();
                return false;
            }
            
            if (!password) {
                alert('Please enter your password');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
            
            // Check if remember me was checked previously
            if (localStorage.getItem('rememberMe') === 'true') {
                document.getElementById('remember').checked = true;
                const savedUsername = localStorage.getItem('username');
                if (savedUsername) {
                    document.getElementById('username').value = savedUsername;
                }
            }
            
            // Save remember me preference
            document.getElementById('remember').addEventListener('change', function() {
                if (this.checked) {
                    localStorage.setItem('rememberMe', 'true');
                    localStorage.setItem('username', document.getElementById('username').value);
                } else {
                    localStorage.removeItem('rememberMe');
                    localStorage.removeItem('username');
                }
            });
        });
        
        // Save username when typing if remember me is checked
        document.getElementById('username').addEventListener('input', function() {
            if (document.getElementById('remember').checked) {
                localStorage.setItem('username', this.value);
            }
        });
    </script>
</body>
</html>