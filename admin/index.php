<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('admin');

$pageTitle = 'Admin Dashboard - ECommerce';

try {
    $db = new Database();

    // Get total users
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
    $db->execute($stmt);
    $totalUsers = $stmt->fetch()['total'];

    // Get users by role
    $stmt = $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $db->execute($stmt);
    $usersByRole = [];
    while ($row = $stmt->fetch()) {
        $usersByRole[$row['role']] = $row['count'];
    }

    // Get total products
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products");
    $db->execute($stmt);
    $totalProducts = $stmt->fetch()['total'];

    // Get total categories
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM categories");
    $db->execute($stmt);
    $totalCategories = $stmt->fetch()['total'];

    // Get total orders
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
    $db->execute($stmt);
    $totalOrders = $stmt->fetch()['total'];

    // Get orders by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $db->execute($stmt);
    $ordersByStatus = [];
    while ($row = $stmt->fetch()) {
        $ordersByStatus[$row['status']] = $row['count'];
    }

    // Get total revenue
    $stmt = $db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
    $db->execute($stmt);
    $totalRevenue = $stmt->fetch()['total'] ?? 0;

    // Get recent users
    $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $db->execute($stmt);
    $recentUsers = $stmt->fetchAll();

    // Get recent orders
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC LIMIT 5");
    $db->execute($stmt);
    $recentOrders = $stmt->fetchAll();

    // Get top selling products
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.sold_count > 0 
                         ORDER BY p.sold_count DESC LIMIT 5");
    $db->execute($stmt);
    $topProducts = $stmt->fetchAll();

    // Get monthly revenue (last 6 months)
    $stmt = $db->prepare("SELECT 
                         DATE_FORMAT(created_at, '%Y-%m') as month,
                         SUM(total_amount) as revenue
                         FROM orders 
                         WHERE status = 'delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month DESC");
    $db->execute($stmt);
    $monthlyRevenue = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $totalUsers = $totalProducts = $totalCategories = $totalOrders = $totalRevenue = 0;
    $usersByRole = $ordersByStatus = [];
    $recentUsers = $recentOrders = $topProducts = $monthlyRevenue = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Admin Dashboard
                            </h2>
                            <p class="mb-0">Welcome back, <?php echo getUserName(); ?>! Here's what's happening in your store today.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4><?php echo $totalUsers; ?></h4>
                                    <small>Users</small>
                                </div>
                                <div class="col-4">
                                    <h4><?php echo $totalProducts; ?></h4>
                                    <small>Products</small>
                                </div>
                                <div class="col-4">
                                    <h4><?php echo $totalOrders; ?></h4>
                                    <small>Orders</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $totalUsers; ?></h3>
                    <p class="mb-0">Total Users</p>
                    <small>
                        <?php echo ($usersByRole['user'] ?? 0); ?> Customers | 
                        <?php echo ($usersByRole['seller'] ?? 0); ?> Sellers
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-box fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $totalProducts; ?></h3>
                    <p class="mb-0">Total Products</p>
                    <small><?php echo $totalCategories; ?> Categories</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $totalOrders; ?></h3>
                    <p class="mb-0">Total Orders</p>
                    <small>
                        <?php echo ($ordersByStatus['pending'] ?? 0); ?> Pending | 
                        <?php echo ($ordersByStatus['delivered'] ?? 0); ?> Delivered
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo formatPrice($totalRevenue); ?></h3>
                    <p class="mb-0">Total Revenue</p>
                    <small>From delivered orders</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Breakdown -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Order Status Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-warning"><?php echo ($ordersByStatus['pending'] ?? 0); ?></h4>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-info"><?php echo ($ordersByStatus['confirmed'] ?? 0); ?></h4>
                                <small class="text-muted">Confirmed</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-primary"><?php echo ($ordersByStatus['shipped'] ?? 0); ?></h4>
                                <small class="text-muted">Shipped</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-success"><?php echo ($ordersByStatus['delivered'] ?? 0); ?></h4>
                                <small class="text-muted">Delivered</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-danger"><?php echo ($ordersByStatus['cancelled'] ?? 0); ?></h4>
                                <small class="text-muted">Cancelled</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="status-stat">
                                <h4 class="text-dark"><?php echo $totalOrders; ?></h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="users.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="products.php" class="btn btn-outline-success">
                            <i class="fas fa-box me-2"></i>Manage Products
                        </a>
                        <a href="orders.php" class="btn btn-outline-info">
                            <i class="fas fa-receipt me-2"></i>View Orders
                        </a>
                        <a href="orders.php?status=pending" class="btn btn-outline-warning">
                            <i class="fas fa-clock me-2"></i>Pending Orders (<?php echo ($ordersByStatus['pending'] ?? 0); ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Users -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentUsers)): ?>
                    <p class="text-muted text-center">No users yet</p>
                    <?php else: ?>
                    <?php foreach ($recentUsers as $user): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                            <div>
                                <span class="badge bg-<?php echo $user['role'] === 'seller' ? 'success' : 'primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                                <small class="text-muted ms-2"><?php echo formatDate($user['created_at']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                    <p class="text-muted text-center">No orders yet</p>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-1">Order #<?php echo $order['id']; ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_name']); ?></small>
                            <div>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?php echo formatPrice($order['total_amount']); ?></div>
                            <small class="text-muted"><?php echo formatDate($order['created_at']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Products</h5>
                    <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($topProducts)): ?>
                    <p class="text-muted text-center">No sales yet</p>
                    <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo getImagePath($product['main_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-muted">by <?php echo htmlspecialchars($product['seller_name']); ?></small>
                            <div>
                                <span class="badge bg-success"><?php echo $product['sold_count']; ?> sold</span>
                                <span class="text-primary ms-2"><?php echo formatPrice($product['price']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue Chart -->
    <?php if (!empty($monthlyRevenue)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Revenue Trend (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                    <th>Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $previousRevenue = 0;
                                foreach (array_reverse($monthlyRevenue) as $index => $data): 
                                    $growth = $previousRevenue > 0 ? (($data['revenue'] - $previousRevenue) / $previousRevenue) * 100 : 0;
                                    $previousRevenue = $data['revenue'];
                                ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($data['month'] . '-01')); ?></td>
                                    <td><?php echo formatPrice($data['revenue']); ?></td>
                                    <td>
                                        <?php if ($index > 0): ?>
                                        <span class="badge bg-<?php echo $growth >= 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $growth >= 0 ? '+' : ''; ?><?php echo number_format($growth, 1); ?>%
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.status-stat h4 {
    margin-bottom: 0;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<?php require_once '../includes/footer.php'; ?>