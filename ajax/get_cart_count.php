<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'user') {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $db = new Database();

    // Get cart count for current user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $db->execute($stmt, [getUserId()]);
    $result = $stmt->fetch();

    echo json_encode(['count' => $result['count']]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['count' => 0]);
}
?>