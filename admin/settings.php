<?php
/**
 * Cài đặt: link kênh TikTok & gian hàng Shopee (nút trên hero trang chủ)
 */
require_once 'auth.php';
requireAdmin();

require_once __DIR__ . '/../includes/functions.php';

$conn = getDB();
$msg = '';
$err = '';

if ($conn && !$conn->connect_error) {
    ensureSiteSettingsTable($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn && !$conn->connect_error) {
    $tik = sanitizeStoreUrl($_POST['tiktok_channel_url'] ?? '');
    $shopee = sanitizeStoreUrl($_POST['shopee_store_url'] ?? '');
    if (setSiteSetting($conn, 'tiktok_channel_url', $tik) && setSiteSetting($conn, 'shopee_store_url', $shopee)) {
        $msg = 'Đã lưu cài đặt.';
    } else {
        $err = 'Không lưu được. Kiểm tra kết nối database.';
    }
}

$tiktokVal = '';
$shopeeVal = '';
if ($conn && !$conn->connect_error) {
    $tiktokVal = htmlspecialchars(getSiteSetting($conn, 'tiktok_channel_url', ''), ENT_QUOTES, 'UTF-8');
    $shopeeVal = htmlspecialchars(getSiteSetting($conn, 'shopee_store_url', ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt trang — <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="../icon.png" type="image/png">
    <link rel="apple-touch-icon" href="../icon.png">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="container">
            <div class="admin-header-content">
                <a href="../" class="logo">
                    <img src="../logo.png" alt="" class="admin-logo-img" width="38" height="38">
                    <span><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <nav class="admin-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a>
                    <a href="add_product.php"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</a>
                    <a href="settings.php" class="active"><i class="fas fa-link"></i> Cài đặt trang</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="container">
            <div class="admin-page-header">
                <h1><i class="fas fa-link"></i> Cài đặt trang chủ</h1>
                <p>Hai nút trên vùng hero (tiêu đề lớn): kênh TikTok và gian hàng Shopee. Để trống thì ẩn nút tương ứng.</p>
            </div>

            <?php if ($msg !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($err !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fab fa-tiktok"></i> &amp; <i class="fas fa-store"></i> Liên kết ngoài</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="settings.php" class="admin-form">
                        <div class="form-group">
                            <label for="tiktok_channel_url">Link kênh TikTok</label>
                            <input type="url" name="tiktok_channel_url" id="tiktok_channel_url" class="form-control"
                                   value="<?php echo $tiktokVal; ?>"
                                   placeholder="https://www.tiktok.com/@tenkenh">
                            <small class="form-hint">Ví dụ: trang profile hoặc kênh TikTok Shop của bạn.</small>
                        </div>
                        <div class="form-group">
                            <label for="shopee_store_url">Link gian hàng Shopee</label>
                            <input type="url" name="shopee_store_url" id="shopee_store_url" class="form-control"
                                   value="<?php echo $shopeeVal; ?>"
                                   placeholder="https://shopee.vn/shop/...">
                            <small class="form-hint">URL cửa hàng trên Shopee (trang shop).</small>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?> — Quản trị</p>
        </div>
    </footer>
</body>
</html>
