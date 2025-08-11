<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'Home - ECommerce';

try {
    $db = new Database();

    // Get featured products (sliders)
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.is_featured = 1 AND p.stock_quantity > 0 
                         ORDER BY p.created_at DESC LIMIT 5");
    $db->execute($stmt);
    $featuredProducts = $stmt->fetchAll();

    // Get categories
    $stmt = $db->prepare("SELECT c.*, COUNT(p.id) as product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id 
                         ORDER BY c.name LIMIT 8");
    $db->execute($stmt);
    $categories = $stmt->fetchAll();

    // Get best selling products
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.stock_quantity > 0 
                         ORDER BY p.sold_count DESC LIMIT 8");
    $db->execute($stmt);
    $bestSelling = $stmt->fetchAll();

    // Get new products
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.stock_quantity > 0 
                         ORDER BY p.created_at DESC LIMIT 8");
    $db->execute($stmt);
    $newProducts = $stmt->fetchAll();

    // Get cheapest products
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         WHERE p.stock_quantity > 0 
                         ORDER BY p.price ASC LIMIT 8");
    $db->execute($stmt);
    $cheapestProducts = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $featuredProducts = $categories = $bestSelling = $newProducts = $cheapestProducts = [];
}

require_once '../includes/header.php';
?>

<div class="container-fluid px-0">
    <!-- Hero Slider -->
    <?php if (!empty($featuredProducts)): ?>
    <div id="heroCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($featuredProducts as $index => $product): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                    <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>

        <div class="carousel-inner">
            <?php foreach ($featuredProducts as $index => $product): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <div class="hero-section">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                                <p class="lead"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <div class="mb-4">
                                    <span class="price h3"><?php echo formatPrice($product['price']); ?></span>
                                    <span class="ms-3 text-light">by <?php echo htmlspecialchars($product['seller_name']); ?></span>
                                </div>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-light btn-lg me-3">
                                    <i class="fas fa-eye me-2"></i>View Product
                                </a>
                                <button class="btn btn-outline-light btn-lg add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                            <div class="col-lg-6 text-center">
                                <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="img-fluid rounded shadow" style="max-height: 400px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="container">
    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="mb-5">
        <h2 class="section-title">Shop by Category</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <i class="fas fa-box"></i>
                        <h5 class="mt-3"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="text-muted"><?php echo $category['product_count']; ?> products</p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Best Selling Products -->
    <?php if (!empty($bestSelling)): ?>
    <section class="mb-5">
        <h2 class="section-title">Best Selling Products</h2>
        <div class="row">
            <?php foreach ($bestSelling as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card">
                    <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-muted small">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <small class="text-muted"><?php echo $product['sold_count']; ?> sold</small>
                        </div>
                        <div class="mt-2">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
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

    <!-- New Products -->
    <?php if (!empty($newProducts)): ?>
    <section class="mb-5">
        <h2 class="section-title">New Arrivals</h2>
        <div class="row">
            <?php foreach ($newProducts as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card">
                    <div class="position-relative">
                        <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <span class="badge bg-success position-absolute top-0 start-0 m-2">New</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-muted small">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <small class="text-muted"><?php echo formatDate($product['created_at']); ?></small>
                        </div>
                        <div class="mt-2">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
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

    <!-- Cheapest Products -->
    <?php if (!empty($cheapestProducts)): ?>
    <section class="mb-5">
        <h2 class="section-title">Great Deals</h2>
        <div class="row">
            <?php foreach ($cheapestProducts as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card">
                    <div class="position-relative">
                        <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">Best Price</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-muted small">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <span class="badge bg-primary">Great Deal</span>
                        </div>
                        <div class="mt-2">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
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

    <!-- Newsletter Section -->
    <section class="bg-primary text-white p-5 rounded mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3>Stay Updated with Our Latest Offers</h3>
                <p class="mb-0">Subscribe to our newsletter and never miss out on great deals and new products.</p>
            </div>
            <div class="col-lg-4">
                <form class="d-flex">
                    <input type="email" class="form-control me-2" placeholder="Enter your email">
                    <button type="submit" class="btn btn-light">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>