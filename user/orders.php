<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'My Orders - ECommerce';

// Get order ID if specified
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $cancelOrderId = (int)$_POST['order_id'];

    try {
        $db = new Database();

        // Check if order belongs to user and is pending
        $stmt = $db->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
        $db->execute($stmt, [$cancelOrderId, getUserId()]);
        $order = $stmt->fetch();

        if ($order && $order['status'] === 'pending') {
            $db->beginTransaction();

            // Update order status
            $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $db->execute($stmt, [$cancelOrderId]);

            // Restore product stock and sold_count
            $stmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $db->execute($stmt, [$cancelOrderId]);
            $orderItems = $stmt->fetchAll();

            foreach ($orderItems as $item) {
                // Restore stock
                $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $db->execute($stmt, [$item['quantity'], $item['product_id']]);

                // Reduce sold_count
                $stmt = $db->prepare("UPDATE products SET sold_count = GREATEST(0, sold_count - ?) WHERE id = ?");
                $db->execute($stmt, [$item['quantity'], $item['product_id']]);
            }

            $db->commit();
            showNotification('success', 'Order cancelled successfully.');
        } else {
            showNotification('error', 'Cannot cancel this order.');
        }
    } catch (Exception $e) {
        $db->rollback();
        showNotification('error', 'Failed to cancel order.');
        error_log($e->getMessage());
    }
}

try {
    $db = new Database();

    // Get user orders
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $db->execute($stmt, [getUserId()]);
    $orders = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $orders = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>My Orders
                    </h4>
                    <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
                </div>

                <div class="card-body">
                    <?php if (empty($orders)): ?>
                    <!-- No Orders -->
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-5x text-muted mb-3"></i>
                        <h4>No orders yet</h4>
                        <p class="text-muted">Start shopping to see your orders here.</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                        </a>
                    </div>
                    <?php else: ?>

                    <!-- Orders List -->
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card mb-4 border rounded">
                        <div class="order-header bg-light p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>Order #<?php echo $order['id']; ?></strong>
                                    <br><small class="text-muted"><?php echo formatDateTime($order['created_at']); ?></small>
                                </div>
                                <div class="col-md-2">
                                    <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                </div>
                                <div class="col-md-2">
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-sm btn-outline-primary toggle-order-details" 
                                            data-order-id="<?php echo $order['id']; ?>"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#order-details-<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>

                                    <?php if ($order['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2 text-end">
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="order-details collapse" id="order-details-<?php echo $order['id']; ?>">
                            <div class="p-3">
                                <?php
                                try {
                                    // Get order items
                                    $stmt = $db->prepare("SELECT oi.*, p.name as product_name, p.main_image_url, u.name as seller_name 
                                                         FROM order_items oi 
                                                         JOIN products p ON oi.product_id = p.id 
                                                         JOIN users u ON oi.seller_id = u.id 
                                                         WHERE oi.order_id = ?");
                                    $db->execute($stmt, [$order['id']]);
                                    $orderItems = $stmt->fetchAll();

                                    // Get shipping address
                                    if ($order['shipping_address_id']) {
                                        $stmt = $db->prepare("SELECT * FROM addresses WHERE id = ?");
                                        $db->execute($stmt, [$order['shipping_address_id']]);
                                        $shippingAddress = $stmt->fetch();
                                    }
                                } catch (Exception $e) {
                                    $orderItems = [];
                                    $shippingAddress = null;
                                }
                                ?>

                                <!-- Order Items -->
                                <h6>Order Items</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Seller</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="/ecommerce/assets/images/products/<?php echo $item['main_image_url']; ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                             class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <a href="product_details.php?id=<?php echo $item['product_id']; ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                                <td><?php echo formatPrice($item['price_at_purchase']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo formatPrice($item['total_price']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Shipping Address -->
                                <?php if ($shippingAddress): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Shipping Address</h6>
                                        <address>
                                            <strong><?php echo htmlspecialchars($shippingAddress['recipient_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($shippingAddress['line1']); ?><br>
                                            <?php if ($shippingAddress['line2']): ?>
                                            <?php echo htmlspecialchars($shippingAddress['line2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($shippingAddress['city'] . ', ' . $shippingAddress['state'] . ' ' . $shippingAddress['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($shippingAddress['country']); ?>
                                        </address>
                                    </div>

                                    <div class="col-md-6">
                                        <h6>Order Status Timeline</h6>
                                        <div class="timeline">
                                            <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'confirmed', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                                                <i class="fas fa-clock"></i> Order Placed
                                                <small class="text-muted d-block"><?php echo formatDateTime($order['created_at']); ?></small>
                                            </div>

                                            <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                                                <i class="fas fa-check"></i> Order Confirmed
                                            </div>

                                            <div class="timeline-item <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
                                                <i class="fas fa-truck"></i> Order Shipped
                                            </div>

                                            <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'active' : ''; ?>">
                                                <i class="fas fa-home"></i> Order Delivered
                                            </div>

                                            <?php if ($order['status'] === 'cancelled'): ?>
                                            <div class="timeline-item cancelled">
                                                <i class="fas fa-times"></i> Order Cancelled
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Timeline -->
<style>
.timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    padding: 10px 0 10px 30px;
    position: relative;
    color: #999;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 18px;
    width: 2px;
    height: 100%;
    background-color: #e9ecef;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-item i {
    position: absolute;
    left: 5px;
    top: 15px;
    width: 12px;
    height: 12px;
    background-color: #e9ecef;
    border-radius: 50%;
    padding: 6px;
    color: white;
    font-size: 8px;
    text-align: center;
}

.timeline-item.active {
    color: #28a745;
}

.timeline-item.active i {
    background-color: #28a745;
}

.timeline-item.cancelled {
    color: #dc3545;
}

.timeline-item.cancelled i {
    background-color: #dc3545;
}

.order-card {
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.toggle-icon.rotated {
    transform: rotate(180deg);
}
</style>

<script>
$(document).ready(function() {
    // Toggle order details with Bootstrap collapse events
    $('.order-details').on('show.bs.collapse', function() {
        const orderId = $(this).attr('id').replace('order-details-', '');
        const toggleBtn = $('[data-order-id="' + orderId + '"]');
        const icon = toggleBtn.closest('.order-header').find('.toggle-icon');

        icon.addClass('rotated');
        toggleBtn.html('<i class="fas fa-eye-slash me-1"></i>Hide Details');
    });

    $('.order-details').on('hide.bs.collapse', function() {
        const orderId = $(this).attr('id').replace('order-details-', '');
        const toggleBtn = $('[data-order-id="' + orderId + '"]');
        const icon = toggleBtn.closest('.order-header').find('.toggle-icon');

        icon.removeClass('rotated');
        toggleBtn.html('<i class="fas fa-eye me-1"></i>View Details');
    });

    // Auto-expand if order ID in URL
    <?php if ($orderId > 0): ?>
    const orderToExpand = $('#order-details-<?php echo $orderId; ?>');
    if (orderToExpand.length) {
        orderToExpand.collapse('show');
        const toggleBtn = $('[data-order-id="<?php echo $orderId; ?>"]');
        const icon = toggleBtn.closest('.order-header').find('.toggle-icon');
        icon.addClass('rotated');
        toggleBtn.html('<i class="fas fa-eye-slash me-1"></i>Hide Details');

        // Scroll to order
        $('html, body').animate({
            scrollTop: orderToExpand.closest('.order-card').offset().top - 100
        }, 1000);
    }
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>