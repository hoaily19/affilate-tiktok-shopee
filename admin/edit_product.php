<?php
/**
 * Trang sửa sản phẩm
 */
require_once '../includes/functions.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$successMsg = '';
$errorMsg = '';

// Lấy ID sản phẩm
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// Lấy thông tin sản phẩm
$product = getProduct($conn, $productId);
if (!$product) {
    header('Location: products.php');
    exit;
}

// Lấy danh mục
$categories = getCategories($conn);

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price' => floatval($_POST['price'] ?? 0),
        'original_price' => floatval($_POST['original_price'] ?? 0),
        'discount' => intval($_POST['discount'] ?? 0),
        'image' => $_POST['image'] ?? '',
        'images' => json_decode($_POST['images_json'] ?? '[]', true) ?: [],
        'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
        'status' => $_POST['status'] ?? 'active'
    ];

    if (updateProduct($conn, $productId, $data)) {
        $successMsg = 'Cập nhật sản phẩm thành công!';
        // Reload product data
        $product = getProduct($conn, $productId);
    } else {
        $errorMsg = 'Có lỗi khi cập nhật sản phẩm.';
    }
}

$images = json_decode($product['images'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../icon.png" type="image/png">
    <link rel="apple-touch-icon" href="../icon.png">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="admin-header-content">
                <a href="../" class="logo">
                    <img src="../logo.png" alt="" class="admin-logo-img" width="38" height="38">
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                <nav class="admin-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a>
                    <a href="add_product.php"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</a>
                    <a href="settings.php"><i class="fas fa-link"></i> Cài đặt trang</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="admin-main">
        <div class="container">
            <div class="admin-page-header">
                <h1><i class="fas fa-edit"></i> Sửa sản phẩm</h1>
                <p><?php echo htmlspecialchars($product['name']); ?></p>
            </div>

            <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMsg); ?>
                <a href="products.php" class="btn-link">Quay lại danh sách</a>
            </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-box-open"></i> Thông tin sản phẩm</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="images_json" value="<?php echo htmlspecialchars(json_encode($images)); ?>">

                        <div class="form-row">
                            <!-- Hình ảnh -->
                            <div class="form-group form-group-image">
                                <label>Hình ảnh</label>
                                <div class="product-image-preview">
                                    <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Preview" id="previewImage">
                                    <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                            </div>

                            <!-- Thông tin -->
                            <div class="form-group-info">
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Tên sản phẩm</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                </div>

                                <div class="form-row-half">
                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Giá bán</label>
                                        <div class="input-with-suffix">
                                            <input type="number" id="price" name="price"
                                                   value="<?php echo $product['price']; ?>" min="0" step="1000">
                                            <span class="suffix">đ</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Giá gốc</label>
                                        <div class="input-with-suffix">
                                            <input type="number" id="original_price" name="original_price"
                                                   value="<?php echo $product['original_price']; ?>" min="0" step="1000">
                                            <span class="suffix">đ</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row-half">
                                    <div class="form-group">
                                        <label><i class="fas fa-percent"></i> Giảm giá (%)</label>
                                        <input type="number" id="discount" name="discount"
                                               value="<?php echo $product['discount']; ?>" min="0" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-toggle-on"></i> Trạng thái</label>
                                        <select name="status">
                                            <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Tạm ẩn</option>
                                            <option value="sold_out" <?php echo $product['status'] === 'sold_out' ? 'selected' : ''; ?>>Hết hàng</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Mô tả</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-folder"></i> Danh mục</label>
                            <select name="category_id">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Link info -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                            <h4 style="margin-bottom: 10px;"><i class="fas fa-link"></i> Thông tin link</h4>
                            <p><strong>Nền tảng:</strong> <?php echo ucfirst($product['platform']); ?></p>
                            <p><strong>Link gốc:</strong> <a href="<?php echo htmlspecialchars($product['source_url']); ?>" target="_blank"><?php echo htmlspecialchars($product['source_url']); ?></a></p>
                            <p><strong>Lượt xem:</strong> <?php echo number_format($product['views']); ?></p>
                            <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>
                            <a href="products.php" class="btn-cancel">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Quản lý sản phẩm.</p>
        </div>
    </footer>

    <script>
        document.getElementById('price')?.addEventListener('input', calcDiscount);
        document.getElementById('original_price')?.addEventListener('input', calcDiscount);

        function calcDiscount() {
            const price = parseFloat(document.getElementById('price')?.value) || 0;
            const original = parseFloat(document.getElementById('original_price')?.value) || 0;
            if (original > 0 && price > 0) {
                const discount = Math.round((1 - price / original) * 100);
                document.getElementById('discount').value = Math.max(0, Math.min(99, discount));
            }
        }
    </script>
</body>
</html>
