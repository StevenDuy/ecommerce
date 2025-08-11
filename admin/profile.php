<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('admin');

$pageTitle = 'Admin Profile - ECommerce';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }

    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Valid email is required.';
    }

    // Check if email is already taken by another user
    try {
        $db = new Database();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $db->execute($stmt, [$email, getUserId()]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already taken by another user.';
        }
    } catch (Exception $e) {
        $errors[] = 'Database error occurred.';
    }

    // Password validation if changing password
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required to change password.';
        } else {
            // Verify current password
            try {
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
                $db->execute($stmt, [getUserId()]);
                $user = $stmt->fetch();

                if (!verifyPassword($currentPassword, $user['password_hash'])) {
                    $errors[] = 'Current password is incorrect.';
                }
            } catch (Exception $e) {
                $errors[] = 'Error verifying current password.';
            }
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($newPassword)) {
                $hashedPassword = hashPassword($newPassword);
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
                $db->execute($stmt, [$name, $email, $hashedPassword, getUserId()]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $db->execute($stmt, [$name, $email, getUserId()]);
            }

            $_SESSION['user_name'] = $name;
            showNotification('success', 'Profile updated successfully!');
        } catch (Exception $e) {
            showNotification('error', 'Failed to update profile.');
            error_log($e->getMessage());
        }
    } else {
        showNotification('error', implode(' ', $errors));
    }
}

try {
    $db = new Database();

    // Get admin profile
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $db->execute($stmt, [getUserId()]);
    $admin = $stmt->fetch();

    // Get system statistics
    $stmt = $db->prepare("SELECT 
                         (SELECT COUNT(*) FROM users) as total_users,
                         (SELECT COUNT(*) FROM products) as total_products,
                         (SELECT COUNT(*) FROM orders) as total_orders,
                         (SELECT COUNT(*) FROM categories) as total_categories,
                         (SELECT SUM(total_amount) FROM orders WHERE status = 'delivered') as total_revenue,
                         (SELECT COUNT(*) FROM users WHERE role = 'seller') as total_sellers,
                         (SELECT COUNT(*) FROM users WHERE role = 'user') as total_customers,
                         (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders");
    $db->execute($stmt);
    $systemStats = $stmt->fetch();

    // Get recent admin activities (example - you can expand this)
    $recentActivities = [
        ['action' => 'System Login', 'date' => date('Y-m-d H:i:s'), 'icon' => 'fas fa-sign-in-alt', 'color' => 'success'],
        ['action' => 'Accessed User Management', 'date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'icon' => 'fas fa-users', 'color' => 'info'],
        ['action' => 'Reviewed Orders', 'date' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'icon' => 'fas fa-receipt', 'color' => 'primary'],
        ['action' => 'Updated Product Status', 'date' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'icon' => 'fas fa-box', 'color' => 'warning'],
        ['action' => 'Generated Reports', 'date' => date('Y-m-d H:i:s', strtotime('-4 hours')), 'icon' => 'fas fa-chart-bar', 'color' => 'secondary']
    ];

    // Get monthly statistics for chart
    $stmt = $db->prepare("SELECT 
                         DATE_FORMAT(created_at, '%Y-%m') as month,
                         COUNT(*) as order_count,
                         SUM(total_amount) as revenue
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month DESC");
    $db->execute($stmt);
    $monthlyStats = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $admin = [];
    $systemStats = ['total_users' => 0, 'total_products' => 0, 'total_orders' => 0, 'total_categories' => 0, 'total_revenue' => 0, 'total_sellers' => 0, 'total_customers' => 0, 'pending_orders' => 0];
    $recentActivities = [];
    $monthlyStats = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Profile</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-avatar-container mb-3">
                        <img src="/ecommerce/assets/images/default-avatar.png" 
                             alt="Admin Avatar" class="profile-avatar">
                    </div>
                    <h4><?php echo htmlspecialchars($admin['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></p>
                    <span class="badge bg-danger fs-6">
                        <i class="fas fa-user-shield me-1"></i>System Administrator
                    </span>
                    <hr>
                    <small class="text-muted">Admin since <?php echo formatDate($admin['created_at']); ?></small>
                </div>
            </div>

            <!-- System Overview -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>System Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-primary"><?php echo $systemStats['total_users']; ?></h4>
                                <small class="text-muted">Total Users</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-success"><?php echo $systemStats['total_products']; ?></h4>
                                <small class="text-muted">Products</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-info"><?php echo $systemStats['total_orders']; ?></h4>
                                <small class="text-muted">Orders</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-warning"><?php echo $systemStats['total_sellers']; ?></h4>
                                <small class="text-muted">Sellers</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-success"><?php echo formatPrice($systemStats['total_revenue'] ?? 0); ?></h5>
                        <small class="text-muted">Total Revenue</small>
                    </div>

                    <?php if ($systemStats['pending_orders'] > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?php echo $systemStats['pending_orders']; ?> orders need attention
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h6>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item d-flex align-items-center mb-3">
                            <div class="activity-icon me-3">
                                <i class="<?php echo $activity['icon']; ?> text-<?php echo $activity['color']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title small"><?php echo $activity['action']; ?></div>
                                <div class="activity-date text-muted" style="font-size: 0.75rem;">
                                    <?php echo formatDateTime($activity['date']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="col-lg-8">
            <!-- Profile Settings -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-tab">
                                <i class="fas fa-user me-2"></i>Profile Settings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#system-tab">
                                <i class="fas fa-cogs me-2"></i>System Info
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security-tab">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <!-- Profile Settings Tab -->
                        <div class="tab-pane fade show active" id="profile-tab">
                            <h5 class="mb-4">Administrator Information</h5>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                            <div class="invalid-feedback">Please enter your full name.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="System Administrator" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <input type="text" class="form-control" value="Active" readonly>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h6 class="mb-3">Change Password</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- System Info Tab -->
                        <div class="tab-pane fade" id="system-tab">
                            <h5 class="mb-4">System Information</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Platform Details</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>PHP Version:</strong></td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Server Software:</strong></td>
                                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>MySQL Version:</strong></td>
                                            <td>
                                                <?php
                                                try {
                                                    $stmt = $db->prepare("SELECT VERSION() as version");
                                                    $db->execute($stmt);
                                                    echo $stmt->fetch()['version'];
                                                } catch (Exception $e) {
                                                    echo 'Unknown';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Max Upload Size:</strong></td>
                                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6>Quick Stats</h6>
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h5 class="text-primary"><?php echo $systemStats['total_customers']; ?></h5>
                                                <small>Customers</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h5 class="text-success"><?php echo $systemStats['total_sellers']; ?></h5>
                                                <small>Sellers</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h5 class="text-info"><?php echo $systemStats['total_categories']; ?></h5>
                                                <small>Categories</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h5 class="text-warning"><?php echo $systemStats['pending_orders']; ?></h5>
                                                <small>Pending Orders</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Performance -->
                            <?php if (!empty($monthlyStats)): ?>
                            <hr>
                            <h6>Monthly Performance (Last 6 Months)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthlyStats as $stat): ?>
                                        <tr>
                                            <td><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></td>
                                            <td><?php echo $stat['order_count']; ?></td>
                                            <td><?php echo formatPrice($stat['revenue']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security-tab">
                            <h5 class="mb-4">Security Settings</h5>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Administrator Account Security</strong><br>
                                Your account has elevated privileges. Please ensure you follow security best practices.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Account Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Last Login:</strong></td>
                                            <td><?php echo formatDateTime($admin['updated_at']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Account Created:</strong></td>
                                            <td><?php echo formatDateTime($admin['created_at']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Password Last Changed:</strong></td>
                                            <td><?php echo formatDateTime($admin['updated_at']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Account Status:</strong></td>
                                            <td><span class="badge bg-success">Active</span></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6>Security Recommendations</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Use a strong, unique password
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Change password regularly
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Monitor system activities
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Keep system updated
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                            Enable two-factor authentication (Coming Soon)
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;

    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>