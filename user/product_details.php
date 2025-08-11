<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId === 0) {
    header('Location: products.php');
    exit();
}

try {
    $db = new Database();

    // Get product details
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name, c.name as category_name 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.id = ?");
    $db->execute($stmt, [$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit();
    }

    // Get product images
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $db->execute($stmt, [$productId]);
    $productImages = $stmt->fetchAll();

    // Get related products (same category)
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.category_id = ? AND p.id != ? AND p.stock_quantity > 0 
                         ORDER BY RAND() LIMIT 4");
    $db->execute($stmt, [$product['category_id'], $productId]);
    $relatedProducts = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage(), 'products.php');
}

$pageTitle = $product['name'] . ' - ECommerce';
require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <?php if ($product['category_name']): ?>
            <li class="breadcrumb-item">
                <a href="products.php?category=<?php echo $product['category_id']; ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <!-- Product Details -->
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <!-- Main Image -->
                <div class="main-image mb-3">
                    <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                         class="img-fluid rounded shadow" id="mainProductImage"
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>

                <!-- Thumbnail Images -->
                <?php if (!empty($productImages)): ?>
                <div class="thumbnail-images">
                    <div class="row">
                        <!-- Main image thumbnail -->
                        <div class="col-3 mb-2">
                            <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                                 class="img-fluid rounded thumbnail-img active" 
                                 data-image="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>"
                                 style="cursor: pointer; border: 2px solid #007bff;">
                        </div>

                        <!-- Additional images -->
                        <?php foreach ($productImages as $image): ?>
                        <div class="col-3 mb-2">
                            <img src="/ecommerce/assets/images/products/<?php echo $image['url']; ?>" 
                                 class="img-fluid rounded thumbnail-img" 
                                 data-image="/ecommerce/assets/images/products/<?php echo $image['url']; ?>"
                                 style="cursor: pointer; border: 2px solid transparent;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="product-info">
                <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="mb-3">
                    <span class="text-muted">Sold by: </span>
                    <strong><?php echo htmlspecialchars($product['seller_name']); ?></strong>

                    <?php if ($product['category_name']): ?>
                    <span class="ms-3 text-muted">Category: </span>
                    <a href="products.php?category=<?php echo $product['category_id']; ?>" 
                       class="text-decoration-none">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="price-section mb-4">
                    <span class="h2 text-primary"><?php echo formatPrice($product['price']); ?></span>

                    <?php if ($product['is_featured']): ?>
                    <span class="badge bg-warning ms-3">Featured</span>
                    <?php endif; ?>

                    <?php if ($product['sold_count'] > 0): ?>
                    <div class="mt-2">
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            <?php echo $product['sold_count']; ?> customers have purchased this product
                        </small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stock-info mb-4">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <?php if ($product['stock_quantity'] > 10): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>In Stock (<?php echo $product['stock_quantity']; ?> available)
                        </span>
                        <?php else: ?>
                        <span class="badge bg-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>Low Stock (<?php echo $product['stock_quantity']; ?> left)
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="badge bg-danger">
                        <i class="fas fa-times me-1"></i>Out of Stock
                    </span>
                    <?php endif; ?>
                </div>

                <div class="description mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <!-- Add to Cart Form -->
                <?php if ($product['stock_quantity'] > 0): ?>
                <form class="add-to-cart-form">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="quantity-controls">
                                <button type="button" class="btn btn-outline-secondary quantity-minus">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control quantity-input text-center" 
                                       id="quantity" name="quantity" value="1" min="1" 
                                       max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="btn btn-outline-secondary quantity-plus">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex">
                        <button type="button" class="btn btn-primary btn-lg add-to-cart me-md-2" 
                                data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                        </button>
                        <a href="cart.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>View Cart
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This product is currently out of stock.
                </div>
                <?php endif; ?>

                <!-- Product Stats -->
                <div class="product-stats mt-4 p-3 bg-light rounded">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-item">
                                <i class="fas fa-calendar-alt text-primary"></i>
                                <div class="mt-1">
                                    <small class="text-muted">Added</small><br>
                                    <strong><?php echo formatDate($product['created_at']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <i class="fas fa-shopping-bag text-success"></i>
                                <div class="mt-1">
                                    <small class="text-muted">Sold</small><br>
                                    <strong><?php echo $product['sold_count']; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <i class="fas fa-box text-info"></i>
                                <div class="mt-1">
                                    <small class="text-muted">In Stock</small><br>
                                    <strong><?php echo $product['stock_quantity']; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="mt-5">
        <h3 class="section-title">Related Products</h3>
        <div class="row">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card product-card h-100">
                    <img src="/ecommerce/assets/images/products/<?php echo $relatedProduct['main_image_url']; ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">

                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h6>
                        <p class="text-muted small">by <?php echo htmlspecialchars($relatedProduct['seller_name']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                            <?php if ($relatedProduct['sold_count'] > 0): ?>
                            <small class="text-muted"><?php echo $relatedProduct['sold_count']; ?> sold</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="product_details.php?id=<?php echo $relatedProduct['id']; ?>" 
                               class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <button class="btn btn-primary btn-sm add-to-cart" 
                                    data-product-id="<?php echo $relatedProduct['id']; ?>">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
// Image gallery functionality
document.addEventListener('DOMContentLoaded', function() {
    const thumbnails = document.querySelectorAll('.thumbnail-img');
    const mainImage = document.getElementById('mainProductImage');

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Update main image
            mainImage.src = this.dataset.image;

            // Update active thumbnail
            thumbnails.forEach(thumb => {
                thumb.style.border = '2px solid transparent';
            });
            this.style.border = '2px solid #007bff';
        });
    });

    // Quantity controls
    const quantityInput = document.getElementById('quantity');
    const minusBtn = document.querySelector('.quantity-minus');
    const plusBtn = document.querySelector('.quantity-plus');

    minusBtn?.addEventListener('click', function() {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    plusBtn?.addEventListener('click', function() {
        const currentValue = parseInt(quantityInput.value);
        const maxValue = parseInt(quantityInput.getAttribute('max'));
        if (currentValue < maxValue) {
            quantityInput.value = currentValue + 1;
        }
    });

    // Add to cart with quantity
    document.querySelector('.add-to-cart')?.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const quantity = quantityInput.value;
        const button = this;

        // Show loading
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="loading"></span> Adding...';
        button.disabled = true;

        fetch('/ecommerce/ajax/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);

                // Update cart count
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }

                // Show success state
                button.innerHTML = '<i class="fas fa-check me-2"></i>Added to Cart!';
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-primary');
                    button.disabled = false;
                }, 2000);
            } else {
                toastr.error(data.message);
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            toastr.error('An error occurred. Please try again.');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>