<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Please login to remove items from cart.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;

if ($cartItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item.']);
    exit();
}

try {
    $db = new Database();

    // Verify cart item belongs to user
    $stmt = $db->prepare("SELECT id FROM cart_items WHERE id = ? AND user_id = ?");
    $db->execute($stmt, [$cartItemId, getUserId()]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
        exit();
    }

    // Remove cart item
    $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ?");
    $db->execute($stmt, [$cartItemId]);

    // Get updated cart total
    $stmt = $db->prepare("SELECT SUM(p.price * ci.quantity) as total 
                         FROM cart_items ci 
                         JOIN products p ON ci.product_id = p.id 
                         WHERE ci.user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $cartTotal = $stmt->fetch()['total'] ?? 0;

    // Get updated cart count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $cartCount = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart successfully!',
        'cart_total' => $cartTotal,
        'cart_count' => $cartCount
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>