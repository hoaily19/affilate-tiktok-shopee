<?php
/**
 * Cấu hình database
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tiktok_affiliate');

/**
 * Cấu hình website
 */
define('SITE_NAME', 'Affiliate Shop');
/** Dòng phụ dưới tên (menu mobile, SEO) */
define('SITE_TAGLINE', 'TikTok Shop & Shopee');
define('SITE_URL', 'http://localhost:8000');

/**
 * Tiền tố đường dẫn shop trên domain (để trống = gốc). Ví dụ cài trong /myshop → '/myshop'
 * Dùng cho link nội bộ khi deploy thư mục con (cần chỉnh router/nginx tương ứng).
 */
define('SHOP_BASE_PATH', '');

/** Liên kết hiển thị trong menu mobile (để # nếu chưa dùng) */
define('SITE_FANPAGE_URL', '#');
define('SITE_MESSENGER_URL', '#');
/** Số hotline (mảng hoặc một chuỗi), để trống thì ẩn block */
define('SITE_PHONES', []); // ví dụ: ['0901 234 567', '0909 888 888']

/**
 * Đường dẫn đăng nhập admin (ẩn — không đặt link công khai trên site)
 * Thư mục: /hoaily19/index.php → truy cập: http://domain/hoaily19/
 */
define('ADMIN_LOGIN_PATH', '/hoaily19/');

/** Trang dashboard sau khi đăng nhập */
define('ADMIN_HOME_PATH', '/admin/index.php');

/**
 * Session
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
