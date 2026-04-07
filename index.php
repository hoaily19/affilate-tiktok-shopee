<?php
/**
 * Trang chủ - Hiển thị danh sách sản phẩm
 */
require_once 'config/config.php';
require_once 'db/connect.php';
require_once 'includes/functions.php';

$conn = getDB();

// Lấy tham số lọc
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$pagerExtra = [];
if ($categoryId) {
    $pagerExtra['category'] = $categoryId;
}
if ($search !== '') {
    $pagerExtra['search'] = $search;
}

// Lấy sản phẩm
$products = getProducts($conn, $limit, $offset, $categoryId, $search);
$productsList = [];
if ($products) {
    while ($row = $products->fetch_assoc()) {
        $productsList[] = $row;
    }
}

// Lấy danh mục
$categories = getCategories($conn);

// Đếm tổng sản phẩm cho pagination
$where = "WHERE status = 'active'";
$countParams = [];
if ($categoryId) {
    $where .= " AND category_id = ?";
    $countParams[] = $categoryId;
}
if ($search !== '') {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}

$countSql = "SELECT COUNT(*) as total FROM products $where";
$stmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $types = str_repeat('s', count($countParams));
    $stmt->bind_param($types, ...$countParams);
}
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">
                <i class="fab fa-tiktok"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
            <button type="button" class="nav-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="main-nav">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>
            <nav id="main-nav" class="main-nav" aria-label="Menu chính">
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-pill nav-pill--active"><i class="fas fa-house" aria-hidden="true"></i> Trang chủ</a></li>
                    <li><a href="admin/index.php" class="nav-pill"><i class="fas fa-user-shield" aria-hidden="true"></i> Quản trị</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Khám phá sản phẩm TikTok Shop & Shopee</h1>
            <p>Hàng ngàn sản phẩm chất lượng với giá tốt nhất từ các gian hàng uy tín</p>
        </div>
    </section>

    <!-- Filter Bar -->
    <section class="filter-bar">
        <div class="container">
            <div class="category-tabs">
                <a href="index.php" class="category-tab <?php echo !$categoryId ? 'active' : ''; ?>">
                    Tất cả
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat['id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
                   class="category-tab <?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <form method="GET" class="search-box">
                <?php if ($categoryId): ?>
                <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </section>

    <!-- Products -->
    <section class="products-section">
        <div class="container">
            <?php if (empty($productsList)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>Không tìm thấy sản phẩm</h3>
                <p>Hãy thử tìm kiếm với từ khóa khác hoặc chọn danh mục khác.</p>
            </div>
            <?php else: ?>
            <?php
                $shownFrom = $totalProducts > 0 ? $offset + 1 : 0;
                $shownTo = min($offset + count($productsList), $totalProducts);
            ?>
            <div class="products-toolbar">
                <div class="products-toolbar__left">
                    <span class="products-toolbar__perpage" title="Số sản phẩm mỗi trang">15<span class="products-toolbar__perpage-sub">/trang</span></span>
                    <p class="products-toolbar__meta">
                        Hiển thị <strong><?php echo number_format($shownFrom); ?>–<?php echo number_format($shownTo); ?></strong>
                        trong tổng <strong><?php echo number_format($totalProducts); ?></strong> sản phẩm
                    </p>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="products-toolbar__pager">
                    <?php renderShopPagination($page, $totalPages, $pagerExtra, 'pagination--toolbar'); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="products-grid products-grid--affiliate">
                <?php foreach ($productsList as $idx => $product): ?>
                <?php
                    $images = json_decode($product['images'] ?? '[]', true) ?: [];
                    $mainImage = $product['image'] ?: ($images[0] ?? '');
                    [$btnClass, $btnLabel] = match ($product['platform']) {
                        'shopee' => ['affiliate-card__btn affiliate-card__btn--shopee', 'Mua ngay '],
                        'tiktok' => ['affiliate-card__btn affiliate-card__btn--tiktok', 'Mua ngay '],
                        default => ['affiliate-card__btn affiliate-card__btn--default', 'Mua ngay'],
                    };
                ?>
                <article class="affiliate-card" style="--card-i: <?php echo (int) $idx; ?>">
                    <a href="buy.php?id=<?php echo (int) $product['id']; ?>" class="affiliate-card__link" target="_blank" rel="noopener noreferrer">
                    <?php if ($mainImage): ?>
                    <img class="affiliate-card__img" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" width="400" height="400">
                    <?php else: ?>
                    <div class="affiliate-card__img affiliate-card__img--placeholder" role="img" aria-label="Không có ảnh"></div>
                    <?php endif; ?>
                    <h3 class="affiliate-card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    </a>
                    <?php if ((float) $product['price'] > 0): ?>
                    <p class="affiliate-card__price">
                        <span class="affiliate-card__price-current"><?php echo formatPrice($product['price']); ?></span>
                        <?php if ((float) $product['original_price'] > (float) $product['price']): ?>
                        <span class="affiliate-card__price-old"><?php echo formatPrice($product['original_price']); ?></span>
                        <?php endif; ?>
                        <?php if ($product['discount'] > 0): ?>
                        <span class="affiliate-card__badge">-<?php echo (int) $product['discount']; ?>%</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($product['category_name'])): ?>
                    <p class="affiliate-card__meta"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <?php endif; ?>
                    <a href="buy.php?id=<?php echo (int) $product['id']; ?>" class="<?php echo $btnClass; ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($btnLabel); ?></a>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrap">
                <?php renderShopPagination($page, $totalPages, $pagerExtra, 'pagination--bottom'); ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Mua sắm thông minh cùng TikTok & Shopee.</p>
        </div>
    </footer>
    <script src="assets/js/main.js" defer></script>
</body>
</html>
