<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Check authentication and citizen role
Auth::checkSessionTimeout();
if (!Auth::hasRole('citizen')) {
    $_SESSION['error'] = 'Access denied. Citizen privileges required.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get user statistics
$stats = [
    'birth_applications' => $conn->query("SELECT COUNT(*) as count FROM birth_registration WHERE user_id = $user_id")->fetch_assoc()['count'],
    'death_applications' => $conn->query("SELECT COUNT(*) as count FROM death_registration WHERE user_id = $user_id")->fetch_assoc()['count'],
    'approved_applications' => $conn->query("SELECT COUNT(*) as count FROM (
        SELECT id FROM birth_registration WHERE user_id = $user_id AND status = 'Approved'
        UNION ALL
        SELECT id FROM death_registration WHERE user_id = $user_id AND status = 'Approved'
    ) as apps")->fetch_assoc()['count'],
    'pending_applications' => $conn->query("SELECT COUNT(*) as count FROM (
        SELECT id FROM birth_registration WHERE user_id = $user_id AND status = 'Pending'
        UNION ALL
        SELECT id FROM death_registration WHERE user_id = $user_id AND status = 'Pending'
    ) as apps")->fetch_assoc()['count']
];

// Get recent applications
$recent_applications = $conn->query("
    (SELECT 'Birth' as type, id, registration_number, child_first_name as name, status, created_at 
     FROM birth_registration WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'Death' as type, id, registration_number, deceased_first_name as name, status, created_at 
     FROM death_registration WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 3)
    ORDER BY created_at DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="logo">
                <h2><i class="fas fa-user-circle"></i> Citizen Dashboard</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="user-role">Citizen</span>
                </div>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="dashboard-nav">
    <div class="container">
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="birth_form.php"><i class="fas fa-baby"></i> Register Birth</a></li>
            <li><a href="death_form.php"><i class="fas fa-cross"></i> Register Death</a></li>
            <li><a href="my_applications.php"><i class="fas fa-file-alt"></i> My Applications</a></li>
            <li><a href="certificates.php"><i class="fas fa-certificate"></i> Certificates</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        </ul>
    </div>
</nav>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="container">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p>Manage your birth and death registration applications from here.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-baby"></i>
                    <span class="count"><?php echo $stats['birth_applications']; ?></span>
                    <span class="label">Birth Applications</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-cross"></i>
                    <span class="count"><?php echo $stats['death_applications']; ?></span>
                    <span class="label">Death Applications</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <span class="count"><?php echo $stats['approved_applications']; ?></span>
                    <span class="label">Approved</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <span class="count"><?php echo $stats['pending_applications']; ?></span>
                    <span class="label">Pending</span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <a href="birth_form.php" class="action-btn">
                        <i class="fas fa-baby"></i>
                        <span>Register Birth</span>
                    </a>
                    <a href="death_form.php" class="action-btn">
                        <i class="fas fa-cross"></i>
                        <span>Register Death</span>
                    </a>
                    <a href="my_applications.php" class="action-btn">
                        <i class="fas fa-list"></i>
                        <span>View Applications</span>
                    </a>
                    <a href="certificates.php" class="action-btn">
                        <i class="fas fa-download"></i>
                        <span>Download Certificates</span>
                    </a>
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user-edit"></i>
                        <span>Update Profile</span>
                    </a>
                    <a href="../help.php" class="action-btn">
                        <i class="fas fa-question-circle"></i>
                        <span>Help Center</span>
                    </a>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-history"></i> Recent Applications</h3>
                    <a href="my_applications.php" class="btn btn-view">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Registration No.</th>
                            <th>Name</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($app = $recent_applications->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo strtolower($app['type']); ?>">
                                    <i class="fas fa-<?php echo $app['type'] == 'Birth' ? 'baby' : 'cross'; ?>"></i>
                                    <?php echo $app['type']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($app['registration_number']); ?></td>
                            <td><?php echo htmlspecialchars($app['name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <?php 
                                $status_class = strtolower($app['status']);
                                echo "<span class='status status-$status_class'>{$app['status']}</span>";
                                ?>
                            </td>
                            <td>
                                <a href="view_application.php?type=<?php echo strtolower($app['type']); ?>&id=<?php echo $app['id']; ?>" 
                                   class="btn btn-view">
                                   <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($recent_applications->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">No applications found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Application Status Guide -->
            <div class="quick-actions">
                <h3><i class="fas fa-info-circle"></i> Application Status Guide</h3>
                <div class="status-guide">
                    <div class="status-item">
                        <span class="status status-pending">Pending</span>
                        <p>Your application is under review</p>
                    </div>
                    <div class="status-item">
                        <span class="status status-verified">Verified</span>
                        <p>Application is verified, awaiting approval</p>
                    </div>
                    <div class="status-item">
                        <span class="status status-approved">Approved</span>
                        <p>Application approved, certificate available</p>
                    </div>
                    <div class="status-item">
                        <span class="status status-rejected">Rejected</span>
                        <p>Application rejected, check remarks</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>