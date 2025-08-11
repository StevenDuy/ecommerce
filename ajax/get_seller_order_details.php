<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'session.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'utils.php';

requireRole('seller');

$orderId = (int)($_GET['id'] ?? 0);
$sellerId = getUserId();

if ($orderId <= 0) {
    echo '<div class="alert alert-danger">Invalid order ID</div>';
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Verify seller has items in this order
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND seller_id = ?");
    $stmt->execute([$orderId, $sellerId]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($access['count'] == 0) {
        echo '<div class="alert alert-danger">Access denied. This order does not contain your products.</div>';
        exit();
    }

    // Get order details with customer info and shipping address
    $stmt = $pdo->prepare("
        SELECT
            o.id, o.user_id, o.shipping_address_id, o.status, o.total_amount,
            o.created_at, o.updated_at,
            u.name as customer_name, u.email as customer_email,
            a.recipient_name, a.line1, a.line2, a.city, a.state, a.postal_code, a.country
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN addresses a ON o.shipping_address_id = a.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo '<div class="alert alert-danger">Order not found</div>';
        exit();
    }

    // Get only seller's items from this order
    $stmt = $pdo->prepare("
        SELECT
            oi.id, oi.product_id, oi.seller_id, oi.quantity,
            oi.price_at_purchase, oi.total_price,
            p.name as product_name, p.main_image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND oi.seller_id = ?
    ");
    $stmt->execute([$orderId, $sellerId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate seller's total from this order
    $sellerTotal = array_sum(array_column($orderItems, 'total_price'));

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <h6><strong>Order Information</strong></h6>
        <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
        <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>Status:</strong>
            <span class="badge bg-<?php
                echo $order['status'] === 'pending' ? 'warning' :
                    ($order['status'] === 'delivered' ? 'success' :
                    ($order['status'] === 'cancelled' ? 'danger' : 'info'));
            ?>">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </p>
        <p><strong>Order Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
        <p><strong>Your Items Total:</strong> <span class="text-success fw-bold">$<?php echo number_format($sellerTotal, 2); ?></span></p>

        <h6 class="mt-3"><strong>Customer Information</strong></h6>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
    </div>

    <div class="col-md-6">
        <h6><strong>Shipping Information</strong></h6>
        <div class="border p-3 rounded bg-light">
            <?php if ($order['recipient_name']): ?>
                <strong><?php echo htmlspecialchars($order['recipient_name']); ?></strong><br>
                <?php echo htmlspecialchars($order['line1']); ?><br>
                <?php if ($order['line2']): ?>
                    <?php echo htmlspecialchars($order['line2']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['postal_code']); ?><br>
                <?php echo htmlspecialchars($order['country']); ?>
            <?php else: ?>
                <em class="text-muted">Shipping address not available</em>
            <?php endif; ?>
        </div>

        <h6 class="mt-3"><strong>Order Status Timeline</strong></h6>
        <div class="border p-3 rounded bg-light">
            <div><strong>Order Placed</strong><br>
                <small><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></small>
            </div>
            <?php if ($order['status'] === 'confirmed'): ?>
                <div class="mt-2"><strong>✓ Order Confirmed</strong></div>
            <?php elseif ($order['status'] === 'shipped'): ?>
                <div class="mt-2"><strong>✓ Order Confirmed</strong></div>
                <div class="mt-2"><strong>✓ Order Shipped</strong></div>
            <?php elseif ($order['status'] === 'delivered'): ?>
                <div class="mt-2"><strong>✓ Order Confirmed</strong></div>
                <div class="mt-2"><strong>✓ Order Shipped</strong></div>
                <div class="mt-2"><strong>✓ Order Delivered</strong></div>
            <?php elseif ($order['status'] === 'cancelled'): ?>
                <div class="mt-2 text-danger"><strong>✗ Order Cancelled</strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<hr>

<h6><strong>Your Items in this Order</strong></h6>
<?php if (empty($orderItems)): ?>
    <div class="alert alert-info">No items found for this order.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>$<?php echo number_format($item['price_at_purchase'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-success">
            <tr>
                <td colspan="3" class="text-end"><strong>Your Total Earnings:</strong></td>
                <td><strong>$<?php echo number_format($sellerTotal, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>
