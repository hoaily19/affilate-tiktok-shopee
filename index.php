<?php
/**
 * Trang chủ - Hiển thị danh sách sản phẩm
 */
require_once 'config/config.php';
require_once 'db/connect.php';
require_once 'includes/functions.php';

$conn = getDB();

// ?category=123 → /slug-danh-mục
if ($conn && !$conn->connect_error && isset($_GET['category']) && !isset($_GET['category_slug'])) {
    $redirectId = (int) $_GET['category'];
    if ($redirectId > 0) {
        $st = $conn->prepare('SELECT slug FROM categories WHERE id = ? LIMIT 1');
        $st->bind_param('i', $redirectId);
        $st->execute();
        $catRow = $st->get_result()->fetch_assoc();
        if ($catRow && $catRow['slug'] !== '') {
            $rq = [];
            if (!empty($_GET['search']) && trim((string) $_GET['search']) !== '') {
                $rq['search'] = trim((string) $_GET['search']);
            }
            if (!empty($_GET['page']) && (int) $_GET['page'] > 1) {
                $rq['page'] = (int) $_GET['page'];
            }
            header('Location: ' . shop_category_path_url($catRow['slug'], $rq), true, 302);
            exit;
        }
    }
}

$categorySlug = shop_category_slug_from_request();

$categoryId = null;
$categoryNotFound = false;
if ($categorySlug !== '') {
    $resolved = getCategoryIdBySlug($conn, $categorySlug);
    if ($resolved === null) {
        $categoryNotFound = true;
        $categoryId = -1;
        http_response_code(404);
    } else {
        $categoryId = $resolved;
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$pagerExtra = [];
if ($categorySlug !== '' && !$categoryNotFound) {
    $pagerExtra['category_slug'] = $categorySlug;
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

// Link kênh TikTok / Shopee (cấu hình trong admin → Cài đặt trang)
$heroUrls = getHeroChannelUrls($conn);

// Đếm tổng sản phẩm cho pagination
$where = "WHERE status = 'active'";
$countParams = [];
if ($categoryId !== null && $categoryId > 0) {
    $where .= " AND category_id = ?";
    $countParams[] = $categoryId;
} elseif ($categoryId === -1) {
    $where .= " AND 1=0";
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
    $types = '';
    if ($categoryId !== null && $categoryId > 0) {
        $types .= 'i';
    }
    if ($search !== '') {
        $types .= 'ss';
    }
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
    <?php
    $favPath = shop_favicon_url();
    $favBust = shop_favicon_cache_buster();
    $favFull = htmlspecialchars(shop_favicon_absolute_url(), ENT_QUOTES, 'UTF-8') . '?v=' . htmlspecialchars($favBust, ENT_QUOTES, 'UTF-8');
    $favRel = htmlspecialchars($favPath, ENT_QUOTES, 'UTF-8') . '?v=' . htmlspecialchars($favBust, ENT_QUOTES, 'UTF-8');
    $log = shop_logo_url();
    ?>
    <link rel="icon" href="<?php echo $favFull; ?>" type="image/png">
    <link rel="shortcut icon" href="<?php echo $favRel; ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo $favFull; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f0f1a" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f1f3f5" media="(prefers-color-scheme: light)">
    <title><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(shop_asset_url('assets/css/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
    function googleTranslateInit() {
        if (typeof google === 'undefined' || !google.translate) return;
        var el = document.getElementById('google_translate_element');
        if (!el) return;
        try {
            new google.translate.TranslateElement({
                pageLanguage: 'vi',
                includedLanguages: 'vi,en,zh-CN,ko,ja',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        } catch (e) {}
    }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateInit"></script>
</head>
<body>
    <!-- Phải có sẵn trước khi callback Google Translate chạy -->
    <div id="google_translate_element" class="google-translate-host" aria-hidden="true"></div>
    <!-- Lớp mờ khi mở menu mobile -->
    <div class="nav-backdrop" id="nav-backdrop" aria-hidden="true"></div>

    <!-- Header -->
    <header class="header">
        <div class="container header-inner">
            <a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>" class="logo">
                <img src="<?php echo htmlspecialchars($log, ENT_QUOTES, 'UTF-8'); ?>" class="logo__img" width="44" height="44" alt="<?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="logo__text"><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>

            <!-- Nút bên phải: translate -->
            <div class="header-bar-right">
                <div class="google-translate-bar" id="gt-bar" aria-label="Chuyển ngôn ngữ">
                    <span class="google-translate-bar__label">Ngôn ngữ</span>
                    <select class="google-translate-bar__sel" id="gt-select" aria-label="Chọn ngôn ngữ">
                        <option value="vi">Tiếng Việt</option>
                        <option value="en">English</option>
                        <option value="zh-CN">中文</option>
                        <option value="ko">한국어</option>
                        <option value="ja">日本語</option>
                    </select>
                </div>
                <button type="button" class="nav-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="main-nav">
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                </button>
            </div>

            <nav id="main-nav" class="main-nav" aria-label="Menu chính">
                <!-- Mobile: panel trượt (kiểu Lái xe hộ) -->
                <div class="main-nav__sheet">
                    <div class="main-nav__sheet-head">
                        <a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>" class="main-nav__brand">
                            <img src="<?php echo htmlspecialchars($log, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="main-nav__brand-logo" width="96" height="96" decoding="async">
                            <span class="main-nav__brand-text">
                                <strong><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars(SITE_TAGLINE, ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </a>
                        <button type="button" class="main-nav__close" aria-label="Đóng menu">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="main-nav__sheet-label">Điều hướng</p>
                    <ul class="main-nav__sheet-list">
                        <li>
                            <a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>" class="main-nav__sheet-link">
                                <i class="fas fa-house main-nav__icon main-nav__icon--yellow" aria-hidden="true"></i>
                                Trang chủ
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>#products" class="main-nav__sheet-link main-nav__sheet-link--accent">
                                <i class="fas fa-bag-shopping main-nav__icon main-nav__icon--yellow" aria-hidden="true"></i>
                                Sản phẩm
                            </a>
                        </li>
                    </ul>

                    <?php
                    $phonesNav = SITE_PHONES;
                    if (!is_array($phonesNav)) {
                        $phonesNav = $phonesNav !== '' ? [$phonesNav] : [];
                    }
                    $navHasLinks = ($heroUrls['tiktok'] !== '' || $heroUrls['shopee'] !== '' || count($phonesNav) > 0);
                    ?>
                    <?php if ($navHasLinks): ?>
                    <p class="main-nav__sheet-label">Liên kết</p>
                    <ul class="main-nav__sheet-list">
                        <?php if ($heroUrls['tiktok'] !== ''): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($heroUrls['tiktok'], ENT_QUOTES, 'UTF-8'); ?>" class="main-nav__sheet-link" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-tiktok main-nav__icon main-nav__icon--tiktok" aria-hidden="true"></i>
                                Kênh TikTok
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($heroUrls['shopee'] !== ''): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($heroUrls['shopee'], ENT_QUOTES, 'UTF-8'); ?>" class="main-nav__sheet-link" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-store main-nav__icon main-nav__icon--shopee" aria-hidden="true"></i>
                                Gian hàng Shopee
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php
                        foreach ($phonesNav as $phone):
                            $tel = preg_replace('/\s+/', '', $phone);
                        ?>
                        <li>
                            <a href="tel:<?php echo htmlspecialchars($tel, ENT_QUOTES, 'UTF-8'); ?>" class="main-nav__sheet-link">
                                <i class="fas fa-phone main-nav__icon main-nav__icon--phone" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <!-- Desktop: pill trong header -->
                <ul class="nav-menu nav-menu--desktop">
                    <li><a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>" class="nav-pill nav-pill--active"><i class="fas fa-house" aria-hidden="true"></i> Trang chủ</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Khám phá sản phẩm TikTok Shop & Shopee</h1>
            <p>Hàng ngàn sản phẩm chất lượng với giá tốt nhất từ các gian hàng uy tín</p>
            <?php if ($heroUrls['tiktok'] !== '' || $heroUrls['shopee'] !== ''): ?>
            <div class="hero__actions">
                <?php if ($heroUrls['tiktok'] !== ''): ?>
                <a href="<?php echo htmlspecialchars($heroUrls['tiktok'], ENT_QUOTES, 'UTF-8'); ?>" class="hero__btn hero__btn--tiktok" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-tiktok" aria-hidden="true"></i>
                    <span>Kênh TikTok</span>
                </a>
                <?php endif; ?>
                <?php if ($heroUrls['shopee'] !== ''): ?>
                <a href="<?php echo htmlspecialchars($heroUrls['shopee'], ENT_QUOTES, 'UTF-8'); ?>" class="hero__btn hero__btn--shopee" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    <span>Gian hàng Shopee</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Filter Bar -->
    <section class="filter-bar">
        <div class="container">
            <div class="category-tabs">
                <a href="<?php echo htmlspecialchars(shop_build_url($search !== '' ? ['search' => $search] : []), ENT_QUOTES, 'UTF-8'); ?>" class="category-tab <?php echo ($categorySlug === '' || $categoryNotFound) ? 'active' : ''; ?>">
                    Tất cả
                </a>
                <?php foreach ($categories as $cat): ?>
                <?php
                    $catPathQ = [];
                    if ($search !== '') {
                        $catPathQ['search'] = $search;
                    }
                    $catHref = shop_category_path_url((string) $cat['slug'], $catPathQ);
                ?>
                <a href="<?php echo htmlspecialchars($catHref, ENT_QUOTES, 'UTF-8'); ?>"
                   class="category-tab <?php echo ($categoryId > 0 && (int) $cat['id'] === $categoryId) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <form method="GET" class="search-box" action="<?php echo htmlspecialchars(($categorySlug !== '' && !$categoryNotFound) ? shop_category_path_url($categorySlug, []) : shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </section>

    <!-- Products -->
    <section class="products-section" id="products">
        <div class="container">
            <?php if (empty($productsList)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <?php if (!empty($categoryNotFound)): ?>
                <h3>Không có danh mục này</h3>
                <p>Đường dẫn không đúng hoặc danh mục đã thay đổi. <a href="<?php echo htmlspecialchars(shop_home_url(), ENT_QUOTES, 'UTF-8'); ?>">Về trang chủ</a></p>
                <?php else: ?>
                <h3>Không tìm thấy sản phẩm</h3>
                <p>Hãy thử tìm kiếm với từ khóa khác hoặc chọn danh mục khác.</p>
                <?php endif; ?>
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
                    <a href="<?php echo htmlspecialchars(shop_asset_url('buy.php'), ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo (int) $product['id']; ?>" class="affiliate-card__link" target="_blank" rel="noopener noreferrer">
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
                    <a href="<?php echo htmlspecialchars(shop_asset_url('buy.php'), ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo (int) $product['id']; ?>" class="<?php echo $btnClass; ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($btnLabel); ?></a>
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

    <script src="<?php echo htmlspecialchars(shop_asset_url('assets/js/main.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</body>
</html>
