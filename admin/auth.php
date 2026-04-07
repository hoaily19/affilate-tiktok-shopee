<?php
/**
 * Quyền quản trị lấy từ bảng users (role = admin), không chỉ dựa vào cờ session tùy ý.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db/connect.php';

/**
 * @return array{id:int,username:string,role:string}|null
 */
function getAdminUserFromSession(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $conn = getDB();
    if (!$conn || $conn->connect_error) {
        return null;
    }
    $id = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $_SESSION = [];
        return null;
    }
    return $user;
}

/**
 * Chặn truy cập nếu không phải admin trong DB.
 *
 * @return array{id:int,username:string,role:string}
 */
function requireAdmin(): array {
    $user = getAdminUserFromSession();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }
    return $user;
}
