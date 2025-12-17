<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check authentication and admin role
Auth::checkSessionTimeout();
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    redirect('../login.php');
}

$conn = getConnection();

// Get report parameters
$report_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'monthly';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Generate reports
$reports = [];

// Monthly statistics
if ($report_type == 'monthly') {
    // Birth registrations by month
    $result = $conn->query("
        SELECT MONTH(created_at) as month, 
               COUNT(*) as count,
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM birth_registration 
        WHERE YEAR(created_at) = $year
        GROUP BY MONTH(created_at)
        ORDER BY month
    ");
    $reports['birth_monthly'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Death registrations by month
    $result = $conn->query("
        SELECT MONTH(created_at) as month, 
               COUNT(*) as count,
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM death_registration 
        WHERE YEAR(created_at) = $year
        GROUP BY MONTH(created_at)
        ORDER BY month
    ");
    $reports['death_monthly'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Yearly statistics
if ($report_type == 'yearly') {
    // Birth registrations by year
    $result = $conn->query("
        SELECT YEAR(created_at) as year, 
               COUNT(*) as count,
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM birth_registration 
        GROUP BY YEAR(created_at)
        ORDER BY year DESC
        LIMIT 5
    ");
    $reports['birth_yearly'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Death registrations by year
    $result = $conn->query("
        SELECT YEAR(created_at) as year, 
               COUNT(*) as count,
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM death_registration 
        GROUP BY YEAR(created_at)
        ORDER BY year DESC
        LIMIT 5
    ");
    $reports['death_yearly'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Summary statistics
$summary = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_births' => $conn->query("SELECT COUNT(*) as count FROM birth_registration")->fetch_assoc()['count'],
    'total_deaths' => $conn->query("SELECT COUNT(*) as count FROM death_registration")->fetch_assoc()['count'],
    'pending_applications' => $conn->query("SELECT COUNT(*) as count FROM (
        SELECT id FROM birth_registration WHERE status = 'Pending' 
        UNION ALL 
        SELECT id FROM death_registration WHERE status = 'Pending'
    ) as apps")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-chart-bar"></i> System Reports</h1>
                <p>Analytics and statistics for birth and death registrations</p>
            </div>

            <!-- Report Filters -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Report Filters</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Report Type</label>
                                <select name="type" onchange="this.form.submit()">
                                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                                    <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
                                    <option value="custom">Custom Report</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month">
                                    <?php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    foreach ($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo $month == $num ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-bar"></i> Generate Report
                                </button>
                                <button type="button" onclick="printReport()" class="btn btn-secondary">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                                <button type="button" onclick="exportToExcel()" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-content">
                        <h3><?php echo $summary['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-baby"></i>
                    <div class="stat-content">
                        <h3><?php echo $summary['total_births']; ?></h3>
                        <p>Birth Registrations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-cross"></i>
                    <div class="stat-content">
                        <h3><?php echo $summary['total_deaths']; ?></h3>
                        <p>Death Registrations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-content">
                        <h3><?php echo $summary['pending_applications']; ?></h3>
                        <p>Pending Applications</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Birth Registrations (<?php echo $year; ?>)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="birthChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Death Registrations (<?php echo $year; ?>)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="deathChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Reports -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Detailed Report</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total</th>
                                    <th>Approved</th>
                                    <th>Pending</th>
                                    <th>Rejected</th>
                                    <th>Approval Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                for ($m = 1; $m <= 12; $m++):
                                    $birth_data = array_filter($reports['birth_monthly'], function($item) use ($m) {
                                        return $item['month'] == $m;
                                    });
                                    $birth_data = !empty($birth_data) ? array_values($birth_data)[0] : ['count' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
                                    
                                    $death_data = array_filter($reports['death_monthly'], function($item) use ($m) {
                                        return $item['month'] == $m;
                                    });
                                    $death_data = !empty($death_data) ? array_values($death_data)[0] : ['count' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
                                    
                                    $total = $birth_data['count'] + $death_data['count'];
                                    $approved = $birth_data['approved'] + $death_data['approved'];
                                    $approval_rate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo $months[$m-1]; ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><?php echo $approved; ?></td>
                                    <td><?php echo $birth_data['pending'] + $death_data['pending']; ?></td>
                                    <td><?php echo $birth_data['rejected'] + $death_data['rejected']; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo $approval_rate; ?>%">
                                                <?php echo $approval_rate; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Birth Chart
    const birthCtx = document.getElementById('birthChart').getContext('2d');
    const birthChart = new Chart(birthCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Birth Registrations',
                data: [
                    <?php 
                    $birth_data = array_column($reports['birth_monthly'], 'count');
                    for ($m = 1; $m <= 12; $m++) {
                        $value = 0;
                        foreach ($reports['birth_monthly'] as $item) {
                            if ($item['month'] == $m) {
                                $value = $item['count'];
                                break;
                            }
                        }
                        echo $value . ($m < 12 ? ',' : '');
                    }
                    ?>
                ],
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Death Chart
    const deathCtx = document.getElementById('deathChart').getContext('2d');
    const deathChart = new Chart(deathCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Death Registrations',
                data: [
                    <?php 
                    for ($m = 1; $m <= 12; $m++) {
                        $value = 0;
                        foreach ($reports['death_monthly'] as $item) {
                            if ($item['month'] == $m) {
                                $value = $item['count'];
                                break;
                            }
                        }
                        echo $value . ($m < 12 ? ',' : '');
                    }
                    ?>
                ],
                backgroundColor: '#e74c3c',
                borderColor: '#c0392b',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    function printReport() {
        window.print();
    }

    function exportToExcel() {
        // This would typically be handled server-side
        window.location.href = 'export_report.php?type=<?php echo $report_type; ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>';
    }
    </script>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>