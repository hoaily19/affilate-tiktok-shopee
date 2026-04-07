-- Database cho Affiliate Shop TikTok/Shopee (không dùng API)

CREATE DATABASE IF NOT EXISTS tiktok_affiliate
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tiktok_affiliate;

-- Bảng danh mục sản phẩm
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu danh mục
INSERT IGNORE INTO categories (id, name, slug, sort_order) VALUES
(1, 'Thời trang', 'thoi-trang', 1),
(2, 'Làm đẹp', 'lam-dep', 2),
(3, 'Điện tử', 'dien-tu', 3),
(4, 'Nhà cửa', 'nha-cua', 4),
(5, 'Sức khỏe', 'suc-khoe', 5),
(6, 'Thể thao', 'the-thao', 6),
(99, 'Khác', 'khac', 99);

-- Bảng sản phẩm - lưu trữ link gốc và link affiliate
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(20) DEFAULT 'other' COMMENT 'tiktok, shopee, other',
    external_id VARCHAR(128) DEFAULT NULL COMMENT 'ID sản phẩm từ nền tảng',
    source_url TEXT NOT NULL COMMENT 'Link người dùng dán (nguồn)',
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    original_price DECIMAL(15,2) DEFAULT 0,
    discount INT DEFAULT 0,
    image TEXT DEFAULT NULL,
    images TEXT DEFAULT NULL COMMENT 'JSON array of images',
    affiliate_link TEXT DEFAULT NULL COMMENT 'Link mua hàng (có thể = source_url)',
    category_id INT DEFAULT NULL,
    status ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
    views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_platform (platform),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_external_id (external_id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng log click
CREATE TABLE IF NOT EXISTS clicks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    referer TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng người dùng — quyền quản trị qua cột role (admin / user)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tài khoản admin mặc định (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, role) VALUES
(1, 'admin', NULL, '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQbLgT4rM4C8Xq7VXrRp9OQv3kXCWq', 'admin');
