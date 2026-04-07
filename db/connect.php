<?php
/**
 * Kết nối database
 */
require_once __DIR__ . '/../config/config.php';

function getDB() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return null;
        }

        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
