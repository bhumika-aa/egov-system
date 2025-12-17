<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Check authentication and admin role
Auth::checkSessionTimeout();
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_births' => $conn->query("SELECT COUNT(*) as count FROM birth_registration")->fetch_assoc()['count'],
    'total_deaths' => $conn->query("SELECT COUNT(*) as count FROM death_registration")->fetch_assoc()['count'],
    'pending_applications' => $conn->query("SELECT COUNT(*) as count FROM (
        SELECT id FROM birth_registration WHERE status = 'Pending' 
        UNION ALL 
        SELECT id FROM death_registration WHERE status = 'Pending'
    ) as applications")->fetch_assoc()['count']
];

// Get recent activities
$recent_activities = $conn->query("
    SELECT * FROM audit_logs 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Get pending applications
$pending_applications = $conn->query("
    (SELECT 'Birth' as type, id, registration_number, child_first_name as name, created_at 
     FROM birth_registration WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'Death' as type, id, registration_number, deceased_first_name as name, created_at 
     FROM death_registration WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="logo">
                <h2><i class="fas fa-user-shield"></i> Admin Dashboard</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="user-role"><?php echo $_SESSION['user_role']; ?></span>
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
                <li><a href="manage_applications.php?type=birth"><i class="fas fa-baby"></i> Birth Applications</a></li>
                <li><a href="manage_applications.php?type=death"><i class="fas fa-cross"></i> Death Applications</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="container">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p>You are logged in as Administrator. Last login: <?php echo date('F j, Y, g:i a'); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <span class="count"><?php echo $stats['total_users']; ?></span>
                    <span class="label">Total Users</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-baby"></i>
                    <span class="count"><?php echo $stats['total_births']; ?></span>
                    <span class="label">Birth Registrations</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-cross"></i>
                    <span class="count"><?php echo $stats['total_deaths']; ?></span>
                    <span class="label">Death Registrations</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <span class="count"><?php echo $stats['pending_applications']; ?></span>
                    <span class="label">Pending Applications</span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <a href="manage_applications.php?type=birth" class="action-btn">
                        <i class="fas fa-baby"></i>
                        <span>Review Birth Apps</span>
                    </a>
                    <a href="manage_applications.php?type=death" class="action-btn">
                        <i class="fas fa-cross"></i>
                        <span>Review Death Apps</span>
                    </a>
                    <a href="add_user.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Add New User</span>
                    </a>
                    <a href="generate_report.php" class="action-btn">
                        <i class="fas fa-file-export"></i>
                        <span>Generate Report</span>
                    </a>
                    <a href="audit_logs.php" class="action-btn">
                        <i class="fas fa-history"></i>
                        <span>View Audit Logs</span>
                    </a>
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </div>
            </div>

            <!-- Pending Applications -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-clock"></i> Pending Applications</h3>
                    <a href="manage_applications.php" class="btn btn-view">View All</a>
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
                        <?php while($app = $pending_applications->fetch_assoc()): ?>
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
                            <td><span class="status status-pending">Pending</span></td>
                            <td>
                                <a href="view_application.php?type=<?php echo strtolower($app['type']); ?>&id=<?php echo $app['id']; ?>" 
                                   class="btn btn-view">
                                   <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activities -->
            <div class="recent-activity">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <ul class="activity-list">
                    <?php while($activity = $recent_activities->fetch_assoc()): ?>
                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text"><?php echo formatActivity($activity); ?></p>
                            <span class="activity-time">
                                <?php echo timeAgo($activity['created_at']); ?>
                            </span>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>

<?php
// Helper functions
function getActivityIcon($action) {
    $icons = [
        'USER_LOGIN' => 'sign-in-alt',
        'USER_LOGOUT' => 'sign-out-alt',
        'USER_REGISTER' => 'user-plus',
        'APPLICATION_SUBMIT' => 'paper-plane',
        'APPLICATION_APPROVE' => 'check-circle',
        'APPLICATION_REJECT' => 'times-circle',
        'PROFILE_UPDATE' => 'user-edit'
    ];
    return $icons[$action] ?? 'info-circle';
}

function formatActivity($activity) {
    $actions = [
        'USER_LOGIN' => 'User logged in',
        'USER_LOGOUT' => 'User logged out',
        'USER_REGISTER' => 'New user registered',
        'APPLICATION_SUBMIT' => 'Application submitted',
        'APPLICATION_APPROVE' => 'Application approved',
        'APPLICATION_REJECT' => 'Application rejected',
        'PROFILE_UPDATE' => 'Profile updated'
    ];
    
    return $actions[$activity['action']] ?? $activity['action'];
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>