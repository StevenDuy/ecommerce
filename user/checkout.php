<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'Checkout - ECommerce';

try {
    $db = new Database();

    // Get cart items with product details
    $stmt = $db->prepare("
        SELECT 
            ci.id,
            ci.user_id,
            ci.product_id,
            ci.quantity,
            p.name as product_name,
            p.price,
            p.seller_id,
            (ci.quantity * p.price) as subtotal
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.user_id = ?
    ");
    $db->execute($stmt, [getUserId()]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) {
        header('Location: cart.php');
        exit();
    }

    // Get user addresses
    $stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $db->execute($stmt, [getUserId()]);
    $addresses = $stmt->fetchAll();

    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['subtotal'];
    }

    $tax = $subtotal * 0.1;
    $shipping = $subtotal > 50 ? 0 : 5;
    $total = $subtotal + $tax + $shipping;

} catch (Exception $e) {
    handleError($e->getMessage(), 'cart.php');
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

    if ($addressId === 0) {
        showNotification('error', 'Please select a shipping address.');
    } else {
        try {
            $db->beginTransaction();

            // Create order
            $stmt = $db->prepare("INSERT INTO orders (user_id, shipping_address_id, status, total_amount) VALUES (?, ?, 'pending', ?)");
            $db->execute($stmt, [getUserId(), $addressId, $total]);
            $orderId = $db->lastInsertId();

            // Add order items - using VALUES instead of SELECT
            foreach ($cartItems as $item) {
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price_at_purchase, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $db->execute($stmt, [
                    $orderId,
                    $item['product_id'],
                    $item['seller_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['subtotal']
                ]);

                // Update product stock
                $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $db->execute($stmt, [$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $db->execute($stmt, [getUserId()]);

            $db->commit();

            showNotification('success', 'Order placed successfully!');
            header('Location: orders.php?order=' . $orderId);
            exit();

        } catch (Exception $e) {
            $db->rollback();
            error_log("Order placement failed: " . $e->getMessage());
            showNotification('error', 'Order failed: ' . $e->getMessage());
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Checkout
                    </h4>
                </div>

                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Shipping Address -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>Shipping Address
                            </h5>

                            <?php if (empty($addresses)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                You need to add a shipping address first.
                                <a href="profile.php#addresses" class="alert-link">Add Address</a>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach ($addresses as $address): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card address-card">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="address_id"
                                                       value="<?php echo $address['id']; ?>"
                                                       id="address_<?php echo $address['id']; ?>"
                                                       <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                                                <label class="form-check-label w-100" for="address_<?php echo $address['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($address['label'] ?? 'Address'); ?></strong>
                                                    <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary ms-2">Default</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($address['street'] ?? ''); ?>
                                                        <br><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> <?php echo htmlspecialchars($address['postal_code'] ?? ''); ?>
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-center">
                                <a href="profile.php#addresses" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Add New Address
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-credit-card me-2"></i>Payment Method
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card payment-card">
                                        <div class="card-body text-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method"
                                                       value="cash_on_delivery" id="cod" checked>
                                                <label class="form-check-label w-100" for="cod">
                                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                                    <br><strong>Cash on Delivery</strong>
                                                    <br><small class="text-muted">Pay when you receive</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card payment-card">
                                        <div class="card-body text-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method"
                                                       value="online_payment" id="online" disabled>
                                                <label class="form-check-label w-100" for="online">
                                                    <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                                    <br><strong>Online Payment</strong>
                                                    <br><small class="text-muted">Coming Soon</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-sticky-note me-2"></i>Order Notes (Optional)
                            </h5>
                            <textarea class="form-control" name="order_notes" rows="3"
                                      placeholder="Special instructions for your order..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <?php if (!empty($addresses)): ?>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Place Order
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>

                <div class="card-body">
                    <!-- Order Items -->
                    <div class="order-items mb-3">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <small class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></small>
                                <br>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                            </div>
                            <div>
                                <small class="fw-bold"><?php echo formatPrice($item['subtotal']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Totals -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (10%):</span>
                        <span><?php echo formatPrice($tax); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <span>
                            <?php if ($shipping === 0): ?>
                                <span class="text-success">FREE</span>
                            <?php else: ?>
                                <?php echo formatPrice($shipping); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary h5"><?php echo formatPrice($total); ?></strong>
                    </div>

                    <!-- Security Info -->
                    <div class="alert alert-light small">
                        <i class="fas fa-shield-alt text-success me-1"></i>
                        Your order is secured with SSL encryption
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>