<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$productId = (int)$_GET['product_id'];

try {
    $db = new Database();

    // Get gallery images for the product
    $stmt = $db->prepare("SELECT id, url, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $db->execute($stmt, [$productId]);
    $images = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'images' => $images
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading gallery images: ' . $e->getMessage()
    ]);
}
?>