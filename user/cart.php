<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'Shopping Cart - ECommerce';

try {
    $db = new Database();

    // Get cart items for current user
    $stmt = $db->prepare("SELECT * FROM cart_details WHERE user_id = ? ORDER BY cart_item_id DESC");
    $db->execute($stmt, [getUserId()]);
    $cartItems = $stmt->fetchAll();

    // Calculate totals
    $subtotal = 0;
    $totalQuantity = 0;

    foreach ($cartItems as $item) {
        $subtotal += $item['subtotal'];
        $totalQuantity += $item['quantity'];
    }

    $tax = $subtotal * 0.1; // 10% tax
    $shipping = $subtotal > 50 ? 0 : 5; // Free shipping over $50
    $total = $subtotal + $tax + $shipping;

} catch (Exception $e) {
    handleError($e->getMessage());
    $cartItems = [];
    $subtotal = $tax = $shipping = $total = $totalQuantity = 0;
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Shopping Cart</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Shopping Cart (<?php echo count($cartItems); ?> items)
                    </h4>
                </div>

                <div class="card-body">
                    <?php if (empty($cartItems)): ?>
                    <!-- Empty Cart -->
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                        <h4>Your cart is empty</h4>
                        <p class="text-muted">Add some products to your cart to get started.</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- Cart Items -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/ecommerce/assets/images/products/<?php echo $item['main_image_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="product_details.php?id=<?php echo $item['product_id']; ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    Sold by <?php echo htmlspecialchars($item['seller_name']); ?>
                                                </small>
                                                <br>
                                                <small class="text-success">
                                                    <?php echo $item['stock_quantity']; ?> in stock
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo formatPrice($item['price']); ?></span>
                                    </td>
                                    <td>
                                        <div class="quantity-controls">
                                            <button type="button" class="btn btn-sm btn-outline-secondary quantity-minus">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm text-center update-cart-quantity" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                                   style="width: 70px;">
                                            <button type="button" class="btn btn-sm btn-outline-secondary quantity-plus">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold subtotal"><?php echo formatPrice($item['subtotal']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger remove-from-cart" 
                                                data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                                title="Remove from cart">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>

                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Update Cart
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cart Summary -->
        <?php if (!empty($cartItems)): ?>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?php echo $totalQuantity; ?> items):</span>
                        <span class="cart-total"><?php echo formatPrice($subtotal); ?></span>
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

                    <?php if ($shipping > 0): ?>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Free shipping on orders over $50!
                    </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary h5"><?php echo formatPrice($total); ?></strong>
                    </div>

                    <div class="d-grid">
                        <a href="checkout.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                        </a>
                    </div>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure checkout with SSL encryption
                        </small>
                    </div>
                </div>
            </div>

            <!-- Recommended Products -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">You might also like</h6>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        // Get recommended products (popular items)
                        $stmt = $db->prepare("SELECT p.*, u.name as seller_name 
                                             FROM products p 
                                             JOIN users u ON p.seller_id = u.id 
                                             WHERE p.stock_quantity > 0 
                                             ORDER BY p.sold_count DESC 
                                             LIMIT 3");
                        $db->execute($stmt);
                        $recommended = $stmt->fetchAll();

                        foreach ($recommended as $product): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 small">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($product['name'], 0, 30)) . '...'; ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary small fw-bold"><?php echo formatPrice($product['price']); ?></span>
                                    <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach;
                    } catch (Exception $e) {
                        // Ignore errors for recommendations
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update quantity controls for cart page
    $('.quantity-minus').on('click', function() {
        const input = $(this).siblings('.update-cart-quantity');
        const currentValue = parseInt(input.val());

        if (currentValue > 1) {
            input.val(currentValue - 1);
            input.trigger('change');
        }
    });

    $('.quantity-plus').on('click', function() {
        const input = $(this).siblings('.update-cart-quantity');
        const currentValue = parseInt(input.val());
        const maxValue = parseInt(input.attr('max'));

        if (currentValue < maxValue) {
            input.val(currentValue + 1);
            input.trigger('change');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>