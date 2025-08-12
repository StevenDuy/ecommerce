-- ========================================
-- 📦 ECOMMERCE DATABASE STRUCTURE & DATA
-- ========================================

-- LƯU Ý: Đảm bảo bạn đã chọn đúng database trước khi chạy script này.
-- Ví dụ: USE your_database;

-- ========================================
-- BẢNG USERS
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100)                NOT NULL,
    email            VARCHAR(255) UNIQUE         NOT NULL,
    password_hash    VARCHAR(255)                NOT NULL,
    role             ENUM('user','seller','admin') NOT NULL DEFAULT 'user',
    status           ENUM('active','inactive')   NOT NULL DEFAULT 'active',
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- BẢNG ADDRESSES
-- ========================================
CREATE TABLE IF NOT EXISTS addresses (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT                         NOT NULL,
    recipient_name   VARCHAR(100)                NOT NULL,
    line1            VARCHAR(255)                NOT NULL,
    line2            VARCHAR(255),
    city             VARCHAR(100)                NOT NULL,
    state            VARCHAR(100),
    postal_code      VARCHAR(20)                 NOT NULL,
    country          VARCHAR(100)                NOT NULL,
    is_default       BOOLEAN                     NOT NULL DEFAULT FALSE,
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_addresses_user_id (user_id),
    INDEX idx_addresses_is_default (is_default)
) ENGINE=InnoDB;

-- ========================================
-- BẢNG CATEGORIES
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100)                NOT NULL,
    parent_id        INT,
    created_by       INT                         NOT NULL,
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- BẢNG PRODUCTS
-- ========================================
CREATE TABLE IF NOT EXISTS products (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    seller_id        INT                         NOT NULL,
    category_id      INT                         NULL,
    name             VARCHAR(255)                NOT NULL,
    description      TEXT,
    main_image_url   VARCHAR(500),
    price            DECIMAL(10,2)               NOT NULL,
    cost_price       DECIMAL(10,2)               NOT NULL DEFAULT 0,
    stock_quantity   INT                         NOT NULL DEFAULT 0,
    sold_count       INT                         NOT NULL DEFAULT 0,
    is_featured      BOOLEAN                     NOT NULL DEFAULT FALSE,
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_name       (name),
    INDEX idx_products_category   (category_id),
    INDEX idx_products_price      (price),
    FOREIGN KEY (seller_id)   REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- BẢNG PRODUCT_IMAGES
-- ========================================
CREATE TABLE IF NOT EXISTS product_images (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    product_id       INT                         NOT NULL,
    url              VARCHAR(500)                NOT NULL,
    sort_order       INT                         NOT NULL DEFAULT 0,
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- BẢNG CART_ITEMS
-- ========================================
CREATE TABLE IF NOT EXISTS cart_items (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT                         NOT NULL,
    product_id       INT                         NOT NULL,
    quantity         INT                         NOT NULL DEFAULT 1,
    created_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_cart_user_id (user_id),
    INDEX idx_cart_product_id (product_id)
) ENGINE=InnoDB;

-- ========================================
-- BẢNG ORDERS
-- ========================================
CREATE TABLE IF NOT EXISTS orders (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    user_id              INT                         NOT NULL,
    shipping_address_id  INT,
    status               ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    total_amount         DECIMAL(12,2)               NOT NULL,
    created_at           DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE RESTRICT,
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- BẢNG ORDER_ITEMS
-- ========================================
CREATE TABLE IF NOT EXISTS order_items (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    order_id          INT                         NOT NULL,
    product_id        INT                         NOT NULL,
    seller_id         INT                         NOT NULL,
    quantity          INT                         NOT NULL,
    price_at_purchase DECIMAL(10,2)               NOT NULL,
    total_price       DECIMAL(12,2)               NOT NULL,
    created_at        DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    INDEX idx_order_items_seller_id (seller_id)
) ENGINE=InnoDB;

-- ========================================
-- TRIGGERS: cập nhật sold_count cho sản phẩm
-- ========================================
DELIMITER //

CREATE TRIGGER update_product_sold_count_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET sold_count = sold_count + NEW.quantity 
    WHERE id = NEW.product_id;
END //

CREATE TRIGGER update_product_sold_count_update
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET sold_count = sold_count - OLD.quantity + NEW.quantity 
    WHERE id = NEW.product_id;
END //

CREATE TRIGGER update_product_sold_count_delete
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET sold_count = GREATEST(0, sold_count - OLD.quantity)
    WHERE id = OLD.product_id;
END //

DELIMITER ;

-- ========================================
-- PROCEDURE: AddToCart (bỏ DEFINER)
-- ========================================
DELIMITER //

CREATE PROCEDURE AddToCart(
    IN p_user_id INT,
    IN p_product_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE v_stock INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT stock_quantity INTO v_stock FROM products WHERE id = p_product_id FOR UPDATE;

    IF v_stock < p_quantity THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Not enough stock.';
    END IF;

    INSERT INTO cart_items (user_id, product_id, quantity)
    VALUES (p_user_id, p_product_id, p_quantity)
    ON DUPLICATE KEY UPDATE 
        quantity = LEAST(quantity + VALUES(quantity), v_stock),
        updated_at = CURRENT_TIMESTAMP;

    COMMIT;
END //

DELIMITER ;

-- ========================================
-- VIEW: cart_details (bỏ DEFINER)
-- ========================================
CREATE OR REPLACE VIEW cart_details AS
SELECT 
    ci.id AS cart_item_id,
    ci.user_id,
    ci.product_id,
    p.name AS product_name,
    p.price,
    p.main_image_url,
    ci.quantity,
    (p.price * ci.quantity) AS subtotal,
    p.stock_quantity,
    u.name AS seller_name
FROM cart_items ci
JOIN products p ON ci.product_id = p.id
JOIN users u ON p.seller_id = u.id;

-- ========================================
-- VIEW: order_details (bỏ DEFINER)
-- ========================================
CREATE OR REPLACE VIEW order_details AS
SELECT 
    o.id AS order_id,
    o.user_id,
    u.name AS user_name,
    o.status,
    o.total_amount,
    o.created_at AS order_date,
    oi.product_id,
    p.name AS product_name,
    oi.quantity,
    oi.price_at_purchase,
    oi.total_price,
    seller.name AS seller_name,
    a.recipient_name,
    CONCAT(a.line1, ', ', a.city, ', ', a.country) AS shipping_address
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
JOIN users seller ON oi.seller_id = seller.id
LEFT JOIN addresses a ON o.shipping_address_id = a.id;
