<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'My Profile - ECommerce';

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

// Handle address operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $recipientName = sanitizeInput($_POST['recipient_name']);
    $line1 = sanitizeInput($_POST['line1']);
    $line2 = sanitizeInput($_POST['line2']);
    $city = sanitizeInput($_POST['city']);
    $state = sanitizeInput($_POST['state']);
    $postalCode = sanitizeInput($_POST['postal_code']);
    $country = sanitizeInput($_POST['country']);
    $isDefault = isset($_POST['is_default']);

    try {
        $db = new Database();

        if ($isDefault) {
            // Remove default flag from other addresses
            $stmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $db->execute($stmt, [getUserId()]);
        }

        $stmt = $db->prepare("INSERT INTO addresses (user_id, recipient_name, line1, line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $db->execute($stmt, [getUserId(), $recipientName, $line1, $line2, $city, $state, $postalCode, $country, $isDefault]);

        showNotification('success', 'Address added successfully!');
    } catch (Exception $e) {
        showNotification('error', 'Failed to add address.');
        error_log($e->getMessage());
    }
}

// Handle delete address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $addressId = (int)$_POST['address_id'];

    try {
        $db = new Database();
        $stmt = $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $db->execute($stmt, [$addressId, getUserId()]);

        showNotification('success', 'Address deleted successfully!');
    } catch (Exception $e) {
        showNotification('error', 'Failed to delete address.');
        error_log($e->getMessage());
    }
}

try {
    $db = new Database();

    // Get user profile
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $db->execute($stmt, [getUserId()]);
    $user = $stmt->fetch();

    // Get user addresses
    $stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $db->execute($stmt, [getUserId()]);
    $addresses = $stmt->fetchAll();

    // Get user statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total_orders, 
                         SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as total_spent
                         FROM orders WHERE user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $stats = $stmt->fetch();

} catch (Exception $e) {
    handleError($e->getMessage());
    $user = $addresses = [];
    $stats = ['total_orders' => 0, 'total_spent' => 0];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">My Profile</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-avatar-container mb-3">
                        <img src="/ecommerce/assets/images/default-avatar.png" 
                             alt="Profile Picture" class="profile-avatar">
                    </div>
                    <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                </div>
            </div>

            <!-- Profile Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Account Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-primary"><?php echo $stats['total_orders']; ?></h4>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-success"><?php echo formatPrice($stats['total_spent'] ?? 0); ?></h4>
                                <small class="text-muted">Total Spent</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">Member since <?php echo formatDate($user['created_at']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="col-lg-9">
            <!-- Profile Settings Tabs -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-tab">
                                <i class="fas fa-user me-2"></i>Profile Settings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addresses-tab">
                                <i class="fas fa-map-marker-alt me-2"></i>Addresses
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
                            <h5 class="mb-4">Profile Information</h5>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            <div class="invalid-feedback">Please enter your full name.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Type</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Status</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user['status']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>

                        <!-- Addresses Tab -->
                        <div class="tab-pane fade" id="addresses-tab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Shipping Addresses</h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="fas fa-plus me-2"></i>Add Address
                                </button>
                            </div>

                            <?php if (empty($addresses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h6>No addresses added yet</h6>
                                <p class="text-muted">Add your first shipping address.</p>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach ($addresses as $address): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card address-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">
                                                        <?php echo htmlspecialchars($address['recipient_name']); ?>
                                                        <?php if ($address['is_default']): ?>
                                                        <span class="badge bg-primary ms-2">Default</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="card-text small text-muted">
                                                        <?php echo htmlspecialchars($address['line1']); ?><br>
                                                        <?php if ($address['line2']): ?>
                                                        <?php echo htmlspecialchars($address['line2']); ?><br>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($address['country']); ?>
                                                    </p>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editAddress(<?php echo $address['id']; ?>)">
                                                                <i class="fas fa-edit me-2"></i>Edit
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('Delete this address?')">
                                                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                                <button type="submit" name="delete_address" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security-tab">
                            <h5 class="mb-4">Change Password</h5>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                            <div class="form-text">Password must be at least 6 characters long.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>

                            <hr class="my-4">

                            <h6>Account Security</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-shield-alt text-success me-2"></i>
                                        <span>Password protected</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-info me-2"></i>
                                        <span>Last login: <?php echo formatDateTime($user['updated_at']); ?></span>
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

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipient_name" class="form-label">Recipient Name</label>
                        <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="line1" class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" id="line1" name="line1" required>
                    </div>

                    <div class="mb-3">
                        <label for="line2" class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control" id="line2" name="line2">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">
                            Set as default address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_address" class="btn btn-primary">Add Address</button>
                </div>
            </form>
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

// Auto-open specific tab based on URL hash
$(document).ready(function() {
    if (window.location.hash === '#addresses') {
        $('.nav-link[data-bs-target="#addresses-tab"]').tab('show');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>