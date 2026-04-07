# TikTok Affiliate Shop

## Cách chạy

### Windows
1. Chạy file `serve.bat` hoặc
2. Mở terminal, cd vào thư mục project, chạy:
   ```
   php -S localhost:8000 router.php
   ```

### Cài đặt Database
1. Mở phpMyAdmin hoặc MySQL CLI
2. Import file `install/schema.sql`

### Đăng nhập Admin
- URL: http://localhost:8000/admin/login.php
- Username: `admin`
- Password: `admin123`

## Tính năng
- Thêm sản phẩm từ link TikTok Shop và Shopee
- Không cần API - chỉ cần dán link
- Tự động trích xuất thông tin sản phẩm
- Hiển thị sản phẩm trên trang chủ
- Ghi log click khi mua hàng
- Quản lý sản phẩm (thêm, sửa, xóa)
