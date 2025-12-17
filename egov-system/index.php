

<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <h1><i class="fas fa-landmark"></i> <?php echo SITE_NAME; ?></h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#services"><i class="fas fa-concierge-bell"></i> Services</a></li>
                    <li><a href="#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="#contact"><i class="fas fa-envelope"></i> Contact</a></li>
                    <li><a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="btn-register"><i class="fas fa-user-plus"></i> Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Online Birth & Death Registration System</h2>
                <p>Register births and deaths online from the comfort of your home. Fast, secure, and government-approved.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Register as Citizen</a>
                    <a href="login.php" class="btn-secondary"><i class="fas fa-sign-in-alt"></i> Login to Portal</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="img/birth.jpg" alt="Government Services">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-baby"></i>
                    </div>
                    <h3>Birth Registration</h3>
                    <p>Register your newborn child online. Get birth certificate delivered to your home.</p>
                    <a href="login.php" class="btn-service">Register Now</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cross"></i>
                    </div>
                    <h3>Death Registration</h3>
                    <p>Register death incidents and obtain official death certificates.</p>
                    <a href="login.php" class="btn-service">Register Now</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-file-certificate"></i>
                    </div>
                    <h3>Certificate Download</h3>
                    <p>Download digitally signed birth and death certificates anytime.</p>
                    <a href="login.php" class="btn-service">Download</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Application Tracking</h3>
                    <p>Track your application status in real-time.</p>
                    <a href="login.php" class="btn-service">Track Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="statistics">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <h3><span class="counter" data-target="12500">0</span>+</h3>
                    <p>Registered Users</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-baby"></i>
                    <h3><span class="counter" data-target="8500">0</span>+</h3>
                    <p>Births Registered</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-cross"></i>
                    <h3><span class="counter" data-target="3200">0</span>+</h3>
                    <p>Deaths Registered</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-check-circle"></i>
                    <h3><span class="counter" data-target="98">0</span>%</h3>
                    <p>Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-landmark"></i> <?php echo SITE_NAME; ?></h3>
                    <p>Government authorized platform for birth and death registration services.</p>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Government Office, Kathmandu, Nepal</p>
                    <p><i class="fas fa-phone"></i> +977-1-1234567</p>
                    <p><i class="fas fa-envelope"></i> info@egov.gov.np</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>