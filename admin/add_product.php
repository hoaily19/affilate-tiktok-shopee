<?php
/**
 * Trang thêm sản phẩm từ link (TikTok Shop, Shopee)
 */
require_once '../includes/functions.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$successMsg = '';
$errorMsg = '';
$productData = null;
/** Sau khi POST form Shopee thủ công (thành công hoặc lỗi), giữ tab đúng */
$forceAddMode = null;

/**
 * Kiểm tra URL có phải domain Shopee (link sản phẩm / affiliate)
 */
function isLikelyShopeeProductUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return false;
    }
    return (bool) preg_match('/(^|\.)shopee\./i', $host);
}

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fetch_product'])) {
        // Trích xuất thông tin từ link
        $url = trim($_POST['product_url'] ?? '');

        if (empty($url)) {
            $errorMsg = 'Vui lòng nhập link sản phẩm.';
        } else {
            $productData = fetchProductInfo($url);

            if (!$productData['success']) {
                $errorMsg = 'Không thể trích xuất thông tin từ link này.';
                $productData = null;
            }
        }
    }
    elseif (isset($_POST['save_product'])) {
        // Lưu sản phẩm
        $url = trim($_POST['product_url'] ?? '');

        if (urlExists($conn, $url)) {
            $errorMsg = 'Link này đã được thêm trước đó.';
        } else {
            $data = [
                'url' => $url,
                'affiliate_link' => trim($_POST['affiliate_link'] ?? '') ?: $url,
                'platform' => $_POST['platform'] ?? 'other',
                'external_id' => $_POST['external_id'] ?? '',
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => floatval($_POST['price'] ?? 0),
                'original_price' => floatval($_POST['original_price'] ?? 0),
                'discount' => intval($_POST['discount'] ?? 0),
                'image' => $_POST['image'] ?? '',
                'images' => json_decode($_POST['images_json'] ?? '[]', true) ?: [],
                'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null
            ];

            $productId = saveProduct($conn, $data);

            if ($productId) {
                $successMsg = 'Thêm sản phẩm thành công! ID: ' . $productId;
                $productData = null;
            } else {
                $errorMsg = 'Có lỗi khi lưu sản phẩm.';
            }
        }
    }
    elseif (isset($_POST['save_manual_shopee'])) {
        $forceAddMode = 'manual_shopee';
        $shopeeUrl = trim($_POST['manual_shopee_url'] ?? '');
        $name = trim($_POST['manual_shopee_name'] ?? '');
        $price = isset($_POST['manual_shopee_price']) ? (float) $_POST['manual_shopee_price'] : 0;
        $image = trim($_POST['manual_shopee_image'] ?? '');
        $description = trim($_POST['manual_shopee_description'] ?? '');
        $originalPrice = isset($_POST['manual_shopee_original_price']) ? (float) $_POST['manual_shopee_original_price'] : 0;
        $discount = isset($_POST['manual_shopee_discount']) ? (int) $_POST['manual_shopee_discount'] : 0;

        if ($shopeeUrl === '' || $name === '' || $image === '') {
            $errorMsg = 'Vui lòng nhập đủ: link Shopee, tên sản phẩm và URL ảnh.';
        } elseif (!isLikelyShopeeProductUrl($shopeeUrl)) {
            $errorMsg = 'Link sản phẩm phải là link Shopee (ví dụ shopee.vn hoặc s.shopee.vn).';
        } elseif (!filter_var($image, FILTER_VALIDATE_URL)) {
            $errorMsg = 'URL ảnh không hợp lệ (cần bắt đầu bằng http:// hoặc https://).';
        } elseif ($price < 0) {
            $errorMsg = 'Giá bán không hợp lệ.';
        } elseif (urlExists($conn, $shopeeUrl)) {
            $errorMsg = 'Link Shopee này đã được thêm trước đó.';
        } else {
            $data = [
                'url' => $shopeeUrl,
                'affiliate_link' => $shopeeUrl,
                'platform' => 'shopee',
                'external_id' => 'manual-' . str_replace('.', '', uniqid('', true)),
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'original_price' => $originalPrice,
                'discount' => max(0, min(100, $discount)),
                'image' => $image,
                'images' => [$image],
                'category_id' => isset($_POST['manual_category_id']) ? (int) $_POST['manual_category_id'] : null,
            ];

            $productId = saveProduct($conn, $data);
            if ($productId) {
                $successMsg = 'Đã thêm sản phẩm Shopee thủ công! ID: ' . $productId;
            } else {
                $errorMsg = 'Có lỗi khi lưu sản phẩm.';
            }
        }
    }
}

// Lấy danh mục
$categories = getCategories($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm - <?php echo SITE_NAME; ?></title>
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
                    <a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a>
                    <a href="add_product.php" class="active"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="admin-main">
        <div class="container">
            <div class="admin-page-header">
                <h1><i class="fas fa-plus-circle"></i> Thêm sản phẩm</h1>
                <p>Chọn cách thêm: trích xuất từ link TikTok/Shopee, hoặc nhập tay sản phẩm Shopee (ảnh, tên, giá, link).</p>
            </div>

            <div class="admin-card add-mode-bar" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 16px 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="add_mode"><i class="fas fa-sliders-h"></i> Chế độ thêm sản phẩm</label>
                        <select id="add_mode" name="add_mode" class="add-mode-select">
                            <option value="auto" <?php echo ($forceAddMode !== 'manual_shopee') ? 'selected' : ''; ?>>Trích xuất từ link (TikTok Shop / Shopee)</option>
                            <option value="manual_shopee" <?php echo ($forceAddMode === 'manual_shopee') ? 'selected' : ''; ?>>Thêm sản phẩm Shopee thủ công</option>
                        </select>
                        <span class="form-hint">Shopee thủ công: nhập URL ảnh, tên, giá và link Shopee — hiển thị trên trang chủ, khách bấm sẽ mở link Shopee.</span>
                    </div>
                </div>
            </div>

            <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMsg); ?>
                <a href="products.php" class="btn-link">Xem danh sách sản phẩm</a>
            </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
            <?php endif; ?>

            <!-- Form nhập link (tự động) -->
            <div class="admin-card add-mode-panel" id="panel_auto" data-mode="auto">
                <div class="card-header">
                    <h2><i class="fas fa-link"></i> Nhập link sản phẩm</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="url-form">
                        <div class="form-group">
                            <label for="product_url">
                                <i class="fas fa-link"></i> Liên kết sản phẩm
                            </label>
                            <input type="url"
                                   id="product_url"
                                   name="product_url"
                                   placeholder="https://www.tiktok.com/shop/... hoặc https://s.shopee.vn/..."
                                   value="<?php echo htmlspecialchars($_POST['product_url'] ?? ''); ?>"
                                   required>
                            <span class="form-hint">Hỗ trợ: TikTok Shop, Shopee (link đầy đủ hoặc link rút gọn)</span>
                        </div>
                        <button type="submit" name="fetch_product" class="btn-fetch">
                            <i class="fas fa-search"></i> Trích xuất thông tin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form Shopee thủ công -->
            <div class="admin-card add-mode-panel" id="panel_manual_shopee" data-mode="manual_shopee" style="display: none;">
                <div class="card-header">
                    <h2><i class="fab fa-shopify" style="color:#ee4d2d;"></i> Thêm sản phẩm Shopee thủ công</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="url-form">
                        <div class="form-group">
                            <label for="manual_shopee_image"><i class="fas fa-image"></i> URL ảnh sản phẩm</label>
                            <input type="url" id="manual_shopee_image" name="manual_shopee_image"
                                   placeholder="https://..."
                                   value="<?php echo htmlspecialchars($_POST['manual_shopee_image'] ?? ''); ?>"
                                   required>
                            <span class="form-hint">Dán đường dẫn ảnh công khai (https). Có thể copy từ Shopee / CDN.</span>
                        </div>
                        <div class="form-group">
                            <label for="manual_shopee_name"><i class="fas fa-tag"></i> Tên sản phẩm</label>
                            <input type="text" id="manual_shopee_name" name="manual_shopee_name"
                                   value="<?php echo htmlspecialchars($_POST['manual_shopee_name'] ?? ''); ?>"
                                   required maxlength="500">
                        </div>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label for="manual_shopee_price"><i class="fas fa-money-bill"></i> Giá bán (đ)</label>
                                <input type="number" id="manual_shopee_price" name="manual_shopee_price"
                                       value="<?php echo htmlspecialchars($_POST['manual_shopee_price'] ?? '0'); ?>"
                                       min="0" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label for="manual_shopee_original_price"><i class="fas fa-tag"></i> Giá gốc (đ)</label>
                                <input type="number" id="manual_shopee_original_price" name="manual_shopee_original_price"
                                       value="<?php echo htmlspecialchars($_POST['manual_shopee_original_price'] ?? '0'); ?>"
                                       min="0" step="1000">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="manual_shopee_discount"><i class="fas fa-percent"></i> Giảm giá (%)</label>
                            <input type="number" id="manual_shopee_discount" name="manual_shopee_discount"
                                   value="<?php echo htmlspecialchars($_POST['manual_shopee_discount'] ?? '0'); ?>"
                                   min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label for="manual_shopee_url"><i class="fas fa-external-link-alt"></i> Link sản phẩm Shopee</label>
                            <input type="url" id="manual_shopee_url" name="manual_shopee_url"
                                   placeholder="https://shopee.vn/... hoặc https://s.shopee.vn/..."
                                   value="<?php echo htmlspecialchars($_POST['manual_shopee_url'] ?? ''); ?>"
                                   required>
                            <span class="form-hint">Khách bấm &quot;Mua trên Shopee&quot; trên trang chủ sẽ mở link này.</span>
                        </div>
                        <div class="form-group">
                            <label for="manual_shopee_description"><i class="fas fa-align-left"></i> Mô tả (tùy chọn)</label>
                            <textarea id="manual_shopee_description" name="manual_shopee_description" rows="3"><?php echo htmlspecialchars($_POST['manual_shopee_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="manual_category_id"><i class="fas fa-folder"></i> Danh mục</label>
                            <select id="manual_category_id" name="manual_category_id">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['manual_category_id']) && (string)$_POST['manual_category_id'] === (string)$cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actions" style="margin-top: 8px;">
                            <button type="submit" name="save_manual_shopee" class="btn-save">
                                <i class="fas fa-save"></i> Lưu sản phẩm Shopee
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Form thông tin sản phẩm -->
            <?php if ($productData): ?>
            <div class="admin-card add-mode-panel" id="panel_preview" data-mode="auto" style="margin-top: 25px;">
                <div class="card-header">
                    <h2><i class="fas fa-box-open"></i> Thông tin sản phẩm</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="product_url" value="<?php echo htmlspecialchars($productData['url']); ?>">
                        <input type="hidden" name="platform" value="<?php echo htmlspecialchars($productData['platform']); ?>">
                        <input type="hidden" name="external_id" value="<?php echo htmlspecialchars($productData['external_id']); ?>">
                        <input type="hidden" name="images_json" value="<?php echo htmlspecialchars(json_encode($productData['images'])); ?>">
                        <input type="hidden" name="affiliate_link" value="<?php echo htmlspecialchars($productData['affiliate_link'] ?? ''); ?>">

                        <div class="form-row">
                            <!-- Hình ảnh -->
                            <div class="form-group form-group-image">
                                <label>Hình ảnh</label>
                                <div class="product-image-preview">
                                    <?php if ($productData['image']): ?>
                                    <img src="<?php echo htmlspecialchars($productData['image']); ?>" alt="Preview" id="previewImage">
                                    <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($productData['image']); ?>">
                            </div>

                            <!-- Thông tin -->
                            <div class="form-group-info">
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Tên sản phẩm</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($productData['name']); ?>" required>
                                </div>

                                <div class="form-row-half">
                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Giá bán</label>
                                        <div class="input-with-suffix">
                                            <input type="number" id="price" name="price"
                                                   value="<?php echo $productData['price']; ?>" min="0" step="1000">
                                            <span class="suffix">đ</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Giá gốc</label>
                                        <div class="input-with-suffix">
                                            <input type="number" id="original_price" name="original_price"
                                                   value="<?php echo $productData['original_price']; ?>" min="0" step="1000">
                                            <span class="suffix">đ</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-percent"></i> Giảm giá (%)</label>
                                    <input type="number" id="discount" name="discount"
                                           value="<?php echo $productData['discount']; ?>" min="0" max="100">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Mô tả</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($productData['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-folder"></i> Danh mục</label>
                            <select name="category_id">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="save_product" class="btn-save">
                                <i class="fas fa-save"></i> Lưu sản phẩm
                            </button>
                            <a href="add_product.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hướng dẫn -->
            <div class="admin-card add-mode-help" style="margin-top: 25px;">
                <div class="card-header">
                    <h2><i class="fas fa-question-circle"></i> Hướng dẫn</h2>
                </div>
                <div class="card-body">
                    <div class="help-steps help-steps--auto">
                        <div class="help-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Copy link sản phẩm</h3>
                                <p>Từ TikTok Shop hoặc Shopee, sao chép link sản phẩm (link đầy đủ hoặc rút gọn đều được).</p>
                            </div>
                        </div>
                        <div class="help-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Dán link và trích xuất</h3>
                                <p>Dán link và nhấn "Trích xuất thông tin" để tự động lấy dữ liệu sản phẩm.</p>
                            </div>
                        </div>
                        <div class="help-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Kiểm tra và lưu</h3>
                                <p>Kiểm tra thông tin, chỉnh sửa nếu cần và nhấn "Lưu sản phẩm".</p>
                            </div>
                        </div>
                    </div>
                    <div class="help-steps help-steps--manual" style="display: none;">
                        <div class="help-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Sao chép link ảnh &amp; link sản phẩm</h3>
                                <p>Lấy URL ảnh (chuột phải ảnh → copy địa chỉ ảnh) và link sản phẩm Shopee (trang sản phẩm hoặc link affiliate).</p>
                            </div>
                        </div>
                        <div class="help-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Điền tên và giá</h3>
                                <p>Nhập tên, giá hiển thị trên trang chủ; có thể thêm giá gốc và % giảm.</p>
                            </div>
                        </div>
                        <div class="help-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Lưu</h3>
                                <p>Nhấn &quot;Lưu sản phẩm Shopee&quot; — sản phẩm xuất hiện trên trang chủ, khách bấm mua sẽ mở đúng link Shopee.</p>
                            </div>
                        </div>
                    </div>
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
        (function () {
            var modeSel = document.getElementById('add_mode');
            var panelAuto = document.getElementById('panel_auto');
            var panelManual = document.getElementById('panel_manual_shopee');
            var panelPreview = document.getElementById('panel_preview');
            var helpAuto = document.querySelector('.help-steps--auto');
            var helpManual = document.querySelector('.help-steps--manual');

            function setMode(mode) {
                var isAuto = mode === 'auto';
                if (panelAuto) panelAuto.style.display = isAuto ? '' : 'none';
                if (panelManual) panelManual.style.display = isAuto ? 'none' : '';
                if (panelPreview) panelPreview.style.display = isAuto ? '' : 'none';
                if (helpAuto) helpAuto.style.display = isAuto ? '' : 'none';
                if (helpManual) helpManual.style.display = isAuto ? 'none' : '';
            }

            if (modeSel) {
                modeSel.addEventListener('change', function () {
                    setMode(modeSel.value);
                    try {
                        localStorage.setItem('add_product_mode', modeSel.value);
                    } catch (e) {}
                });
                var forceMode = <?php echo $forceAddMode ? json_encode($forceAddMode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>;
                if (forceMode === 'manual_shopee') {
                    modeSel.value = 'manual_shopee';
                } else {
                    var saved = null;
                    try {
                        saved = localStorage.getItem('add_product_mode');
                    } catch (e) {}
                    if (saved === 'manual_shopee' || saved === 'auto') {
                        modeSel.value = saved;
                    }
                }
                setMode(modeSel.value);
            }
        })();

        // Tự động tính % giảm giá
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
