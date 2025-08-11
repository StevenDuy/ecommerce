<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cartItemId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity.']);
    exit();
}

try {
    $db = new Database();

    // Verify cart item belongs to user and get product info
    $stmt = $db->prepare("SELECT ci.product_id, p.name, p.price, p.stock_quantity 
                         FROM cart_items ci 
                         JOIN products p ON ci.product_id = p.id 
                         WHERE ci.id = ? AND ci.user_id = ?");
    $db->execute($stmt, [$cartItemId, getUserId()]);
    $cartItem = $stmt->fetch();

    if (!$cartItem) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
        exit();
    }

    // Check if requested quantity is available
    if ($quantity > $cartItem['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $cartItem['stock_quantity'] . ' items left.']);
        exit();
    }

    // Update cart item quantity
    $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $db->execute($stmt, [$quantity, $cartItemId]);

    // Calculate new subtotal for this item
    $subtotal = $cartItem['price'] * $quantity;

    // Get updated cart total
    $stmt = $db->prepare("SELECT SUM(p.price * ci.quantity) as total 
                         FROM cart_items ci 
                         JOIN products p ON ci.product_id = p.id 
                         WHERE ci.user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $cartTotal = $stmt->fetch()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully!',
        'subtotal' => $subtotal,
        'cart_total' => $cartTotal
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>