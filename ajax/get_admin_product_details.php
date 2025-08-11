<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'session.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'utils.php';

requireRole('admin');

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    echo '<div class="alert alert-danger">Invalid product ID</div>';
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get product details with seller and category info
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.name, p.description, p.price, p.cost_price, 
            p.stock_quantity, p.sold_count, p.is_featured, p.main_image_url,
            p.created_at, p.updated_at,
            c.name as category_name,
            u.name as seller_name, u.email as seller_email
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.seller_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo '<div class="alert alert-danger">Product not found</div>';
        exit();
    }

    // Get product images
    $stmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? ORDER BY id");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Calculate profit margin
    $profitMargin = $product['cost_price'] > 0 ? 
        (($product['price'] - $product['cost_price']) / $product['cost_price']) * 100 : 0;

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

        <!-- Product Stats -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Product Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-primary mb-1"><?php echo $product['stock_quantity']; ?></h5>
                            <small class="text-muted">In Stock</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-success mb-1"><?php echo $product['sold_count']; ?></h5>
                            <small class="text-muted">Sold</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h5 class="text-warning mb-1"><?php echo number_format($profitMargin, 1); ?>%</h5>
                        <small class="text-muted">Profit Margin</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Product Information -->
        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
        <p class="text-muted mb-3">Product ID: #<?php echo $product['id']; ?></p>

        <!-- Status Badges -->
        <div class="mb-3">
            <?php if ($product['is_featured']): ?>
                <span class="badge bg-warning me-2">Featured</span>
            <?php endif; ?>

            <?php if ($product['stock_quantity'] == 0): ?>
                <span class="badge bg-danger">Out of Stock</span>
            <?php elseif ($product['stock_quantity'] <= 5): ?>
                <span class="badge bg-warning">Low Stock</span>
            <?php else: ?>
                <span class="badge bg-success">In Stock</span>
            <?php endif; ?>
        </div>

        <!-- Pricing Information -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="fas fa-dollar-sign me-2"></i>Pricing</h6>
                <div class="row">
                    <div class="col-6">
                        <strong>Selling Price:</strong><br>
                        <span class="h5 text-success">$<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <div class="col-6">
                        <strong>Cost Price:</strong><br>
                        <span class="h6 text-muted">$<?php echo number_format($product['cost_price'], 2); ?></span>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <strong>Profit per unit: </strong>
                    <span class="text-success">$<?php echo number_format($product['price'] - $product['cost_price'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Seller Information -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="fas fa-user me-2"></i>Seller Information</h6>
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
            </div>
        </div>

        <!-- Category & Dates -->
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-info-circle me-2"></i>Details</h6>
                <p class="mb-1"><strong>Category:</strong> <?php echo $product['category_name'] ? htmlspecialchars($product['category_name']) : 'Uncategorized'; ?></p>
                <p class="mb-1"><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($product['created_at'])); ?></p>
                <p class="mb-0"><strong>Updated:</strong> <?php echo date('M d, Y H:i', strtotime($product['updated_at'])); ?></p>
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