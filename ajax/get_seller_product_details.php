<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'session.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'utils.php';

requireRole('seller');

$productId = (int)($_GET['id'] ?? 0);
$sellerId = getUserId();

if ($productId <= 0) {
    echo '<div class="alert alert-danger">Invalid product ID</div>';
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get product details (only if it belongs to this seller)
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.name, p.description, p.price, p.cost_price, 
            p.stock_quantity, p.sold_count, p.is_featured, p.main_image_url,
            p.created_at, p.updated_at,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.seller_id = ?
    ");
    $stmt->execute([$productId, $sellerId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo '<div class="alert alert-danger">Product not found or access denied</div>';
        exit();
    }

    // Get product images
    $stmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? ORDER BY id");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Calculate profit and revenue
    $profitPerUnit = $product['price'] - $product['cost_price'];
    $totalRevenue = $product['price'] * $product['sold_count'];
    $totalProfit = $profitPerUnit * $product['sold_count'];
    $profitMargin = $product['cost_price'] > 0 ? 
        (($product['price'] - $product['cost_price']) / $product['cost_price']) * 100 : 0;

    // Calculate inventory value
    $inventoryValue = $product['stock_quantity'] * $product['cost_price'];

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <!-- Product Images -->
        <div class="mb-4">
            <?php if ($product['main_image_url']): ?>
                <img src="/ecommerce/assets/images/products/<?php echo htmlspecialchars($product['main_image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="img-fluid rounded shadow-sm mb-3" 
                     style="max-height: 300px; width: 100%; object-fit: cover;">
            <?php endif; ?>

            <?php if (!empty($images)): ?>
                <div class="row g-2">
                    <?php foreach (array_slice($images, 0, 4) as $image): ?>
                        <div class="col-3">
                            <img src="/ecommerce/assets/images/products/<?php echo htmlspecialchars($image); ?>" 
                                 alt="Product image" class="img-fluid rounded" 
                                 style="height: 60px; width: 100%; object-fit: cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Performance Stats -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Your Performance</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h5 class="text-success mb-1">$<?php echo number_format($totalRevenue, 2); ?></h5>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h5 class="text-primary mb-1">$<?php echo number_format($totalProfit, 2); ?></h5>
                        <small class="text-muted">Total Profit</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-warning mb-1"><?php echo number_format($profitMargin, 1); ?>%</h6>
                            <small class="text-muted">Profit Margin</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-info mb-1">$<?php echo number_format($inventoryValue, 2); ?></h6>
                        <small class="text-muted">Inventory Value</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Product Information -->
        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
        <p class="text-muted mb-3">Your Product ID: #<?php echo $product['id']; ?></p>

        <!-- Status Badges -->
        <div class="mb-3">
            <?php if ($product['is_featured']): ?>
                <span class="badge bg-warning me-2"><i class="fas fa-star me-1"></i>Featured</span>
            <?php endif; ?>

            <?php if ($product['stock_quantity'] == 0): ?>
                <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Out of Stock</span>
            <?php elseif ($product['stock_quantity'] <= 5): ?>
                <span class="badge bg-warning"><i class="fas fa-exclamation me-1"></i>Low Stock</span>
            <?php else: ?>
                <span class="badge bg-success"><i class="fas fa-check me-1"></i>In Stock</span>
            <?php endif; ?>
        </div>

        <!-- Pricing & Profit -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Pricing & Profit</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center p-2 border rounded">
                            <strong>Selling Price</strong><br>
                            <span class="h5 text-success">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 border rounded">
                            <strong>Your Cost</strong><br>
                            <span class="h6 text-muted">$<?php echo number_format($product['cost_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-center p-2 bg-success text-white rounded">
                    <strong>Profit per sale: $<?php echo number_format($profitPerUnit, 2); ?></strong>
                </div>
            </div>
        </div>

        <!-- Stock & Sales -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="fas fa-boxes me-2"></i>Inventory & Sales</h6>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><strong>In Stock:</strong> 
                            <span class="badge bg-<?php echo $product['stock_quantity'] > 5 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                <?php echo $product['stock_quantity']; ?> units
                            </span>
                        </p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><strong>Total Sold:</strong> 
                            <span class="badge bg-info"><?php echo $product['sold_count']; ?> units</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-info-circle me-2"></i>Product Details</h6>
                <p class="mb-1"><strong>Category:</strong> <?php echo $product['category_name'] ? htmlspecialchars($product['category_name']) : 'Uncategorized'; ?></p>
                <p class="mb-1"><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($product['created_at'])); ?></p>
                <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($product['updated_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($product['description']): ?>
<hr>
<div>
    <h6><i class="fas fa-align-left me-2"></i>Product Description</h6>
    <div class="border rounded p-3 bg-light">
        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<hr>
<div class="text-center">
    <button class="btn btn-primary me-2" onclick="$('#viewProductModal').modal('hide'); $('.edit-product[data-product*='&quot;id&quot;:<?php echo $product['id']; ?>']').click();">
        <i class="fas fa-edit me-2"></i>Edit Product
    </button>
    <a href="../user/product_details.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-outline-info">
        <i class="fas fa-external-link-alt me-2"></i>View as Customer
    </a>
</div>