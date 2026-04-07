-- Cài đặt link kênh TikTok / gian hàng Shopee (chạy một lần nếu site không tự tạo bảng)
USE tiktok_affiliate;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('tiktok_channel_url', ''),
('shopee_store_url', '');
