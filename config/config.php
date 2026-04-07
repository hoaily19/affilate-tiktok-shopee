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
define('SITE_NAME', 'TikTok Affiliate Shop');
define('SITE_URL', 'http://localhost:8000');

/**
 * Session
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
