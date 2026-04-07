<?php
/**
 * Router cho PHP built-in server
 * Chạy: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri === false ? '/' : $uri;
$uri = '/' . ltrim(str_replace('\\', '/', $uri), '/');
if ($uri === '//') $uri = '/';

// Chuẩn hóa: không hiển thị index.php — chuyển về slug trang chủ /?…
if ($uri === '/index.php') {
    require_once __DIR__ . '/config/config.php';
    $qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '';
    $p = trim((string) (defined('SHOP_BASE_PATH') ? SHOP_BASE_PATH : ''), '/');
    $loc = $p === '' ? '/' : '/' . $p;
    header('Location: ' . $loc . $qs, true, 302);
    return true;
}

// Trình duyệt hay gọi /favicon.ico trước khi đọc <link rel="icon">
if ($uri === '/favicon.ico') {
    foreach ([__DIR__ . DIRECTORY_SEPARATOR . 'icon.png', __DIR__ . DIRECTORY_SEPARATOR . 'logo.png'] as $f) {
        if (is_file($f)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            readfile($f);
            return true;
        }
    }
    http_response_code(204);
    return true;
}

// Alias /icon → icon.png, /logo → logo.png (và ngược lại nếu thiếu file)
if (in_array($uri, ['/icon', '/icon.png'], true)) {
    foreach ([__DIR__ . DIRECTORY_SEPARATOR . 'icon.png', __DIR__ . DIRECTORY_SEPARATOR . 'logo.png'] as $f) {
        if (is_file($f)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            readfile($f);
            return true;
        }
    }
    http_response_code(404);
    return true;
}
if (in_array($uri, ['/logo', '/logo.png'], true)) {
    foreach ([__DIR__ . DIRECTORY_SEPARATOR . 'logo.png', __DIR__ . DIRECTORY_SEPARATOR . 'icon.png'] as $f) {
        if (is_file($f)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            readfile($f);
            return true;
        }
    }
    http_response_code(404);
    return true;
}

$path = __DIR__ . $uri;

// File tĩnh / .php có sẵn → để server PHP xử lý
if ($uri !== '/' && is_file($path)) {
    return false;
}

// Trang chủ
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

// Danh mục dạng slug: /thoi-trang (không trùng file, thư mục, route hệ thống)
$routerReservedSlugs = ['admin', 'assets', 'db', 'config', 'includes', 'install', 'hoaily19', 'buy', 'icon', 'logo'];
if (preg_match('#^/([a-z0-9][a-z0-9\-_]*)/?$#i', $uri, $m)) {
    $seg = $m[1];
    if (!in_array(strtolower($seg), array_map('strtolower', $routerReservedSlugs), true)) {
        $probe = __DIR__ . DIRECTORY_SEPARATOR . $seg;
        if (!is_file($probe) && !is_dir($probe)) {
            $_GET['category_slug'] = $seg;
            $_REQUEST['category_slug'] = $seg;
            require __DIR__ . '/index.php';
            return true;
        }
    }
}

// Thư mục có index.php (vd: /admin)
if (is_dir($path)) {
    $index = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "404 Not Found\n";
echo "Chạy server: php -S localhost:8000 router.php";
return true;
