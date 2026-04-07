<?php
/**
 * Trang chuyển hướng - Ghi log click và chuyển đến trang mua hàng
 */
require_once 'config/config.php';
require_once 'db/connect.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDB();

if ($conn && !$conn->connect_error) {
    // Lấy thông tin sản phẩm
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        // Lấy URL mua hàng (ưu tiên affiliate_link, không có thì dùng source_url)
        $redirectUrl = $product['affiliate_link'] ?: $product['source_url'];

        if (!empty($redirectUrl)) {
            // Ghi log click
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ref = $_SERVER['HTTP_REFERER'] ?? '';

            $ins = $conn->prepare("INSERT INTO clicks (product_id, ip_address, user_agent, referer, created_at) VALUES (?, ?, ?, ?, NOW())");
            $ins->bind_param('isss', $productId, $ip, $ua, $ref);
            $ins->execute();

            // Tăng lượt xem
            $conn->query("UPDATE products SET views = views + 1 WHERE id = $productId");

            // Chuyển hướng
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

// Nếu không tìm thấy sản phẩm
header('Location: index.php');
exit;
