# TikTok Affiliate Shop

## Cách chạy

### Windows
1. Chạy file `serve.bat` hoặc
2. Mở terminal, cd vào thư mục project, chạy:
   ```
   php -S localhost:8000 router.php
   ```
   **Bắt buộc dùng `router.php`** để địa chỉ trang chủ là `http://localhost:8000/` (slug), không còn `index.php` trên thanh URL; favicon/tab dùng đường dẫn `/icon`.

### Apache
- Bật `mod_rewrite`, trỏ document root vào thư mục project; file `.htaccess` đã có redirect `index.php` → `/` và alias `/icon`, `/logo` → `logo.png`.

### Cài đặt Database
1. Mở phpMyAdmin hoặc MySQL CLI
2. Import file `install/schema.sql`

**Đã có database từ bản cũ:** chạy thêm `install/migrate_site_settings.sql` (hoặc mở trang **Admin → Cài đặt trang** — hệ thống sẽ tự tạo bảng nếu được quyền `CREATE TABLE`).

### Đăng nhập Admin (đường dẫn ẩn — không có nút trên trang chủ)
- URL: **http://localhost:8000/hoaily19/** (có dấu `/` cuối)
- Truy cập trực tiếp `/admin/login.php` sẽ chuyển về trang chủ.
- Username: `admin`
- Password: `admin123`

Đổi slug thư mục: đổi tên `hoaily19/` và cập nhật `ADMIN_LOGIN_PATH` trong `config/config.php`.

## Logo & favicon
Đặt **`logo.png`** (header, menu) và **`icon.png`** (tab trình duyệt) ngay thư mục gốc project. Trang dùng đường dẫn `/logo.png` và `/icon.png`.

## Tính năng
- **Admin → Cài đặt trang:** nhập link kênh TikTok và gian hàng Shopee → hiện 2 nút trên hero trang chủ
- Thêm sản phẩm từ link TikTok Shop và Shopee
- Không cần API - chỉ cần dán link
- Tự động trích xuất thông tin sản phẩm
- Hiển thị sản phẩm trên trang chủ
- Ghi log click khi mua hàng
- Quản lý sản phẩm (thêm, sửa, xóa)
