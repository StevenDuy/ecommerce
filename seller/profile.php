<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('seller');

$pageTitle = 'G4F - Seller Profile';

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

    // Get seller profile
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $db->execute($stmt, [getUserId()]);
    $seller = $stmt->fetch();

    // Get seller statistics
    $stmt = $db->prepare("SELECT 
                         COUNT(*) as total_products,
                         SUM(sold_count) as total_sold,
                         COUNT(DISTINCT oi.order_id) as total_orders,
                         SUM(CASE WHEN o.status = 'delivered' THEN oi.total_price ELSE 0 END) as total_revenue,
                         SUM(CASE WHEN o.status = 'delivered' THEN (oi.price_at_purchase - p.cost_price) * oi.quantity ELSE 0 END) as total_profit
                         FROM products p 
                         LEFT JOIN order_items oi ON p.id = oi.product_id 
                         LEFT JOIN orders o ON oi.order_id = o.id 
                         WHERE p.seller_id = ?");
    $db->execute($stmt, [getUserId()]);
    $stats = $stmt->fetch();

    // Get recent activity
    $stmt = $db->prepare("SELECT 'product' as type, name as title, created_at as date FROM products WHERE seller_id = ?
                         UNION ALL
                         SELECT 'order' as type, CONCAT('Order #', o.id) as title, o.created_at as date 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         WHERE oi.seller_id = ?
                         ORDER BY date DESC LIMIT 10");
    $db->execute($stmt, [getUserId(), getUserId()]);
    $recentActivity = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $seller = [];
    $stats = ['total_products' => 0, 'total_sold' => 0, 'total_orders' => 0, 'total_revenue' => 0, 'total_profit' => 0];
    $recentActivity = [];
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
                             alt="Profile Picture" class="profile-avatar">
                    </div>
                    <h4><?php echo htmlspecialchars($seller['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($seller['email']); ?></p>
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-store me-1"></i>Seller Account
                    </span>
                    <hr>
                    <small class="text-muted">Member since <?php echo formatDate($seller['created_at']); ?></small>
                </div>
            </div>

            <!-- Business Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Business Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-primary"><?php echo $stats['total_products']; ?></h4>
                                <small class="text-muted">Products</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-success"><?php echo $stats['total_sold']; ?></h4>
                                <small class="text-muted">Items Sold</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-info"><?php echo $stats['total_orders']; ?></h4>
                                <small class="text-muted">Orders</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-warning"><?php echo formatPrice($stats['total_revenue']); ?></h4>
                                <small class="text-muted">Revenue</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-success"><?php echo formatPrice($stats['total_profit']); ?></h5>
                        <small class="text-muted">Total Profit</small>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Activity</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center">No recent activity</p>
                    <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item d-flex align-items-center mb-2">
                            <div class="activity-icon me-3">
                                <?php if ($activity['type'] === 'product'): ?>
                                <i class="fas fa-box text-primary"></i>
                                <?php else: ?>
                                <i class="fas fa-shopping-bag text-success"></i>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title small"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-date text-muted" style="font-size: 0.75rem;">
                                    <?php echo formatDateTime($activity['date']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#business-tab">
                                <i class="fas fa-store me-2"></i>Business Info
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
                            <h5 class="mb-4">Personal Information</h5>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($seller['name']); ?>" required>
                                            <div class="invalid-feedback">Please enter your full name.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($seller['email']); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Type</label>
                                            <input type="text" class="form-control" value="Seller Account" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Status</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo ucfirst($seller['status']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>

                        <!-- Business Info Tab -->
                        <div class="tab-pane fade" id="business-tab">
                            <h5 class="mb-4">Business Information</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="text-primary"><?php echo $stats['total_products']; ?></h3>
                                            <p class="mb-0">Active Products</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="text-success"><?php echo $stats['total_sold']; ?></h3>
                                            <p class="mb-0">Total Sales</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total Revenue</h6>
                                    <h4 class="text-success"><?php echo formatPrice($stats['total_revenue']); ?></h4>
                                </div>
                                <div class="col-md-6">
                                    <h6>Total Profit</h6>
                                    <h4 class="text-primary"><?php echo formatPrice($stats['total_profit']); ?></h4>
                                </div>
                            </div>

                            <hr>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Business Tips:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Keep your product descriptions detailed and accurate</li>
                                    <li>Upload high-quality product images</li>
                                    <li>Respond to orders promptly to maintain good customer relations</li>
                                    <li>Monitor your inventory levels regularly</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security-tab">
                            <h5 class="mb-4">Security Settings</h5>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                    <div class="form-text">Required only if changing password</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                            <div class="invalid-feedback">Passwords must match.</div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>

                            <hr>

                            <div class="security-info">
                                <h6>Account Security</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>Email verified</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>
                                            <span>Account secured</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <span>Last login: <?php echo formatDateTime($seller['updated_at']); ?></span>
                                        </div>
                                    </div>
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