<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('admin');

$pageTitle = 'Manage Users - ECommerce';

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = sanitizeInput($_POST['role']);
        $status = sanitizeInput($_POST['status']);

        $errors = [];

        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $errors[] = 'All fields are required.';
        }

        if (!validateEmail($email)) {
            $errors[] = 'Valid email is required.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!in_array($role, ['user', 'seller', 'admin'])) {
            $errors[] = 'Invalid role selected.';
        }

        if (!in_array($status, ['active', 'inactive'])) {
            $errors[] = 'Invalid status selected.';
        }

        if (empty($errors)) {
            try {
                $db = new Database();

                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $db->execute($stmt, [$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already exists.';
                } else {
                    $hashedPassword = hashPassword($password);
                    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
                    $db->execute($stmt, [$name, $email, $hashedPassword, $role, $status]);

                    showNotification('success', 'User added successfully!');
                }
            } catch (Exception $e) {
                showNotification('error', 'Failed to add user.');
                error_log($e->getMessage());
            }
        }

        if (!empty($errors)) {
            showNotification('error', implode(' ', $errors));
        }
    }

    elseif (isset($_POST['update_user'])) {
        // Update user
        $userId = (int)$_POST['user_id'];
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $status = sanitizeInput($_POST['status']);

        try {
            $db = new Database();

            // Don't allow admin to change their own role or status
            if ($userId === getUserId() && (getUserRole() !== $role || $status !== 'active')) {
                showNotification('error', 'You cannot modify your own role or deactivate your account.');
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $db->execute($stmt, [$name, $email, $role, $status, $userId]);

                showNotification('success', 'User updated successfully!');
            }
        } catch (Exception $e) {
            showNotification('error', 'Failed to update user.');
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['delete_user'])) {
        // Delete user
        $userId = (int)$_POST['user_id'];

        try {
            $db = new Database();

            // Don't allow admin to delete themselves
            if ($userId === getUserId()) {
                showNotification('error', 'You cannot delete your own account.');
            } else {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $db->execute($stmt, [$userId]);

                showNotification('success', 'User deleted successfully!');
            }
        } catch (Exception $e) {
            showNotification('error', 'Failed to delete user.');
            error_log($e->getMessage());
        }
    }
}

// Get filters
$roleFilter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $db = new Database();

    // Build WHERE clause for filtering
    $whereConditions = [];
    $params = [];

    if (!empty($roleFilter)) {
        $whereConditions[] = "role = ?";
        $params[] = $roleFilter;
    }

    if (!empty($statusFilter)) {
        $whereConditions[] = "status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($searchFilter)) {
        $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get users
    $stmt = $db->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC");
    $db->execute($stmt, $params);
    $users = $stmt->fetchAll();

    // Get user statistics
    $stmt = $db->prepare("SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status");
    $db->execute($stmt);
    $userStats = [];
    while ($row = $stmt->fetch()) {
        $userStats[$row['role']][$row['status']] = $row['count'];
    }

} catch (Exception $e) {
    handleError($e->getMessage());
    $users = [];
    $userStats = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-users me-2"></i>Manage Users</h2>
            <p class="text-muted">Add, edit, and manage user accounts</p>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i>Add User
        </button>
    </div>

    <!-- User Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users text-primary fa-2x mb-2"></i>
                    <h4><?php echo count($users); ?></h4>
                    <small class="text-muted">Total Users</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user text-info fa-2x mb-2"></i>
                    <h4><?php echo array_sum($userStats['user'] ?? []); ?></h4>
                    <small class="text-muted">Customers</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-store text-success fa-2x mb-2"></i>
                    <h4><?php echo array_sum($userStats['seller'] ?? []); ?></h4>
                    <small class="text-muted">Sellers</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user-shield text-warning fa-2x mb-2"></i>
                    <h4><?php echo array_sum($userStats['admin'] ?? []); ?></h4>
                    <small class="text-muted">Admins</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search Users</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($searchFilter); ?>" 
                           placeholder="Search by name or email...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Customer</option>
                        <option value="seller" <?php echo $roleFilter === 'seller' ? 'selected' : ''; ?>>Seller</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Users (<?php echo count($users); ?>)
            </h5>
        </div>

        <div class="card-body">
            <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-5x text-muted mb-3"></i>
                <h4>No users found</h4>
                <p class="text-muted">No users match your current filters.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="/ecommerce/assets/images/default-avatar.png" 
                                         alt="Avatar" class="rounded-circle me-3" 
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['role'] === 'admin' ? 'danger' : 
                                         ($user['role'] === 'seller' ? 'success' : 'primary'); 
                                ?>">
                                    <i class="fas fa-<?php 
                                        echo $user['role'] === 'admin' ? 'user-shield' : 
                                             ($user['role'] === 'seller' ? 'store' : 'user'); 
                                    ?> me-1"></i>
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check' : 'times'; ?> me-1"></i>
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary view-user" 
                                            data-bs-toggle="modal" data-bs-target="#viewUserModal"
                                            data-user='<?php echo json_encode($user); ?>'>
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <button class="btn btn-sm btn-outline-success edit-user" 
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-user='<?php echo json_encode($user); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <?php if ($user['id'] !== getUserId()): ?>
                                    <button class="btn btn-sm btn-outline-danger delete-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                        <div class="invalid-feedback">Please enter the full name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="add_password" name="password" minlength="6" required>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_role" class="form-label">Role</label>
                        <select class="form-select" id="add_role" name="role" required>
                            <option value="">Select role...</option>
                            <option value="user">Customer</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_status" class="form-label">Status</label>
                        <select class="form-select" id="add_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" id="edit_user_id" name="user_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="user">Customer</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="/ecommerce/assets/images/default-avatar.png" 
                             alt="Avatar" class="rounded-circle mb-3" 
                             style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 id="view_user_name"></h5>
                        <p class="text-muted" id="view_user_email"></p>
                        <span id="view_user_role_badge"></span>
                        <span id="view_user_status_badge"></span>
                    </div>

                    <div class="col-md-8">
                        <h6>Account Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>User ID:</strong></td>
                                <td id="view_user_id"></td>
                            </tr>
                            <tr>
                                <td><strong>Full Name:</strong></td>
                                <td id="view_user_fullname"></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td id="view_user_email_detail"></td>
                            </tr>
                            <tr>
                                <td><strong>Role:</strong></td>
                                <td id="view_user_role"></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td id="view_user_status"></td>
                            </tr>
                            <tr>
                                <td><strong>Joined:</strong></td>
                                <td id="view_user_created"></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td id="view_user_updated"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Edit user modal
document.querySelectorAll('.edit-user').forEach(button => {
    button.addEventListener('click', function() {
        const user = JSON.parse(this.dataset.user);

        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
    });
});

// View user modal
document.querySelectorAll('.view-user').forEach(button => {
    button.addEventListener('click', function() {
        const user = JSON.parse(this.dataset.user);

        document.getElementById('view_user_id').textContent = '#' + user.id;
        document.getElementById('view_user_name').textContent = user.name;
        document.getElementById('view_user_email').textContent = user.email;
        document.getElementById('view_user_fullname').textContent = user.name;
        document.getElementById('view_user_email_detail').textContent = user.email;
        document.getElementById('view_user_role').textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
        document.getElementById('view_user_status').textContent = user.status.charAt(0).toUpperCase() + user.status.slice(1);
        document.getElementById('view_user_created').textContent = new Date(user.created_at).toLocaleDateString();
        document.getElementById('view_user_updated').textContent = new Date(user.updated_at).toLocaleDateString();

        // Role badge
        const roleBadge = document.getElementById('view_user_role_badge');
        const roleClass = user.role === 'admin' ? 'danger' : (user.role === 'seller' ? 'success' : 'primary');
        const roleIcon = user.role === 'admin' ? 'user-shield' : (user.role === 'seller' ? 'store' : 'user');
        roleBadge.className = `badge bg-${roleClass} me-2`;
        roleBadge.innerHTML = `<i class="fas fa-${roleIcon} me-1"></i>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}`;

        // Status badge
        const statusBadge = document.getElementById('view_user_status_badge');
        const statusClass = user.status === 'active' ? 'success' : 'secondary';
        const statusIcon = user.status === 'active' ? 'check' : 'times';
        statusBadge.className = `badge bg-${statusClass}`;
        statusBadge.innerHTML = `<i class="fas fa-${statusIcon} me-1"></i>${user.status.charAt(0).toUpperCase() + user.status.slice(1)}`;
    });
});

// Delete user
document.querySelectorAll('.delete-user').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const userName = this.dataset.userName;

        if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="delete_user" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>