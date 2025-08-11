<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['products' => []]);
    exit();
}

$searchTerm = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if (strlen($searchTerm) < 2) {
    echo json_encode(['products' => []]);
    exit();
}

try {
    $db = new Database();

    // Search products
    $stmt = $db->prepare("SELECT p.id, p.name, p.price, p.main_image_url, u.name as seller_name 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE (p.name LIKE ? OR p.description LIKE ?) 
                         AND p.stock_quantity > 0 
                         ORDER BY p.name ASC 
                         LIMIT 10");
    $searchParam = "%$searchTerm%";
    $db->execute($stmt, [$searchParam, $searchParam]);
    $products = $stmt->fetchAll();

    echo json_encode(['products' => $products]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['products' => []]);
}
?>