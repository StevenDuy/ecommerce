<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('seller');

$pageTitle = 'G4F - Seller Dashboard';

try {
    $db = new Database();

    // Get seller statistics
    $sellerId = getUserId();

    // Total products
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ?");
    $db->execute($stmt, [$sellerId]);
    $totalProducts = $stmt->fetch()['total'];

    // Total orders
    $stmt = $db->prepare("SELECT COUNT(DISTINCT o.id) as total FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         WHERE oi.seller_id = ?");
    $db->execute($stmt, [$sellerId]);
    $totalOrders = $stmt->fetch()['total'];

    // Total revenue
    $stmt = $db->prepare("SELECT SUM(oi.total_price) as total FROM order_items oi 
                         JOIN orders o ON oi.order_id = o.id 
                         WHERE oi.seller_id = ? AND o.status = 'delivered'");
    $db->execute($stmt, [$sellerId]);
    $totalRevenue = $stmt->fetch()['total'] ?? 0;

    // Total profit (revenue - cost)
    $stmt = $db->prepare("SELECT SUM((oi.price_at_purchase - p.cost_price) * oi.quantity) as total 
                         FROM order_items oi 
                         JOIN orders o ON oi.order_id = o.id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.seller_id = ? AND o.status = 'delivered'");
    $db->execute($stmt, [$sellerId]);
    $totalProfit = $stmt->fetch()['total'] ?? 0;

    // Products sold
    $stmt = $db->prepare("SELECT SUM(sold_count) as total FROM products WHERE seller_id = ?");
    $db->execute($stmt, [$sellerId]);
    $productsSold = $stmt->fetch()['total'] ?? 0;

    // Low stock products
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND stock_quantity <= 5 AND stock_quantity > 0");
    $db->execute($stmt, [$sellerId]);
    $lowStockProducts = $stmt->fetch()['total'];

    // Recent orders
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, 
                         COUNT(oi.id) as item_count,
                         SUM(oi.total_price) as order_total
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         JOIN order_items oi ON o.id = oi.order_id 
                         WHERE oi.seller_id = ? 
                         GROUP BY o.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5");
    $db->execute($stmt, [$sellerId]);
    $recentOrders = $stmt->fetchAll();

    // Top selling products
    $stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND sold_count > 0 ORDER BY sold_count DESC LIMIT 5");
    $db->execute($stmt, [$sellerId]);
    $topProducts = $stmt->fetchAll();

    // Categories count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM categories WHERE created_by = ?");
    $db->execute($stmt, [$sellerId]);
    $totalCategories = $stmt->fetch()['total'];

} catch (Exception $e) {
    handleError($e->getMessage());
    $totalProducts = $totalOrders = $totalRevenue = $totalProfit = $productsSold = $lowStockProducts = $totalCategories = 0;
    $recentOrders = $topProducts = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-store me-2"></i>
                                Welcome back, <?php echo getUserName(); ?>!
                            </h2>
                            <p class="mb-0">Manage your products, orders, and grow your business.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="products.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-box fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $totalProducts; ?></h3>
                    <p class="mb-0">Total Products</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-shopping-bag fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $productsSold; ?></h3>
                    <p class="mb-0">Products Sold</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-receipt fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo $totalOrders; ?></h3>
                    <p class="mb-0">Total Orders</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-center text-white">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h3 class="stats-number"><?php echo formatPrice($totalRevenue); ?></h3>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line text-success fa-2x mb-2"></i>
                    <h4 class="text-success"><?php echo formatPrice($totalProfit); ?></h4>
                    <p class="text-muted mb-0">Total Profit</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-tags text-info fa-2x mb-2"></i>
                    <h4 class="text-info"><?php echo $totalCategories; ?></h4>
                    <p class="text-muted mb-0">Categories</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                    <h4 class="text-warning"><?php echo $lowStockProducts; ?></h4>
                    <p class="text-muted mb-0">Low Stock Alert</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Orders
                    </h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>

                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h6>No orders yet</h6>
                        <p class="text-muted">Orders will appear here when customers purchase your products.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="orders.php?order=<?php echo $order['id']; ?>" class="text-decoration-none">
                                            #<?php echo $order['id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo $order['item_count']; ?> items</td>
                                    <td><?php echo formatPrice($order['order_total']); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($order['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>Top Products
                    </h5>
                    <a href="products.php" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>

                <div class="card-body">
                    <?php if (empty($topProducts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h6>No sales yet</h6>
                        <p class="text-muted small">Your best selling products will appear here.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo getImagePath($product['main_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 small">
                                <a href="products.php?edit=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars(substr($product['name'], 0, 30)) . (strlen($product['name']) > 30 ? '...' : ''); ?>
                                </a>
                            </h6>
                            <div class="d-flex justify-content-between">
                                <small class="text-success"><?php echo $product['sold_count']; ?> sold</small>
                                <small class="text-primary"><?php echo formatPrice($product['price']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="products.php?action=add" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-plus fa-2x mb-2"></i>
                                <br>Add New Product
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="orders.php?status=pending" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <br>Pending Orders
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="products.php?low_stock=1" class="btn btn-outline-danger w-100 py-3">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <br>Low Stock Items
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="profile.php" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-user-cog fa-2x mb-2"></i>
                                <br>Account Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>