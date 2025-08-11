<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    exit();
}

try {
    $db = new Database();

    // Check if product exists and has enough stock
    $stmt = $db->prepare("SELECT name, stock_quantity, price FROM products WHERE id = ? AND stock_quantity > 0");
    $db->execute($stmt, [$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or out of stock.']);
        exit();
    }

    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.']);
        exit();
    }

    // Check if item already exists in cart
    $stmt = $db->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $db->execute($stmt, [getUserId(), $productId]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;

        if ($newQuantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more items. Total would exceed available stock.']);
            exit();
        }

        // Update existing cart item
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        $db->execute($stmt, [$newQuantity, getUserId(), $productId]);
    } else {
        // Add new cart item
        $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $db->execute($stmt, [getUserId(), $productId, $quantity]);
    }

    // Get updated cart count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $cartCount = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true, 
        'message' => $product['name'] . ' added to cart successfully!',
        'cart_count' => $cartCount
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>