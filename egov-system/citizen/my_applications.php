<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check authentication and citizen role
Auth::checkSessionTimeout();
if (!Auth::hasRole('citizen')) {
    $_SESSION['error'] = 'Access denied. Please login as citizen.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get filter parameters
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query for birth applications
$birth_where = ["user_id = $user_id"];
if ($status_filter && $status_filter != 'all') {
    $birth_where[] = "status = '$status_filter'";
}
if ($search) {
    $birth_where[] = "(child_first_name LIKE '%$search%' OR child_last_name LIKE '%$search%' OR registration_number LIKE '%$search%')";
}
$birth_where_clause = implode(' AND ', $birth_where);

// Build query for death applications
$death_where = ["user_id = $user_id"];
if ($status_filter && $status_filter != 'all') {
    $death_where[] = "status = '$status_filter'";
}
if ($search) {
    $death_where[] = "(deceased_first_name LIKE '%$search%' OR deceased_last_name LIKE '%$search%' OR registration_number LIKE '%$search%')";
}
$death_where_clause = implode(' AND ', $death_where);

// Get applications based on filter
$applications = [];
$total_count = 0;

if (!$type_filter || $type_filter == 'birth') {
    $birth_query = "SELECT 'birth' as type, id, registration_number, 
                    CONCAT(child_first_name, ' ', child_last_name) as name,
                    status, created_at, remarks
                    FROM birth_registration 
                    WHERE $birth_where_clause 
                    ORDER BY created_at DESC";
    $birth_result = $conn->query($birth_query);
    while ($row = $birth_result->fetch_assoc()) {
        $applications[] = $row;
        $total_count++;
    }
}

if (!$type_filter || $type_filter == 'death') {
    $death_query = "SELECT 'death' as type, id, registration_number, 
                    CONCAT(deceased_first_name, ' ', deceased_last_name) as name,
                    status, created_at, remarks
                    FROM death_registration 
                    WHERE $death_where_clause 
                    ORDER BY created_at DESC";
    $death_result = $conn->query($death_query);
    while ($row = $death_result->fetch_assoc()) {
        $applications[] = $row;
        $total_count++;
    }
}

// Sort all applications by date
usort($applications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Citizen Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-file-alt"></i> My Applications</h1>
                <p>Track and manage your birth and death registration applications</p>
            </div>

            <!-- Application Statistics -->
            <div class="stats-grid">
                <?php
                $stats = getUserStatistics($user_id, $conn);
                ?>
                <div class="stat-card">
                    <i class="fas fa-baby"></i>
                    <div class="stat-content">
                        <h3><?php echo $stats['birth']['total'] ?? 0; ?></h3>
                        <p>Birth Applications</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-cross"></i>
                    <div class="stat-content">
                        <h3><?php echo $stats['death']['total'] ?? 0; ?></h3>
                        <p>Death Applications</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-content">
                        <h3><?php echo ($stats['birth']['approved'] ?? 0) + ($stats['death']['approved'] ?? 0); ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-content">
                        <h3><?php echo ($stats['birth']['pending'] ?? 0) + ($stats['death']['pending'] ?? 0); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Applications</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search by name or registration number" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <select name="type">
                                    <option value="">All Types</option>
                                    <option value="birth" <?php echo $type_filter == 'birth' ? 'selected' : ''; ?>>Birth Only</option>
                                    <option value="death" <?php echo $type_filter == 'death' ? 'selected' : ''; ?>>Death Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="status">
                                    <option value="all">All Status</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Verified" <?php echo $status_filter == 'Verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="my_applications.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Applications List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Applications List</h3>
                    <span class="badge"><?php echo $total_count; ?> Applications</span>
                </div>
                <div class="card-body">
                    <?php if ($total_count > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Reg. Number</th>
                                    <th>Name</th>
                                    <th>Submitted Date</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $app['type']; ?>">
                                            <i class="fas fa-<?php echo $app['type'] == 'birth' ? 'baby' : 'cross'; ?>"></i>
                                            <?php echo ucfirst($app['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['registration_number']); ?></td>
                                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                                    <td><?php echo formatDate($app['created_at']); ?></td>
                                    <td><?php echo getStatusBadge($app['status']); ?></td>
                                    <td>
                                        <?php if ($app['remarks']): ?>
                                            <span class="remarks" title="<?php echo htmlspecialchars($app['remarks']); ?>">
                                                <i class="fas fa-comment"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No remarks</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_application.php?type=<?php echo $app['type']; ?>&id=<?php echo $app['id']; ?>" 
                                               class="btn btn-view btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <?php if (canDownloadCertificate($app['status'])): ?>
                                                <a href="../certificates/<?php echo $app['type']; ?>_certificate.php?id=<?php echo $app['id']; ?>" 
                                                   class="btn btn-download btn-sm" target="_blank">
                                                    <i class="fas fa-download"></i> Certificate
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($app['status'] == 'Rejected'): ?>
                                                <a href="edit_application.php?type=<?php echo $app['type']; ?>&id=<?php echo $app['id']; ?>" 
                                                   class="btn btn-edit btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt fa-3x"></i>
                        <h4>No applications found</h4>
                        <p>You haven't submitted any applications yet.</p>
                        <div class="empty-state-actions">
                            <a href="birth_form.php" class="btn btn-primary">
                                <i class="fas fa-baby"></i> Register Birth
                            </a>
                            <a href="death_form.php" class="btn btn-primary">
                                <i class="fas fa-cross"></i> Register Death
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="pagination-info">
                        Showing <?php echo $total_count; ?> application(s)
                    </div>
                </div>
            </div>

            <!-- Status Guide -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Application Status Guide</h3>
                </div>
                <div class="card-body">
                    <div class="status-guide">
                        <div class="status-item">
                            <span class="status status-pending">Pending</span>
                            <p>Your application is under review by administrators.</p>
                        </div>
                        <div class="status-item">
                            <span class="status status-verified">Verified</span>
                            <p>Application details have been verified and are awaiting final approval.</p>
                        </div>
                        <div class="status-item">
                            <span class="status status-approved">Approved</span>
                            <p>Application approved. You can now download the official certificate.</p>
                        </div>
                        <div class="status-item">
                            <span class="status status-rejected">Rejected</span>
                            <p>Application rejected. Check remarks for details and edit your application.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>