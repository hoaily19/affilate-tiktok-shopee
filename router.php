<?php
/**
 * Router cho PHP built-in server
 * Chạy: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri === false ? '/' : $uri;
$uri = '/' . ltrim(str_replace('\\', '/', $uri), '/');
if ($uri === '//') $uri = '/';

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
