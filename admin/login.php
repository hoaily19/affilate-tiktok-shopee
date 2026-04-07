<?php
/**
 * Trang đăng nhập quản trị
 */
require_once 'auth.php';

$errorMsg = '';

// Đã đăng nhập admin (đã kiểm tra lại trong DB) → vào dashboard
if (getAdminUserFromSession() !== null) {
    header('Location: index.php');
    exit;
}

// Xử lý đăng nhập — chỉ user có role = admin được vào khu quản trị
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errorMsg = 'Vui lòng nhập username và password.';
    } else {
        $conn = getDB();

        $stmt = $conn->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($password, $row['password'])) {
            if (($row['role'] ?? '') !== 'admin') {
                $errorMsg = 'Tài khoản không có quyền quản trị (cần role admin).';
            } else {
                $_SESSION['user_id'] = (int) $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_role'] = $row['role'];

                header('Location: index.php');
                exit;
            }
        } else {
            $errorMsg = 'Username hoặc password không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fab fa-tiktok" style="color: var(--primary); font-size: 3rem;"></i>
            </div>
            <h1><?php echo SITE_NAME; ?></h1>

            <?php if ($errorMsg): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-save" style="width: 100%; justify-content: center; padding: 15px;">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>

            <p style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 0.9rem;">
                <a href="../index.php" style="color: var(--primary);">
                    <i class="fas fa-arrow-left"></i> Quay lại website
                </a>
            </p>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                <p style="color: #999; font-size: 0.85rem;">
                    <strong>Tài khoản mặc định:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
