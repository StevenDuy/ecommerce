<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'Products - ECommerce';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$priceMin = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$priceMax = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 10000;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'created_at DESC';

// Build WHERE clause
$whereConditions = ["p.stock_quantity > 0"];
$params = [];

if ($categoryFilter > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchFilter)) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$searchFilter%";
    $params[] = "%$searchFilter%";
}

if ($priceMin > 0) {
    $whereConditions[] = "p.price >= ?";
    $params[] = $priceMin;
}

if ($priceMax < 10000) {
    $whereConditions[] = "p.price <= ?";
    $params[] = $priceMax;
}

$whereClause = implode(' AND ', $whereConditions);

// Validate sort option
$validSorts = [
    'price ASC' => 'Price: Low to High',
    'price DESC' => 'Price: High to Low',
    'created_at DESC' => 'Newest',
    'created_at ASC' => 'Oldest',
    'sold_count DESC' => 'Most Popular',
    'name ASC' => 'Name: A to Z',
    'name DESC' => 'Name: Z to A'
];

if (!array_key_exists($sortBy, $validSorts)) {
    $sortBy = 'created_at DESC';
}

try {
    $db = new Database();

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 WHERE $whereClause";
    $stmt = $db->prepare($countSql);
    $db->execute($stmt, $params);
    $totalItems = $stmt->fetch()['total'];

    // Get products
    $sql = "SELECT p.*, u.name as seller_name, c.name as category_name 
            FROM products p 
            JOIN users u ON p.seller_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $whereClause 
            ORDER BY p.$sortBy 
            LIMIT $itemsPerPage OFFSET $offset";

    $stmt = $db->prepare($sql);
    $db->execute($stmt, $params);
    $products = $stmt->fetchAll();

    // Get categories for filter
    $stmt = $db->prepare("SELECT c.*, COUNT(p.id) as product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id 
                         ORDER BY c.name");
    $db->execute($stmt);
    $categories = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $products = [];
    $categories = [];
    $totalItems = 0;
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Products</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <h5 class="mb-3">
                    <i class="fas fa-filter me-2"></i>Filters
                </h5>

                <form method="GET" id="filterForm">
                    <!-- Search -->
                    <div class="filter-group">
                        <h6>Search</h6>
                        <div class="search-box">
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($searchFilter); ?>" 
                                   placeholder="Search products...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                    <div class="filter-group">
                        <h6>Categories</h6>
                        <div class="form-check">
                            <input class="form-check-input category-filter" type="radio" name="category" value="0" 
                                   <?php echo $categoryFilter === 0 ? 'checked' : ''; ?>>
                            <label class="form-check-label">All Categories</label>
                        </div>
                        <?php foreach ($categories as $category): ?>
                        <div class="form-check">
                            <input class="form-check-input category-filter" type="radio" name="category" 
                                   value="<?php echo $category['id']; ?>" 
                                   <?php echo $categoryFilter === (int)$category['id'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="text-muted">(<?php echo $category['product_count']; ?>)</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <h6>Price Range</h6>
                        <div class="price-range">
                            <input type="number" class="form-control" name="price_min" 
                                   value="<?php echo $priceMin; ?>" placeholder="Min" min="0">
                            <span>to</span>
                            <input type="number" class="form-control" name="price_max" 
                                   value="<?php echo $priceMax; ?>" placeholder="Max" min="0">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>

                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Clear All
                    </a>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Results Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4>Products</h4>
                    <p class="text-muted mb-0">Showing <?php echo count($products); ?> of <?php echo $totalItems; ?> products</p>
                </div>

                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">Sort by:</label>
                    <select class="form-select" id="sort-select" style="width: auto;">
                        <?php foreach ($validSorts as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $sortBy === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        <div class="position-relative">
                            <img src="/ecommerce/assets/images/products/<?php echo $product['main_image_url']; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">

                            <?php if ($product['is_featured']): ?>
                            <span class="badge bg-warning position-absolute top-0 start-0 m-2">Featured</span>
                            <?php endif; ?>

                            <?php if ($product['sold_count'] > 50): ?>
                            <span class="badge bg-success position-absolute top-0 end-0 m-2">Popular</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <p class="text-muted small mb-2">
                                by <?php echo htmlspecialchars($product['seller_name']); ?>
                                <?php if ($product['category_name']): ?>
                                in <?php echo htmlspecialchars($product['category_name']); ?>
                                <?php endif; ?>
                            </p>
                            <p class="card-text small text-muted">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="price h6"><?php echo formatPrice($product['price']); ?></span>
                                <small class="text-muted">
                                    <i class="fas fa-box me-1"></i><?php echo $product['stock_quantity']; ?> in stock
                                </small>
                            </div>

                            <?php if ($product['sold_count'] > 0): ?>
                            <div class="mb-2">
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i><?php echo $product['sold_count']; ?> sold
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary flex-fill">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalItems > $itemsPerPage): ?>
            <div class="d-flex justify-content-center mt-4">
                <?php 
                $currentUrl = $_SERVER['REQUEST_URI'];
                $currentUrl = strtok($currentUrl, '?');
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseUrl = $currentUrl . '?' . http_build_query($queryParams);
                echo paginate($totalItems, $itemsPerPage, $page, $baseUrl);
                ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- No Products Found -->
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>No products found</h4>
                <p class="text-muted">Try adjusting your search or filter criteria</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Reset Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Update sort parameter in URL when sort select changes
document.getElementById('sort-select').addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('sort', this.value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location = url.toString();
});

// Auto-submit form when filters change
document.querySelectorAll('.category-filter').forEach(function(input) {
    input.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>