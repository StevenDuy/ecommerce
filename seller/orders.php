<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('seller');

$pageTitle = 'G4F - Manage Orders';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitizeInput($_POST['status']);

    $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    if (in_array($newStatus, $validStatuses)) {
        try {
            $db = new Database();

            // Verify order contains seller's products
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND seller_id = ?");
            $db->execute($stmt, [$orderId, getUserId()]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $db->execute($stmt, [$newStatus, $orderId]);

                showNotification('success', 'Order status updated successfully!');
            } else {
                showNotification('error', 'Order not found or access denied.');
            }
        } catch (Exception $e) {
            showNotification('error', 'Failed to update order status.');
            error_log($e->getMessage());
        }
    } else {
        showNotification('error', 'Invalid status selected.');
    }
}

// Get filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $db = new Database();

    // Get orders containing seller's products
    $whereConditions = ["oi.seller_id = ?"];
    $params = [getUserId()];

    if (!empty($statusFilter)) {
        $whereConditions[] = "o.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($searchFilter)) {
        $whereConditions[] = "(u.name LIKE ? OR o.id LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }

    $whereClause = implode(' AND ', $whereConditions);

    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email,
                         COUNT(oi.id) as item_count,
                         SUM(oi.total_price) as seller_total
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         JOIN order_items oi ON o.id = oi.order_id 
                         WHERE $whereClause 
                         GROUP BY o.id 
                         ORDER BY o.created_at DESC");
    $db->execute($stmt, $params);
    $orders = $stmt->fetchAll();

    // Get order statistics
    $stmt = $db->prepare("SELECT 
                         COUNT(DISTINCT o.id) as total_orders,
                         COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
                         COUNT(DISTINCT CASE WHEN o.status = 'confirmed' THEN o.id END) as confirmed_orders,
                         COUNT(DISTINCT CASE WHEN o.status = 'shipped' THEN o.id END) as shipped_orders,
                         COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
                         COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         WHERE oi.seller_id = ?");
    $db->execute($stmt, [getUserId()]);
    $stats = $stmt->fetch();

} catch (Exception $e) {
    handleError($e->getMessage());
    $orders = [];
    $stats = ['total_orders' => 0, 'pending_orders' => 0, 'confirmed_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'cancelled_orders' => 0];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-receipt me-2"></i>Manage Orders</h2>
            <p class="text-muted">Process and track orders for your products</p>
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
                <div class="col-md-4">
                    <label class="form-label">Search Orders</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($searchFilter); ?>" 
                           placeholder="Search by order ID or customer name...">
                </div>

                <div class="col-md-4">
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

                <div class="col-md-4 d-flex align-items-end">
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

    <!-- Orders List -->
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
                <p class="text-muted">Orders for your products will appear here when customers make purchases.</p>
            </div>
            <?php else: ?>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Your Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $order['id']; ?></strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </div>
                            </td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td><strong><?php echo formatPrice($order['seller_total']); ?></strong></td>
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

                                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                                    <button class="btn btn-sm btn-outline-success update-status" 
                                            data-order-id="<?php echo $order['id']; ?>"
                                            data-current-status="<?php echo $order['status']; ?>">
                                        <i class="fas fa-edit"></i>
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

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>Order Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Order Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="statusOrderId">

                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <input type="text" class="form-control" id="currentStatus" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select" name="status" id="newStatus" required>
                            <option value="">Select new status...</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Status Guidelines:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Confirmed:</strong> Order accepted and being prepared</li>
                            <li><strong>Shipped:</strong> Order has been dispatched</li>
                            <li><strong>Delivered:</strong> Order reached customer</li>
                            <li><strong>Cancelled:</strong> Order cancelled (stock will be restored)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle - cần thiết cho các modal và dropdown trong trang này -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery - cần thiết cho AJAX trong trang này -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // View order details
    $('.view-order').on('click', function() {
        const orderId = $(this).data('order-id');

        $('#orderDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();

        $.get('../ajax/get_seller_order_details.php', { id: orderId }, function(data) {
            $('#orderDetailsContent').html(data);
        }).fail(function() {
            $('#orderDetailsContent').html('<div class="alert alert-danger">Failed to load order details.</div>');
        });
    });

    // Update order status
    $('.update-status').on('click', function() {
        const orderId = $(this).data('order-id');
        const currentStatus = $(this).data('current-status');

        $('#statusOrderId').val(orderId);
        $('#currentStatus').val(currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1));
        $('#newStatus').val('');

        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>