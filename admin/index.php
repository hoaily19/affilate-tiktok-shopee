<?php
/**
 * Trang quản trị - Dashboard
 */
require_once 'auth.php';
requireAdmin();

$conn = getDB();

// Thống kê
$stats = [
    'total_products' => 0,
    'total_clicks' => 0,
    'active_products' => 0,
    'today_clicks' => 0
];

if ($conn && !$conn->connect_error) {
    $conn->query("SET time_zone = '+07:00'");

    $result = $conn->query("SELECT COUNT(*) as cnt FROM products");
    if ($row = $result->fetch_assoc()) {
        $stats['total_products'] = $row['cnt'];
    }

    $result = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE status = 'active'");
    if ($row = $result->fetch_assoc()) {
        $stats['active_products'] = $row['cnt'];
    }

    $result = $conn->query("SELECT COUNT(*) as cnt FROM clicks");
    if ($row = $result->fetch_assoc()) {
        $stats['total_clicks'] = $row['cnt'];
    }

    $result = $conn->query("SELECT COUNT(*) as cnt FROM clicks WHERE DATE(created_at) = CURDATE()");
    if ($row = $result->fetch_assoc()) {
        $stats['today_clicks'] = $row['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
                    <a href="index.php" class="active"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a>
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
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Chào mừng đến với trang quản trị</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="fas fa-box"></i></div>
                    <div class="stat-card__body">
                        <h3>Tổng sản phẩm</h3>
                        <div class="value"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-card__body">
                        <h3>Sản phẩm đang hoạt động</h3>
                        <div class="value"><?php echo number_format($stats['active_products']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="fas fa-mouse-pointer"></i></div>
                    <div class="stat-card__body">
                        <h3>Tổng lượt click</h3>
                        <div class="value"><?php echo number_format($stats['total_clicks']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-card__body">
                        <h3>Click hôm nay</h3>
                        <div class="value"><?php echo number_format($stats['today_clicks']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> Thao tác nhanh</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="add_product.php" class="btn-save">
                            <i class="fas fa-plus"></i> Thêm sản phẩm mới
                        </a>
                        <a href="products.php" class="btn-cancel">
                            <i class="fas fa-list"></i> Danh sách sản phẩm
                        </a>
                        <a href="../index.php" target="_blank" rel="noopener" class="btn-outline">
                            <i class="fas fa-external-link-alt"></i> Xem website
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            <?php
            $recentProducts = $conn->query("SELECT p.*, c.name as category_name
                                            FROM products p
                                            LEFT JOIN categories c ON p.category_id = c.id
                                            ORDER BY p.created_at DESC LIMIT 5");
            ?>
            <?php if ($recentProducts && $recentProducts->num_rows > 0): ?>
            <div class="admin-card" style="margin-top: 25px;">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Sản phẩm mới thêm gần đây</h2>
                </div>
                <div class="card-body card-body--flush">
                    <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá</th>
                                <th>Danh mục</th>
                                <th>Ngày thêm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $recentProducts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($p['image']): ?>
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" class="product-thumb" alt="">
                                    <?php else: ?>
                                    <div class="thumb-placeholder" aria-hidden="true">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
