<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('admin');

$pageTitle = 'Manage Products - ECommerce';

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_product'])) {
        // Delete product
        $productId = (int)$_POST['product_id'];

        try {
            $db = new Database();
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $db->execute($stmt, [$productId]);

            showNotification('success', 'Product deleted successfully!');
        } catch (Exception $e) {
            showNotification('error', 'Failed to delete product.');
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['update_product'])) {
        // Update product
        $productId = (int)$_POST['product_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $stockQuantity = (int)$_POST['stock_quantity'];
        $isFeatured = isset($_POST['is_featured']);

        try {
            $db = new Database();
            $db->beginTransaction();

            // Get current product info
            $stmt = $db->prepare("SELECT main_image_url FROM products WHERE id = ?");
            $db->execute($stmt, [$productId]);
            $product = $stmt->fetch();
            $mainImageUrl = $product['main_image_url'];

            // Upload new main image if provided
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $newMainImage = uploadImage($_FILES['main_image']);
                if ($newMainImage) {
                    // Delete old main image
                    if ($mainImageUrl) {
                        deleteImage($mainImageUrl);
                    }
                    $mainImageUrl = $newMainImage;
                }
            }

            // Update product
            $stmt = $db->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, main_image_url = ?, price = ?, stock_quantity = ?, is_featured = ? WHERE id = ?");
            $db->execute($stmt, [$categoryId, $name, $description, $mainImageUrl, $price, $stockQuantity, $isFeatured, $productId]);

            // Handle additional images
            if (isset($_FILES['additional_images'])) {
                for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                    if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $imageFile = [
                            'name' => $_FILES['additional_images']['name'][$i],
                            'type' => $_FILES['additional_images']['type'][$i],
                            'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                            'error' => $_FILES['additional_images']['error'][$i],
                            'size' => $_FILES['additional_images']['size'][$i]
                        ];

                        $imageUrl = uploadImage($imageFile);
                        if ($imageUrl) {
                            $stmt = $db->prepare("INSERT INTO product_images (product_id, url, sort_order) VALUES (?, ?, ?)");
                            $db->execute($stmt, [$productId, $imageUrl, $i]);
                        }
                    }
                }
            }

            $db->commit();
            showNotification('success', 'Product updated successfully!');
        } catch (Exception $e) {
            $db->rollback();
            showNotification('error', 'Failed to update product.');
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['delete_gallery_image'])) {
        // Delete gallery image
        $imageId = (int)$_POST['image_id'];

        try {
            $db = new Database();

            // Get image info before deletion
            $stmt = $db->prepare("SELECT url FROM product_images WHERE id = ?");
            $db->execute($stmt, [$imageId]);
            $image = $stmt->fetch();

            if ($image) {
                // Delete image file
                deleteImage($image['url']);

                // Delete from database
                $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
                $db->execute($stmt, [$imageId]);

                showNotification('success', 'Image deleted successfully!');
            }
        } catch (Exception $e) {
            showNotification('error', 'Failed to delete image.');
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['add_category'])) {
        // Add new category
        $categoryName = sanitizeInput($_POST['category_name']);
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        try {
            $db = new Database();
            $stmt = $db->prepare("INSERT INTO categories (name, parent_id, created_by) VALUES (?, ?, ?)");
            $db->execute($stmt, [$categoryName, $parentId, getUserId()]);

            showNotification('success', 'Category added successfully!');
        } catch (Exception $e) {
            showNotification('error', 'Failed to add category.');
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['delete_category'])) {
        // Delete category
        $categoryId = (int)$_POST['category_id'];

        try {
            $db = new Database();

            // Check if category has products
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $db->execute($stmt, [$categoryId]);
            $productCount = $stmt->fetch()['count'];

            if ($productCount > 0) {
                showNotification('error', 'Cannot delete category with products. Move products to another category first.');
            } else {
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $db->execute($stmt, [$categoryId]);

                showNotification('success', 'Category deleted successfully!');
            }
        } catch (Exception $e) {
            showNotification('error', 'Failed to delete category.');
            error_log($e->getMessage());
        }
    }
}

// Get filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sellerFilter = isset($_GET['seller']) ? (int)$_GET['seller'] : 0;
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

try {
    $db = new Database();

    // Build WHERE clause for filtering
    $whereConditions = [];
    $params = [];

    if ($categoryFilter > 0) {
        $whereConditions[] = "p.category_id = ?";
        $params[] = $categoryFilter;
    }

    if ($sellerFilter > 0) {
        $whereConditions[] = "p.seller_id = ?";
        $params[] = $sellerFilter;
    }

    if (!empty($searchFilter)) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }

    if ($statusFilter === 'out_of_stock') {
        $whereConditions[] = "p.stock_quantity = 0";
    } elseif ($statusFilter === 'low_stock') {
        $whereConditions[] = "p.stock_quantity > 0 AND p.stock_quantity <= 5";
    } elseif ($statusFilter === 'featured') {
        $whereConditions[] = "p.is_featured = 1";
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get products
    $stmt = $db->prepare("SELECT p.*, u.name as seller_name, c.name as category_name 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         $whereClause 
                         ORDER BY p.created_at DESC");
    $db->execute($stmt, $params);
    $products = $stmt->fetchAll();

    // Get all categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $db->execute($stmt);
    $categories = $stmt->fetchAll();

    // Get all sellers
    $stmt = $db->prepare("SELECT id, name FROM users WHERE role = 'seller' ORDER BY name");
    $db->execute($stmt);
    $sellers = $stmt->fetchAll();

    // Get product statistics
    $stmt = $db->prepare("SELECT 
                         COUNT(*) as total_products,
                         COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
                         COUNT(CASE WHEN stock_quantity > 0 AND stock_quantity <= 5 THEN 1 END) as low_stock,
                         COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured,
                         SUM(sold_count) as total_sold
                         FROM products");
    $db->execute($stmt);
    $stats = $stmt->fetch();

} catch (Exception $e) {
    handleError($e->getMessage());
    $products = $categories = $sellers = [];
    $stats = ['total_products' => 0, 'out_of_stock' => 0, 'low_stock' => 0, 'featured' => 0, 'total_sold' => 0];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-box me-2"></i>Manage Products</h2>
            <p class="text-muted">View and manage all products in the system</p>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-tags me-2"></i>Add Category
        </button>
    </div>

    <!-- Product Statistics -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-box text-primary fa-2x mb-2"></i>
                    <h4><?php echo $stats['total_products']; ?></h4>
                    <small class="text-muted">Total Products</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star text-warning fa-2x mb-2"></i>
                    <h4><?php echo $stats['featured']; ?></h4>
                    <small class="text-muted">Featured</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-shopping-bag text-success fa-2x mb-2"></i>
                    <h4><?php echo $stats['total_sold']; ?></h4>
                    <small class="text-muted">Items Sold</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                    <h4><?php echo $stats['low_stock']; ?></h4>
                    <small class="text-muted">Low Stock</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                    <h4><?php echo $stats['out_of_stock']; ?></h4>
                    <small class="text-muted">Out of Stock</small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tags text-info fa-2x mb-2"></i>
                    <h4><?php echo count($categories); ?></h4>
                    <small class="text-muted">Categories</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search Products</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($searchFilter); ?>" 
                           placeholder="Search by name or description...">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $categoryFilter === (int)$category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Seller</label>
                    <select class="form-select" name="seller">
                        <option value="0">All Sellers</option>
                        <?php foreach ($sellers as $seller): ?>
                        <option value="<?php echo $seller['id']; ?>" 
                                <?php echo $sellerFilter === (int)$seller['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($seller['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="featured" <?php echo $statusFilter === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="low_stock" <?php echo $statusFilter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out_of_stock" <?php echo $statusFilter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Products List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Products (<?php echo count($products); ?>)
                    </h5>
                </div>

                <div class="card-body">
                    <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-5x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">No products match your current filters.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Sold</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getImagePath($product['main_image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if ($product['is_featured']): ?>
                                                <span class="badge bg-warning ms-2">Featured</span>
                                                <?php endif; ?>
                                                <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><strong><?php echo formatPrice($product['price']); ?></strong></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] <= 5): ?>
                                        <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?> left</span>
                                        <?php else: ?>
                                        <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $product['sold_count']; ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo formatDate($product['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info view-product"
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-product" 
                                                    data-product='<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-product" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Categories Management -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Categories
                    </h5>
                </div>

                <div class="card-body">
                    <?php if (empty($categories)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No categories found</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-1"></i>Add Category
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($categories as $category): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                <br><small class="text-muted">
                                    <?php
                                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                                    $db->execute($stmt, [$category['id']]);
                                    $productCount = $stmt->fetch()['count'];
                                    echo $productCount . ' product' . ($productCount != 1 ? 's' : '');
                                    ?>
                                </small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger delete-category" 
                                    data-category-id="<?php echo $category['id']; ?>"
                                    data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                        <div class="invalid-feedback">Please enter a category name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category (Optional)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="product_id" id="edit_product_id">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price ($)</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_main_image" class="form-label">Update Main Image</label>
                        <input type="file" class="form-control image-upload-input" name="main_image" id="edit_main_image" accept="image/*">
                        <div class="form-text">Leave empty to keep current image</div>
                        <div id="current-main-image" class="mt-2"></div>
                        <div class="image-preview mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_additional_images" class="form-label">Add More Images</label>
                        <input type="file" class="form-control image-upload-input" name="additional_images[]" id="edit_additional_images" accept="image/*" multiple>
                        <div class="form-text">You can select multiple images to add to gallery</div>
                        <div class="image-preview mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Gallery Images</label>
                        <div id="current-gallery-images" class="row g-2 mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured">
                            <label class="form-check-label" for="edit_is_featured">
                                Featured Product
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="product_id" id="delete_product_id">

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>

                    <p>Are you sure you want to delete the product: <strong id="delete_product_name"></strong>?</p>

                    <p class="text-muted small">This will permanently remove the product and all its associated data from the system.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_product" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="category_id" id="delete_category_id">

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>

                    <p>Are you sure you want to delete the category: <strong id="delete_category_name"></strong>?</p>

                    <p class="text-muted small">Note: You cannot delete categories that contain products.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_category" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Product Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="productDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading product details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script>
$(document).ready(function() {
    // Edit product
    $('.edit-product').on('click', function() {
        const product = $(this).data('product');

        $('#edit_product_id').val(product.id);
        $('#edit_name').val(product.name);
        $('#edit_description').val(product.description);
        $('#edit_category_id').val(product.category_id);
        $('#edit_price').val(product.price);
        $('#edit_stock_quantity').val(product.stock_quantity);
        $('#edit_is_featured').prop('checked', product.is_featured == 1);

        // Load current main image
        const currentMainImage = document.getElementById('current-main-image');
        if (product.main_image_url) {
            currentMainImage.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="/ecommerce/assets/images/products/${product.main_image_url}" 
                         alt="Current main image" class="rounded me-2" style="width: 60px; height: 60px; object-fit: cover;">
                    <span class="text-muted">Current main image</span>
                </div>
            `;
        } else {
            currentMainImage.innerHTML = '<span class="text-muted">No main image</span>';
        }

        // Load current gallery images
        loadGalleryImages(product.id);

        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    });

    // Function to load gallery images
    function loadGalleryImages(productId) {
        const galleryContainer = document.getElementById('current-gallery-images');
        galleryContainer.innerHTML = '<div class="text-muted">Loading gallery images...</div>';

        fetch('../ajax/get_product_gallery.php?product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.images.length > 0) {
                    let imagesHtml = '';
                    data.images.forEach(image => {
                        imagesHtml += `
                            <div class="col-md-3 col-4">
                                <div class="position-relative">
                                    <img src="/ecommerce/assets/images/products/${image.url}" 
                                         alt="Gallery image" class="img-thumbnail w-100" 
                                         style="height: 80px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                                            style="transform: translate(50%, -50%); font-size: 10px; padding: 2px 6px;"
                                            onclick="deleteGalleryImage(${image.id}, ${productId}, this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    galleryContainer.innerHTML = imagesHtml;
                } else {
                    galleryContainer.innerHTML = '<div class="text-muted">No gallery images</div>';
                }
            })
            .catch(error => {
                console.error('Error loading gallery images:', error);
                galleryContainer.innerHTML = '<div class="text-danger">Error loading gallery images</div>';
            });
    }

    // Function to delete gallery image
    window.deleteGalleryImage = function(imageId, productId, button) {
        if (confirm('Are you sure you want to delete this image?')) {
            const formData = new FormData();
            formData.append('delete_gallery_image', '1');
            formData.append('image_id', imageId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                button.closest('.col-md-3').remove();
                console.log('Image deleted successfully');
            })
            .catch(error => {
                console.error('Error deleting image:', error);
                alert('Failed to delete image');
            });
        }
    };

    // Image preview functionality
    const imageInputs = document.querySelectorAll('.image-upload-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewContainer = this.parentNode.querySelector('.image-preview');
            previewImages(this, previewContainer);
        });
    });

    // Image preview function
    function previewImages(input, container) {
        container.innerHTML = '';

        if (input.files && input.files.length > 0) {
            const files = Array.from(input.files);

            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imageWrapper = document.createElement('div');
                        imageWrapper.className = 'image-wrapper d-inline-block position-relative me-2 mb-2';
                        imageWrapper.style.width = '100px';
                        imageWrapper.style.height = '100px';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';

                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
                        removeBtn.style.transform = 'translate(50%, -50%)';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.onclick = function() {
                            removeImage(input, index, imageWrapper);
                        };

                        imageWrapper.appendChild(img);
                        imageWrapper.appendChild(removeBtn);
                        container.appendChild(imageWrapper);
                    };

                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Remove image function
    function removeImage(input, index, wrapper) {
        wrapper.remove();

        const dt = new DataTransfer();
        const files = Array.from(input.files);

        files.forEach((file, i) => {
            if (i !== index) {
                dt.items.add(file);
            }
        });

        input.files = dt.files;

        const previewContainer = input.parentNode.querySelector('.image-preview');
        previewImages(input, previewContainer);
    }

    // View product
    $('.view-product').on('click', function() {
        const productId = $(this).data('product-id');

        $('#productDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading product details...</p></div>');

        new bootstrap.Modal(document.getElementById('viewProductModal')).show();

        $.get('../ajax/get_admin_product_details.php', { id: productId }, function(data) {
            $('#productDetailsContent').html(data);
        }).fail(function() {
            $('#productDetailsContent').html('<div class="alert alert-danger">Failed to load product details.</div>');
        });
    });

    // Delete product
    $('.delete-product').on('click', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');

        $('#delete_product_id').val(productId);
        $('#delete_product_name').text(productName);

        new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
    });

    // Delete category
    $('.delete-category').on('click', function() {
        const categoryId = $(this).data('category-id');
        const categoryName = $(this).data('category-name');

        $('#delete_category_id').val(categoryId);
        $('#delete_category_name').text(categoryName);

        new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
    });
});
</script>