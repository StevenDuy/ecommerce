<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('admin');

$pageTitle = 'Manage Orders - ECommerce';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitizeInput($_POST['status']);

    $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    if (in_array($newStatus, $validStatuses)) {
        try {
            $db = new Database();
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $db->execute($stmt, [$newStatus, $orderId]);

            showNotification('success', 'Order status updated successfully!');
        } catch (Exception $e) {
            showNotification('error', 'Failed to update order status.');
            error_log($e->getMessage());
        }
    } else {
        showNotification('error', 'Invalid status selected.');
    }
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)$_POST['order_id'];

    try {
        $db = new Database();
        $db->beginTransaction();

        // Get order items to restore stock
        $stmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $db->execute($stmt, [$orderId]);
        $orderItems = $stmt->fetchAll();

        // Restore stock for each product
        foreach ($orderItems as $item) {
            $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $db->execute($stmt, [$item['quantity'], $item['product_id']]);
        }

        // Delete order (order_items will be deleted by CASCADE)
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $db->execute($stmt, [$orderId]);

        $db->commit();
        showNotification('success', 'Order deleted successfully!');
    } catch (Exception $e) {
        $db->rollback();
        showNotification('error', 'Failed to delete order.');
        error_log($e->getMessage());
    }
}

// Get filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$customerFilter = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$dateFilter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

try {
    $db = new Database();

    // Build WHERE clause for filtering
    $whereConditions = [];
    $params = [];

    if (!empty($statusFilter)) {
        $whereConditions[] = "o.status = ?";
        $params[] = $statusFilter;
    }

    if ($customerFilter > 0) {
        $whereConditions[] = "o.user_id = ?";
        $params[] = $customerFilter;
    }

    if (!empty($searchFilter)) {
        $whereConditions[] = "(u.name LIKE ? OR o.id LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }

    if (!empty($dateFilter)) {
        switch ($dateFilter) {
            case 'today':
                $whereConditions[] = "DATE(o.created_at) = CURDATE()";
                break;
            case 'week':
                $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get orders
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email,
                         COUNT(oi.id) as item_count
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         LEFT JOIN order_items oi ON o.id = oi.order_id 
                         $whereClause 
                         GROUP BY o.id 
                         ORDER BY o.created_at DESC");
    $db->execute($stmt, $params);
    $orders = $stmt->fetchAll();

    // Get all customers for filter
    $stmt = $db->prepare("SELECT DISTINCT u.id, u.name FROM users u 
                         JOIN orders o ON u.id = o.user_id 
                         ORDER BY u.name");
    $db->execute($stmt);
    $customers = $stmt->fetchAll();

    // Get order statistics
    $stmt = $db->prepare("SELECT 
                         COUNT(*) as total_orders,
                         COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                         COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_orders,
                         COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                         COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                         COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                         SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as total_revenue
                         FROM orders");
    $db->execute($stmt);
    $stats = $stmt->fetch();

} catch (Exception $e) {
    handleError($e->getMessage());
    $orders = $customers = [];
    $stats = ['total_orders' => 0, 'pending_orders' => 0, 'confirmed_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'cancelled_orders' => 0, 'total_revenue' => 0];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-receipt me-2"></i>Manage Orders</h2>
            <p class="text-muted">View and manage all orders in the system</p>
        </div>
        <div class="text-end">
            <div class="h4 text-success mb-0"><?php echo formatPrice($stats['total_revenue']); ?></div>
            <small class="text-muted">Total Revenue</small>
        </div>
    </div>

    <!-- Order Statistics -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-shopping-bag text-primary fa-2x mb-2"></i>
                    <h4><?php echo $stats['total_orders']; ?></h4>
                    <small class="text-muted">Total Orders</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                    <h4><?php echo $stats['pending_orders']; ?></h4>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check text-info fa-2x mb-2"></i>
                    <h4><?php echo $stats['confirmed_orders']; ?></h4>
                    <small class="text-muted">Confirmed</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-truck text-primary fa-2x mb-2"></i>
                    <h4><?php echo $stats['shipped_orders']; ?></h4>
                    <small class="text-muted">Shipped</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h4><?php echo $stats['delivered_orders']; ?></h4>
                    <small class="text-muted">Delivered</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                    <h4><?php echo $stats['cancelled_orders']; ?></h4>
                    <small class="text-muted">Cancelled</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search Orders</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($searchFilter); ?>" 
                           placeholder="Search by order ID or customer...">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Customer</label>
                    <select class="form-select" name="customer">
                        <option value="0">All Customers</option>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" 
                                <?php echo $customerFilter === (int)$customer['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="date">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Orders (<?php echo count($orders); ?>)
            </h5>
        </div>

        <div class="card-body">
            <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-5x text-muted mb-3"></i>
                <h4>No orders found</h4>
                <p class="text-muted">No orders match your current filters.</p>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </div>
                            </td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                            <td>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($order['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary view-order" 
                                            data-order-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <button class="btn btn-sm btn-outline-success update-status" 
                                            data-order-id="<?php echo $order['id']; ?>" 
                                            data-current-status="<?php echo $order['status']; ?>"
                                            <?php echo $order['status'] === 'cancelled' ? 'disabled title="Cannot edit cancelled order"' : ''; ?>>
                                        <i class="fas fa-<?php echo $order['status'] === 'cancelled' ? 'ban' : 'edit'; ?>"></i>
                                    </button>

                                    <button class="btn btn-sm btn-outline-danger delete-order" 
                                            data-order-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="updateOrderId">

                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select class="form-select" name="status" id="updateStatus" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // View order details
    $('.view-order').on('click', function() {
        const orderId = $(this).data('order-id');

        $.get('../ajax/get_admin_order_details.php', {id: orderId}, function(data) {
            $('#orderDetailsContent').html(data);
            new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
        }).fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            $('#orderDetailsContent').html('<div class="alert alert-danger">Error loading order details:<br>' + xhr.responseText + '</div>');
            new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
        });
    });

    // Update order status
    $('.update-status').on('click', function() {
        // Check if button is disabled
        if ($(this).prop('disabled')) {
            return false;
        }

        const orderId = $(this).data('order-id');
        const currentStatus = $(this).data('current-status');

        $('#updateOrderId').val(orderId);
        $('#updateStatus').val(currentStatus);
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    });

    // Delete order
    $('.delete-order').on('click', function() {
        const orderId = $(this).data('order-id');

        if (confirm('Are you sure you want to delete this order?')) {
            const form = $('<form method="POST">' +
                          '<input type="hidden" name="delete_order" value="1">' +
                          '<input type="hidden" name="order_id" value="' + orderId + '">' +
                          '</form>');
            $('body').append(form);
            form.submit();
        }
    });
});
</script>