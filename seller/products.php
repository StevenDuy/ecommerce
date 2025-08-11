<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('seller');

$pageTitle = 'G4F - Manage Products';

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $costPrice = (float)$_POST['cost_price'];
        $stockQuantity = (int)$_POST['stock_quantity'];
        $isFeatured = isset($_POST['is_featured']);

        try {
            $db = new Database();
            $db->beginTransaction();

            // Upload main image
            $mainImageUrl = '';
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $mainImageUrl = uploadImage($_FILES['main_image']);
                if (!$mainImageUrl) {
                    throw new Exception('Failed to upload main image.');
                }
            }

            // Insert product
            $stmt = $db->prepare("INSERT INTO products (seller_id, category_id, name, description, main_image_url, price, cost_price, stock_quantity, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $db->execute($stmt, [getUserId(), $categoryId, $name, $description, $mainImageUrl, $price, $costPrice, $stockQuantity, $isFeatured]);
            $productId = $db->lastInsertId();

            // Upload additional images
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
            showNotification('success', 'Product added successfully!');
        } catch (Exception $e) {
            $db->rollback();
            showNotification('error', 'Failed to add product: ' . $e->getMessage());
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
        $costPrice = (float)$_POST['cost_price'];
        $stockQuantity = (int)$_POST['stock_quantity'];
        $isFeatured = isset($_POST['is_featured']);

        try {
            $db = new Database();
            $db->beginTransaction();

            // Check if product belongs to seller
            $stmt = $db->prepare("SELECT id, main_image_url FROM products WHERE id = ? AND seller_id = ?");
            $db->execute($stmt, [$productId, getUserId()]);
            $product = $stmt->fetch();
            if (!$product) {
                throw new Exception('Product not found or access denied.');
            }

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
            $stmt = $db->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, main_image_url = ?, price = ?, cost_price = ?, stock_quantity = ?, is_featured = ? WHERE id = ?");
            $db->execute($stmt, [$categoryId, $name, $description, $mainImageUrl, $price, $costPrice, $stockQuantity, $isFeatured, $productId]);

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
            showNotification('error', 'Failed to update product: ' . $e->getMessage());
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['delete_gallery_image'])) {
        // Delete gallery image
        $imageId = (int)$_POST['image_id'];
        $productId = (int)$_POST['product_id'];

        try {
            $db = new Database();

            // Check if product belongs to seller
            $stmt = $db->prepare("SELECT p.id FROM products p JOIN product_images pi ON p.id = pi.product_id WHERE pi.id = ? AND p.seller_id = ?");
            $db->execute($stmt, [$imageId, getUserId()]);
            if (!$stmt->fetch()) {
                throw new Exception('Image not found or access denied.');
            }

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
            showNotification('error', 'Failed to delete image: ' . $e->getMessage());
            error_log($e->getMessage());
        }
    }

    elseif (isset($_POST['delete_product'])) {
        // Delete product
        $productId = (int)$_POST['product_id'];

        try {
            $db = new Database();

            // Check if product belongs to seller and get images
            $stmt = $db->prepare("SELECT id, main_image_url FROM products WHERE id = ? AND seller_id = ?");
            $db->execute($stmt, [$productId, getUserId()]);
            $product = $stmt->fetch();
            if (!$product) {
                throw new Exception('Product not found or access denied.');
            }

            // Get all gallery images
            $stmt = $db->prepare("SELECT url FROM product_images WHERE product_id = ?");
            $db->execute($stmt, [$productId]);
            $galleryImages = $stmt->fetchAll();

            // Delete main image
            if ($product['main_image_url']) {
                deleteImage($product['main_image_url']);
            }

            // Delete gallery images
            foreach ($galleryImages as $image) {
                deleteImage($image['url']);
            }

            // Delete product (cascade will delete product_images)
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $db->execute($stmt, [$productId]);

            showNotification('success', 'Product deleted successfully!');
        } catch (Exception $e) {
            showNotification('error', 'Failed to delete product: ' . $e->getMessage());
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
            showNotification('error', 'Failed to add category: ' . $e->getMessage());
            error_log($e->getMessage());
        }
    }
}

// Get filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchFilter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $db = new Database();

    // Get seller's products
    $whereConditions = ["p.seller_id = ?"];
    $params = [getUserId()];

    if ($categoryFilter > 0) {
        $whereConditions[] = "p.category_id = ?";
        $params[] = $categoryFilter;
    }

    if (!empty($searchFilter)) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$searchFilter%";
        $params[] = "%$searchFilter%";
    }

    $whereClause = implode(' AND ', $whereConditions);

    $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE $whereClause 
                         ORDER BY p.created_at DESC");
    $db->execute($stmt, $params);
    $products = $stmt->fetchAll();

    // Get seller's categories
    $stmt = $db->prepare("SELECT * FROM categories WHERE created_by = ? ORDER BY name");
    $db->execute($stmt, [getUserId()]);
    $categories = $stmt->fetchAll();

} catch (Exception $e) {
    handleError($e->getMessage());
    $products = $categories = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-box me-2"></i>Manage Products</h2>
            <p class="text-muted">Add, edit, and manage your product inventory</p>
        </div>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i>Add Product
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-tags me-2"></i>Add Category
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Products</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($searchFilter); ?>" 
                           placeholder="Search by name or description...">
                </div>

                <div class="col-md-4">
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

                <div class="col-md-4 d-flex align-items-end">
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

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Your Products (<?php echo count($products); ?>)
            </h5>
        </div>

        <div class="card-body">
            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-5x text-muted mb-3"></i>
                <h4>No products found</h4>
                <p class="text-muted">Start by adding your first product to begin selling.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Add Your First Product
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Cost</th>
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
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">
                                            ID: <?php echo $product['id']; ?>
                                            <?php if ($product['is_featured']): ?>
                                            <span class="badge bg-warning ms-1">Featured</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo $product['category_name'] ? htmlspecialchars($product['category_name']) : '<span class="text-muted">Uncategorized</span>'; ?>
                            </td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td><?php echo formatPrice($product['cost_price']); ?></td>
                            <td>
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php elseif ($product['stock_quantity'] <= 5): ?>
                                    <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?> Low</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['sold_count']; ?></td>
                            <td>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary edit-product" 
                                            data-product='<?php echo json_encode($product); ?>'
                                            data-bs-toggle="modal" data-bs-target="#editProductModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-info view-product"
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required>
                                <div class="invalid-feedback">Please enter product name.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <option value="0">Select Category</option>
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
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                <div class="invalid-feedback">Please enter selling price.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01" min="0">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" required>
                                <div class="invalid-feedback">Please enter stock quantity.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="main_image" class="form-label"><i class="fas fa-image me-2"></i>Thumbnail Image *</label>
                        <input type="file" class="form-control image-upload-input" name="main_image" 
                               accept="image/*" required>
                        <div class="invalid-feedback">Please select main product image.</div>
                        <div class="thumbnail-preview mt-3 border rounded p-2 bg-light" style="min-height: 120px; display: flex; align-items: center; justify-content: center;"><div class="text-muted">Thumbnail preview will appear here</div></div>
                    </div>

                    <div class="mb-3">
                        <label for="additional_images" class="form-label"><i class="fas fa-images me-2"></i>Gallery Images (Optional)</label>
                        <input type="file" class="form-control image-upload-input" name="additional_images[]" 
                               accept="image/*" multiple>
                        <div class="form-text">You can select multiple images. Click on an image to remove it before uploading.</div>
                        <div class="image-preview mt-2"></div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_featured" id="is_featured">
                        <label class="form-check-label" for="is_featured">
                            Mark as Featured Product
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Product
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
                                <label for="edit_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                                <div class="invalid-feedback">Please enter product name.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="edit_category_id">
                                    <option value="0">Select Category</option>
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
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Selling Price *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                                <div class="invalid-feedback">Please enter selling price.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_cost_price" class="form-label">Cost Price</label>
                                <input type="number" class="form-control" name="cost_price" id="edit_cost_price" step="0.01" min="0">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock_quantity" id="edit_stock_quantity" min="0" required>
                                <div class="invalid-feedback">Please enter stock quantity.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_main_image" class="form-label"><i class="fas fa-image me-2"></i>Update Thumbnail Image</label>
                        <input type="file" class="form-control image-upload-input" name="main_image" id="edit_main_image" accept="image/*">
                        <div class="form-text">Leave empty to keep current image</div>
                        <div id="current-main-image" class="mb-3"></div>
                        <div class="thumbnail-preview mt-3 border rounded p-2 bg-light" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                            <div class="text-muted">New thumbnail preview will appear here</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_additional_images" class="form-label"><i class="fas fa-images me-2"></i>Add More Gallery Images</label>
                        <input type="file" class="form-control image-upload-input" name="additional_images[]" id="edit_additional_images" accept="image/*" multiple>
                        <div class="form-text">You can select multiple images to add to gallery</div>
                        <div class="image-preview mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Gallery Images</label>
                        <div id="current-gallery-images" class="row g-2 mt-1"></div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_featured" id="edit_is_featured">
                        <label class="form-check-label" for="edit_is_featured">
                            Mark as Featured Product
                        </label>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tags me-2"></i>Add New Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" required>
                        <div class="invalid-feedback">Please enter category name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category (Optional)</label>
                        <select class="form-select" name="parent_id">
                            <option value="">No Parent (Main Category)</option>
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
                        <i class="fas fa-save me-2"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

// Edit product functionality
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-product');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const product = JSON.parse(this.getAttribute('data-product'));

            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_category_id').value = product.category_id || 0;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_cost_price').value = product.cost_price;
            document.getElementById('edit_stock_quantity').value = product.stock_quantity;
            document.getElementById('edit_is_featured').checked = product.is_featured == 1;

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
        });
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
                                         style="height: 120px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" 
                                            style="transform: translate(50%, -50%); width: 24px; height: 24px; padding: 0; font-size: 12px; display: flex; align-items: center; justify-content: center;"
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
    function deleteGalleryImage(imageId, productId, button) {
        if (confirm('Are you sure you want to delete this image?')) {
            const formData = new FormData();
            formData.append('delete_gallery_image', '1');
            formData.append('image_id', imageId);
            formData.append('product_id', productId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Remove the image from display
                button.closest('.col-md-3').remove();
                // Show success message (you might want to add a notification system)
                console.log('Image deleted successfully');
            })
            .catch(error => {
                console.error('Error deleting image:', error);
                alert('Failed to delete image');
            });
        }
    }

    // Image preview functionality
    const imageInputs = document.querySelectorAll('.image-upload-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Determine if this is a thumbnail or gallery input
            const isThumbnail = this.name === 'main_image' || this.id === 'edit_main_image';
            const previewContainer = isThumbnail ? 
                this.closest('.card').querySelector('.thumbnail-preview') : 
                this.closest('.card').querySelector('.gallery-preview');
            
            previewImages(this, previewContainer, isThumbnail);
        });
    });
});

// Image preview function
function previewImages(input, container, isThumbnail) {
    container.innerHTML = '';

    if (input.files && input.files.length > 0) {
        const files = Array.from(input.files);

        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const imageWrapper = document.createElement('div');
                    imageWrapper.className = isThumbnail ? 
                        'image-wrapper d-flex justify-content-center mb-3' : 
                        'image-wrapper d-inline-block position-relative me-2 mb-2';
                    
                    if (isThumbnail) {
                        imageWrapper.style.width = '100%';
                        imageWrapper.style.height = '200px';
                        imageWrapper.style.borderRadius = '8px';
                        imageWrapper.style.overflow = 'hidden';
                        imageWrapper.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                    } else {
                        imageWrapper.style.width = '120px';
                        imageWrapper.style.height = '120px';
                        imageWrapper.style.borderRadius = '6px';
                        imageWrapper.style.overflow = 'hidden';
                        imageWrapper.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                    }

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    img.style.transition = 'transform 0.3s ease';
                    
                    // Add hover effect
                    imageWrapper.addEventListener('mouseenter', function() {
                        img.style.transform = 'scale(1.05)';
                    });
                    
                    imageWrapper.addEventListener('mouseleave', function() {
                        img.style.transform = 'scale(1)';
                    });

                    // Add remove button only for gallery images
                    if (!isThumbnail) {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle';
                        removeBtn.style.transform = 'translate(50%, -50%)';
                        removeBtn.style.width = '24px';
                        removeBtn.style.height = '24px';
                        removeBtn.style.padding = '0';
                        removeBtn.style.fontSize = '12px';
                        removeBtn.style.display = 'flex';
                        removeBtn.style.alignItems = 'center';
                        removeBtn.style.justifyContent = 'center';
                        removeBtn.style.zIndex = '2';
                        removeBtn.style.transition = 'all 0.2s ease';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        
                        // Add hover effect
                        removeBtn.addEventListener('mouseenter', function() {
                            removeBtn.style.transform = 'translate(50%, -50%) scale(1.1)';
                            removeBtn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
                        });
                        
                        removeBtn.addEventListener('mouseleave', function() {
                            removeBtn.style.transform = 'translate(50%, -50%) scale(1)';
                            removeBtn.style.boxShadow = 'none';
                        });
                        
                        removeBtn.onclick = function() {
                            removeImage(input, index, imageWrapper);
                        };

                        imageWrapper.appendChild(removeBtn);
                    }

                    imageWrapper.appendChild(img);
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

    // Create new FileList without the removed file
    const dt = new DataTransfer();
    const files = Array.from(input.files);

    files.forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });

    input.files = dt.files;

    // Update preview
    const previewContainer = input.parentNode.querySelector('.image-preview');
    previewImages(input, previewContainer);
}

// Delete product function
function deleteProduct(productId, productName) {
    if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_product" value="1">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- View Product Modal -->
<div class="modal fade" id="viewSellerProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Your Product Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sellerProductDetailsContent">
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
// View product modal
$(document).ready(function() {
    $('.view-product').on('click', function() {
        const productId = $(this).data('product-id');

        $('#sellerProductDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading product details...</p></div>');

        new bootstrap.Modal(document.getElementById('viewSellerProductModal')).show();

        $.get('../ajax/get_seller_product_details.php', { id: productId }, function(data) {
            $('#sellerProductDetailsContent').html(data);
        }).fail(function() {
            $('#sellerProductDetailsContent').html('<div class="alert alert-danger">Failed to load product details.</div>');
        });
    });
});
</script>