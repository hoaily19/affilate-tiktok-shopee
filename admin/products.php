<?php
/**
 * Trang quản lý sản phẩm
 */
require_once '../includes/functions.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$successMsg = '';
$errorMsg = '';

// Xử lý xóa sản phẩm
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteProduct($conn, $id)) {
        $successMsg = 'Xóa sản phẩm thành công!';
    } else {
        $errorMsg = 'Không thể xóa sản phẩm.';
    }
}

// Phân trang
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Lấy sản phẩm
$products = getProducts($conn, $limit, $offset, null, '', 'all');
$productsList = [];
if ($products) {
    while ($row = $products->fetch_assoc()) {
        $productsList[] = $row;
    }
}

// Đếm tổng
$totalResult = $conn->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Lấy danh mục
$categories = getCategories($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - <?php echo SITE_NAME; ?></title>
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
                <a href="../index.php" class="logo">
                    <i class="fab fa-tiktok"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                <nav class="admin-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a>
                    <a href="add_product.php"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="admin-main">
        <div class="container">
            <div class="admin-page-header">
                <h1><i class="fas fa-box"></i> Quản lý sản phẩm</h1>
                <p>Tổng cộng <?php echo number_format($totalProducts); ?> sản phẩm</p>
            </div>

            <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMsg); ?>
            </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="quick-actions" style="margin-bottom: 20px;">
                <a href="add_product.php" class="btn-save">
                    <i class="fas fa-plus"></i> Thêm sản phẩm mới
                </a>
            </div>

            <!-- Products Table -->
            <div class="admin-card">
                <div class="card-body card-body--flush">
                    <?php if (empty($productsList)): ?>
                    <div class="empty-inbox">
                        <i class="fas fa-inbox"></i>
                        <p>Chưa có sản phẩm nào. <a href="add_product.php">Thêm sản phẩm đầu tiên</a></p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Hình</th>
                                <th>Tên sản phẩm</th>
                                <th>Nền tảng</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Lượt xem</th>
                                <th style="width: 150px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productsList as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" class="product-thumb" alt="">
                                    <?php else: ?>
                                    <div class="thumb-placeholder" aria-hidden="true">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if ($product['category_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $platformClass = match($product['platform']) {
                                        'tiktok' => 'style="color:#fe2c55;"',
                                        'shopee' => 'style="color:#ee4d2d;"',
                                        default => ''
                                    };
                                    ?>
                                    <i class="fab fa-<?php echo $product['platform'] === 'shopee' ? 'shopify' : 'tiktok'; ?>" <?php echo $platformClass; ?>></i>
                                    <?php echo ucfirst($product['platform']); ?>
                                </td>
                                <td>
                                    <strong style="color: var(--primary);"><?php echo formatPrice($product['price']); ?></strong>
                                    <?php if ($product['original_price'] > $product['price']): ?>
                                    <br><small style="text-decoration: line-through; color: #999;"><?php echo formatPrice($product['original_price']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = match($product['status']) {
                                        'active' => 'badge-status--active',
                                        'inactive' => 'badge-status--inactive',
                                        'sold_out' => 'badge-status--sold_out',
                                        default => ''
                                    };
                                    ?>
                                    <span class="badge-status <?php echo $statusBadge; ?>">
                                        <?php echo match($product['status']) {
                                            'active' => 'Hoạt động',
                                            'inactive' => 'Tạm ẩn',
                                            'sold_out' => 'Hết hàng',
                                            default => $product['status']
                                        }; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($product['views']); ?></td>
                                <td class="actions">
                                    <a href="../buy.php?id=<?php echo $product['id']; ?>" target="_blank" rel="noopener" class="btn-edit" title="Mở link mua">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-edit" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn-delete"
                                       onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
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
</body>
</html>
