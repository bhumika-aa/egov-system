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

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    switch ($action) {
        case 'activate':
            $conn->query("UPDATE users SET status = 'active' WHERE id = $id");
            $_SESSION['success'] = 'User activated successfully.';
            break;
            
        case 'deactivate':
            $conn->query("UPDATE users SET status = 'inactive' WHERE id = $id");
            $_SESSION['success'] = 'User deactivated successfully.';
            break;
            
        case 'delete':
            $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
            $_SESSION['success'] = 'User deleted successfully.';
            break;
            
        case 'make_admin':
            $conn->query("UPDATE users SET role = 'admin' WHERE id = $id");
            $_SESSION['success'] = 'User promoted to admin.';
            break;
    }
    
    redirect('manage_users.php');
}

// Get all users
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <p>Manage citizen accounts and administrators</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filters -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Users</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search by name, username or email" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <select name="role">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="citizen" <?php echo $role_filter == 'citizen' ? 'selected' : ''; ?>>Citizen</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="manage_users.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> User List</h3>
                    <span class="badge"><?php echo $users->num_rows; ?> Users</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['status'] == 'active'): ?>
                                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-warning btn-sm"
                                                   onclick="return confirm('Deactivate this user?')">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] != 'admin'): ?>
                                                <a href="?action=make_admin&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-info btn-sm"
                                                   onclick="return confirm('Promote this user to admin?')">
                                                    <i class="fas fa-user-shield"></i> Make Admin
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Delete this user permanently?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <?php if ($users->num_rows == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash fa-3x"></i>
                                            <h4>No users found</h4>
                                            <p>Try adjusting your search filters</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>